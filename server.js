const express = require('express');
const http = require('http');
const { Server } = require('socket.io');

const app = express();
const server = http.createServer(app);

const activeOffersByRoom = new Map();
const activeCallsById = new Map();
const CALL_LOBBY_ROOM = 'emergency-lobby';
const OFFER_TTL_MS = 4 * 60 * 60 * 1000;

function cleanText(value, max = 200) {
  return typeof value === 'string' ? value.trim().slice(0, max) : '';
}

function pruneExpiredCalls() {
  const cutoff = Date.now() - OFFER_TTL_MS;
  for (const [room, offer] of activeOffersByRoom.entries()) {
    if (!offer || offer.ts < cutoff) activeOffersByRoom.delete(room);
  }
  for (const [callId, call] of activeCallsById.entries()) {
    if (!call || call.updatedAt < cutoff) activeCallsById.delete(callId);
  }
}

function callSummary(call) {
  return {
    callId: call.callId,
    room: call.room,
    status: call.status,
    adminKey: call.adminKey || null,
    offer: call.offer || null,
    caller: call.offer?.caller || null,
    location: call.offer?.location || null,
    conversationId: call.offer?.conversationId || null,
    updatedAt: call.updatedAt,
  };
}

const io = new Server(server, {
  allowEIO3: true,
  cors: {
    origin: '*',
    methods: ['GET', 'POST'],
  },
});

app.get('/health', (req, res) => {
  res.json({ ok: true });
});

io.on('connection', (socket) => {
  console.log(`[socket] connected ${socket.id}`);

  socket.on('join', (room) => {
    if (typeof room === 'string' && room.length > 0) {
      pruneExpiredCalls();
      socket.join(room);
      console.log(`[socket] ${socket.id} joined room=${room}`);

      const cached = activeOffersByRoom.get(room);
      const cachedCallId = cleanText(cached?.payload?.callId, 128);
      const cachedCall = cachedCallId ? activeCallsById.get(cachedCallId) : null;
      if (cached && Date.now() - cached.ts <= OFFER_TTL_MS && (!cachedCall || cachedCall.status === 'ringing')) {
        socket.emit('offer', cached.payload);
        console.log(`[socket] replayed cached offer room=${room} callId=${cached.payload?.callId || ''}`);
      }
      if (room === CALL_LOBBY_ROOM) {
        for (const call of activeCallsById.values()) {
          if (call.status === 'ringing' && call.offer) socket.emit('offer', call.offer);
        }
      }
    }
  });

  socket.on('offer', (payload, room) => {
    const signalRoom = typeof payload?.room === 'string' && payload.room.length > 0 ? payload.room : room;
    const callId = cleanText(payload?.callId, 128);
    if (typeof signalRoom === 'string' && signalRoom.length > 0) {
      activeOffersByRoom.set(signalRoom, { payload, ts: Date.now() });
      console.log(`[signal] offer room=${signalRoom} broadcast=${room} callId=${payload?.callId || ''}`);
    }
    if (callId && signalRoom) {
      const current = activeCallsById.get(callId);
      activeCallsById.set(callId, {
        callId,
        room: signalRoom,
        offer: payload,
        callerSocketId: socket.id,
        adminSocketId: current?.adminSocketId || null,
        adminKey: current?.adminKey || null,
        status: current?.status === 'accepted' ? 'accepted' : 'ringing',
        updatedAt: Date.now(),
      });
    }
    socket.to(room).emit('offer', payload);
  });

  socket.on('claim-call', (payload, acknowledge) => {
    const callId = cleanText(payload?.callId, 128);
    const adminKey = cleanText(payload?.adminKey, 160);
    const call = activeCallsById.get(callId);
    if (!call || !adminKey) {
      if (typeof acknowledge === 'function') acknowledge({ ok: false, reason: 'Call is no longer available.' });
      return;
    }
    if (call.adminKey && call.adminKey !== adminKey) {
      if (typeof acknowledge === 'function') acknowledge({ ok: false, reason: 'This call was answered by another admin.' });
      return;
    }
    call.status = 'accepted';
    call.adminKey = adminKey;
    call.adminSocketId = socket.id;
    call.updatedAt = Date.now();
    socket.join(call.room);
    socket.to(CALL_LOBBY_ROOM).emit('call-claimed', { callId, adminKey });
    if (typeof acknowledge === 'function') acknowledge({ ok: true, call: callSummary(call) });
  });

  socket.on('resume-admin-call', (payload, acknowledge) => {
    const callId = cleanText(payload?.callId, 128);
    const room = cleanText(payload?.room, 180) || `emergency-call-${callId}`;
    const adminKey = cleanText(payload?.adminKey, 160);
    if (!callId || !adminKey) {
      if (typeof acknowledge === 'function') acknowledge({ ok: false, reason: 'Invalid call resume request.' });
      return;
    }
    let call = activeCallsById.get(callId);
    if (call?.adminKey && call.adminKey !== adminKey) {
      if (typeof acknowledge === 'function') acknowledge({ ok: false, reason: 'This call belongs to another admin.' });
      return;
    }
    if (!call) {
      call = { callId, room, offer: null, callerSocketId: null, status: 'accepted', updatedAt: Date.now() };
      activeCallsById.set(callId, call);
    }
    call.status = 'accepted';
    call.adminKey = adminKey;
    call.adminSocketId = socket.id;
    call.updatedAt = Date.now();
    socket.join(call.room);
    io.to(call.room).emit('request-offer', { callId, room: call.room, reason: 'admin-resume' });
    if (typeof acknowledge === 'function') acknowledge({ ok: true, call: callSummary(call) });
  });

  socket.on('resume-user-call', (payload, acknowledge) => {
    const callId = cleanText(payload?.callId, 128);
    const room = cleanText(payload?.room, 180) || `emergency-call-${callId}`;
    if (!callId) {
      if (typeof acknowledge === 'function') acknowledge({ ok: false });
      return;
    }
    let call = activeCallsById.get(callId);
    if (!call) {
      call = { callId, room, offer: null, adminSocketId: null, adminKey: null, status: payload?.accepted ? 'accepted' : 'ringing' };
      activeCallsById.set(callId, call);
    }
    call.room = room;
    call.callerSocketId = socket.id;
    call.updatedAt = Date.now();
    socket.join(room);
    if (typeof acknowledge === 'function') acknowledge({ ok: true, call: callSummary(call) });
  });

  socket.on('answer', (payload, room) => {
    console.log(`[signal] answer room=${room} callId=${payload?.callId || ''}`);
    socket.to(room).emit('answer', payload);
  });

  socket.on('candidate', (candidate, room) => {
    socket.to(room).emit('candidate', candidate);
  });

  socket.on('hangup', (payload, room) => {
    if (typeof room === 'string' && room.length > 0) {
      activeOffersByRoom.delete(room);
      if (typeof payload?.room === 'string') activeOffersByRoom.delete(payload.room);
      console.log(`[signal] hangup room=${room} callId=${payload?.callId || ''}`);
    }
    if (payload?.callId) activeCallsById.delete(String(payload.callId));
    socket.to(room).emit('hangup', payload);
  });

  socket.on('call-message', (payload, room) => {
    if (typeof room === 'string' && room.length > 0) {
      console.log(`[message] room=${room} callId=${payload?.callId || ''} sender=${payload?.sender || 'unknown'}`);
      socket.to(room).emit('call-message', payload);
    }
  });

  socket.on('call-transfer', (payload, room) => {
    if (typeof room === 'string' && room.length > 0) {
      activeOffersByRoom.delete(room);
      if (typeof payload?.room === 'string') activeOffersByRoom.delete(payload.room);
      console.log(`[transfer] room=${room} callId=${payload?.callId || ''}`);
    }
    if (payload?.callId) activeCallsById.delete(String(payload.callId));
    socket.to(room).emit('call-transfer', payload);
  });

  socket.on('disconnect', (reason) => {
    for (const call of activeCallsById.values()) {
      if (call.callerSocketId === socket.id) call.callerSocketId = null;
      if (call.adminSocketId === socket.id) call.adminSocketId = null;
    }
    console.log(`[socket] disconnected ${socket.id} reason=${reason}`);
  });
});

setInterval(pruneExpiredCalls, 60 * 1000).unref();

const PORT = process.env.SOCKET_PORT ? Number(process.env.SOCKET_PORT) : 3000;
const HOST = process.env.SOCKET_HOST || '0.0.0.0'; // Listen on all interfaces for production
server.listen(PORT, HOST, () => {
  console.log(`Socket.IO signaling server listening on ${HOST}:${PORT}`);
});
