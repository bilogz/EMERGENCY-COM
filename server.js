const express = require('express');
const http = require('http');
const { Server } = require('socket.io');

const app = express();
const server = http.createServer(app);

const activeOffersByRoom = new Map();
const activeCallsById = new Map();
const CALL_LOBBY_ROOM = 'emergency-lobby';
const TRANSFER_INBOX_ROOM = 'ers-transfer-inbox';
const OFFER_TTL_MS = 4 * 60 * 60 * 1000;

function cleanText(value, max = 200) {
  return typeof value === 'string' ? value.trim().slice(0, max) : '';
}

function signalText(value, max = 200) {
  return value === null || value === undefined ? '' : cleanText(String(value), max);
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

function getSignalCallId(payload) {
  const source = payload && typeof payload === 'object' ? payload : {};
  return signalText(source.callId || source.call_id || source.transferId || source.transfer_id, 128);
}

function resolveSignalRoom(payload, room) {
  const source = payload && typeof payload === 'object' ? payload : {};
  const callId = getSignalCallId(source);
  const storedRoom = callId ? signalText(activeCallsById.get(callId)?.room, 180) : '';
  return signalText(source.room, 180) || signalText(room, 180) || storedRoom;
}

function relayHangup(socket, payload = {}, room) {
  const source = payload && typeof payload === 'object' && !Array.isArray(payload) ? payload : {};
  const callId = getSignalCallId(source);
  const signalRoom = resolveSignalRoom(source, room);
  const notice = {
    ...source,
    callId: callId || signalText(source.callId || source.call_id, 128),
    call_id: callId || signalText(source.call_id || source.callId, 128),
    room: signalRoom,
    endedAt: signalText(source.endedAt || source.ended_at, 100) || new Date().toISOString(),
  };

  if (signalRoom) {
    activeOffersByRoom.delete(signalRoom);
    const payloadRoom = signalText(source.room, 180);
    if (payloadRoom && payloadRoom !== signalRoom) activeOffersByRoom.delete(payloadRoom);
    console.log(`[signal] hangup room=${signalRoom} callId=${notice.callId || ''}`);
    socket.to(signalRoom).emit('hangup', notice);
    socket.to(signalRoom).emit('call-ended', notice);
    socket.to(signalRoom).emit('call_ended', notice);
  }
  if (callId) activeCallsById.delete(callId);
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
    const announcementRoom = cleanText(room, 180);
    const callId = cleanText(payload?.callId, 128);
    if (typeof signalRoom === 'string' && signalRoom.length > 0) {
      activeOffersByRoom.set(signalRoom, { payload, ts: Date.now() });
      console.log(`[signal] offer room=${signalRoom} broadcast=${announcementRoom || signalRoom} callId=${payload?.callId || ''}`);
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
    // The caller and accepted admin exchange media in the private signalRoom.
    // A new incoming call is first announced to the shared admin lobby so an
    // admin can discover it, claim it, and then join the private room.
    const targetRoom = announcementRoom || signalRoom;
    socket.to(targetRoom).emit('offer', payload);
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
    const signalRoom = cleanText(payload?.room, 180) || cleanText(room, 180);
    console.log(`[signal] answer room=${signalRoom} callId=${payload?.callId || ''}`);
    if (signalRoom) socket.to(signalRoom).emit('answer', payload);
  });

  socket.on('candidate', (candidate, room) => {
    const signalRoom = cleanText(candidate?.room, 180) || cleanText(room, 180);
    if (signalRoom) socket.to(signalRoom).emit('candidate', candidate);
  });

  socket.on('hangup', (payload, room) => relayHangup(socket, payload, room));
  socket.on('call-ended', (payload, room) => relayHangup(socket, payload, room));
  socket.on('call_ended', (payload, room) => relayHangup(socket, payload, room));

  socket.on('call-message', (payload, room) => {
    if (typeof room === 'string' && room.length > 0) {
      console.log(`[message] room=${room} callId=${payload?.callId || ''} sender=${payload?.sender || 'unknown'}`);
      socket.to(room).emit('call-message', payload);
    }
  });

  const forwardTransferControl = (eventName) => {
    socket.on(eventName, (payload, room) => {
      const signalRoom = cleanText(payload?.room, 180) || cleanText(room, 180);
      if (!signalRoom) return;
      console.log(`[transfer-control] ${eventName} room=${signalRoom} callId=${payload?.callId || ''}`);
      socket.to(signalRoom).emit(eventName, payload);
    });
  };

  ['dispatcher-ready', 'call-accepted', 'accepted', 'request-transfer-offer'].forEach(forwardTransferControl);

  socket.on('call-transfer', (payload, room) => {
    const transferRoom = cleanText(payload?.room, 180) || cleanText(room, 180);
    if (transferRoom) {
      activeOffersByRoom.delete(transferRoom);
      console.log(`[transfer] room=${transferRoom} callId=${payload?.callId || ''}`);
    }
    if (payload?.callId) activeCallsById.delete(String(payload.callId));
    const transferNotice = {
      ...(payload || {}),
      room: transferRoom,
      socketUrl: cleanText(payload?.socketUrl, 255) || cleanText(payload?.transfer?.socketUrl, 255) || cleanText(payload?.transfer?.data?.socketUrl, 255) || 'https://emergency-comm.alertaraqc.com',
      socketPath: cleanText(payload?.socketPath, 100) || cleanText(payload?.transfer?.socketPath, 100) || cleanText(payload?.transfer?.data?.socketPath, 100) || '/socket.io',
      event: 'emergency_call_transfer',
      source_system: 'AlertaraQC Emergency Communication',
      transferredAt: payload?.transferredAt || new Date().toISOString(),
    };
    io.to(TRANSFER_INBOX_ROOM).emit('incoming-transfer', transferNotice);
    io.to(TRANSFER_INBOX_ROOM).emit('ers-transfer-notify', transferNotice);
    if (transferRoom) socket.to(transferRoom).emit('call-transfer', transferNotice);
  });

  socket.on('ers-transfer-notify', (payload) => {
    const transferType = cleanText(payload?.transfer_type, 40) || cleanText(payload?.transferType, 40) || 'report';
    const transferNotice = {
      ...(payload || {}),
      event: cleanText(payload?.event, 80) || (transferType === 'live_call' ? 'emergency_call_transfer' : 'emergency_report_transfer'),
      transfer_type: transferType,
      transferType,
      source_system: cleanText(payload?.source_system, 180) || 'AlertaraQC Emergency Communication',
      transferredAt: payload?.transferredAt || payload?.transferred_at || new Date().toISOString(),
    };
    console.log(`[transfer-notify] type=${transferType} transferId=${payload?.transferId || payload?.transfer_id || ''}`);
    io.to(TRANSFER_INBOX_ROOM).emit('incoming-transfer', transferNotice);
    io.to(TRANSFER_INBOX_ROOM).emit('ers-transfer-notify', transferNotice);
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
