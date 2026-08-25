const express = require('express');
const http = require('http');
const fs = require('fs');
const path = require('path');
const { Server } = require('socket.io');

const app = express();
const server = http.createServer(app);

const activeOffersByRoom = new Map();
const activeCallsById = new Map();
const recentMessagesByRoom = new Map();
const CALL_LOBBY_ROOM = 'emergency-lobby';
const TRANSFER_INBOX_ROOM = 'ers-transfer-inbox';
const SIGNALING_PROTOCOL_VERSION = '2026-08-01.8';
const OFFER_TTL_MS = 60 * 60 * 1000;
const RINGING_CALL_TTL_MS = 10 * 60 * 1000;
const MESSAGE_TTL_MS = 60 * 60 * 1000;
const MAX_MESSAGES_PER_ROOM = 50;
const MAX_ACTIVE_OFFERS = Number(process.env.MAX_ACTIVE_OFFERS || 500);
const MAX_ACTIVE_CALLS = Number(process.env.MAX_ACTIVE_CALLS || 500);
const SOCKET_DEBUG = process.env.SOCKET_DEBUG === '1';
const CALL_STATE_FILE = path.join(__dirname, 'ADMIN', 'api', 'cache', 'live-call-state.json');
const TERMINAL_CALL_STATUSES = new Set(['ended', 'declined', 'cancelled', 'canceled', 'completed', 'resolved']);

function debugLog(message) {
  if (SOCKET_DEBUG) console.log(message);
}

function cleanText(value, max = 200) {
  return typeof value === 'string' ? value.trim().slice(0, max) : '';
}

function signalText(value, max = 200) {
  return value === null || value === undefined ? '' : cleanText(String(value), max);
}

function pruneExpiredCalls() {
  const now = Date.now();
  const cutoff = now - OFFER_TTL_MS;
  const ringingCutoff = now - RINGING_CALL_TTL_MS;
  let stateChanged = false;
  for (const [room, offer] of activeOffersByRoom.entries()) {
    if (!offer || offer.ts < cutoff) {
      activeOffersByRoom.delete(room);
      stateChanged = true;
    }
  }
  for (const [callId, call] of activeCallsById.entries()) {
    if (!call || call.updatedAt < cutoff || (call.status === 'ringing' && call.updatedAt < ringingCutoff)) {
      if (call?.room) activeOffersByRoom.delete(call.room);
      activeCallsById.delete(callId);
      stateChanged = true;
    }
  }
  for (const [room, messages] of recentMessagesByRoom.entries()) {
    const current = Array.isArray(messages)
      ? messages.filter((message) => Number(message?.serverTimestamp || 0) >= Date.now() - MESSAGE_TTL_MS)
      : [];
    if (current.length) recentMessagesByRoom.set(room, current.slice(-MAX_MESSAGES_PER_ROOM));
    else recentMessagesByRoom.delete(room);
  }
  while (activeOffersByRoom.size > MAX_ACTIVE_OFFERS) {
    const oldestRoom = activeOffersByRoom.keys().next().value;
    if (!oldestRoom) break;
    activeOffersByRoom.delete(oldestRoom);
    stateChanged = true;
  }
  while (activeCallsById.size > MAX_ACTIVE_CALLS) {
    const oldestCallId = activeCallsById.keys().next().value;
    if (!oldestCallId) break;
    activeCallsById.delete(oldestCallId);
    stateChanged = true;
  }
  if (stateChanged) persistCallState();
}

function persistCallState() {
  try {
    fs.mkdirSync(path.dirname(CALL_STATE_FILE), { recursive: true });
    const state = {
      version: 1,
      savedAt: Date.now(),
      calls: Array.from(activeCallsById.values())
        .filter((call) => call && !TERMINAL_CALL_STATUSES.has(String(call.status || '').toLowerCase()))
        .map((call) => ({
          ...call,
          callerSocketId: null,
          adminSocketId: null,
        })),
      offers: Array.from(activeOffersByRoom.entries()).map(([room, offer]) => ({
        room,
        payload: offer?.payload || null,
        ts: Number(offer?.ts || Date.now()),
      })),
    };
    const tmpFile = `${CALL_STATE_FILE}.tmp`;
    fs.writeFileSync(tmpFile, JSON.stringify(state));
    fs.renameSync(tmpFile, CALL_STATE_FILE);
  } catch (error) {
    console.warn(`[call-state] persist failed: ${error.message}`);
  }
}

function restoreCallState() {
  try {
    if (!fs.existsSync(CALL_STATE_FILE)) return;
    const state = JSON.parse(fs.readFileSync(CALL_STATE_FILE, 'utf8'));
    const now = Date.now();
    for (const call of Array.isArray(state.calls) ? state.calls : []) {
      if (!call?.callId) continue;
      if (TERMINAL_CALL_STATUSES.has(String(call.status || '').toLowerCase())) continue;
      const updatedAt = Number(call.updatedAt || call.createdAt || 0) || now;
      if (now - updatedAt > OFFER_TTL_MS) continue;
      activeCallsById.set(String(call.callId), {
        ...call,
        callerSocketId: null,
        adminSocketId: null,
        updatedAt,
      });
    }
    for (const offer of Array.isArray(state.offers) ? state.offers : []) {
      const ts = Number(offer?.ts || 0) || now;
      if (!offer?.room || !offer?.payload || now - ts > OFFER_TTL_MS) continue;
      activeOffersByRoom.set(String(offer.room), { payload: offer.payload, ts });
    }
    if (activeCallsById.size) console.log(`[call-state] restored ${activeCallsById.size} live call(s)`);
  } catch (error) {
    console.warn(`[call-state] restore failed: ${error.message}`);
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
    offer: call.offer || null,
    updatedAt: call.updatedAt,
  };
}

function callQueueSummary(call) {
  return {
    callId: call.callId,
    room: call.room,
    status: call.status,
    adminKey: call.adminKey || null,
    caller: call.offer?.caller || null,
    location: call.offer?.location || null,
    conversationId: call.offer?.conversationId || null,
    offer: call.offer || null,
    createdAt: call.createdAt || call.updatedAt,
    updatedAt: call.updatedAt,
  };
}

function emitCallQueue() {
  pruneExpiredCalls();
  const calls = Array.from(activeCallsById.values()).map(callQueueSummary);
  io.to(CALL_LOBBY_ROOM).emit('call-queue', {
    open: calls.filter((call) => call.status === 'ringing'),
    assigned: calls.filter((call) => call.status === 'accepted'),
    pending: calls.filter((call) => call.status === 'pending'),
    updatedAt: Date.now(),
  });
}

function emitCallUpdate(event, call) {
  if (!call) return;
  persistCallState();
  const payload = { event, call: callQueueSummary(call), updatedAt: Date.now() };
  io.to(CALL_LOBBY_ROOM).emit('call-updated', payload);
  if (call.room) io.to(call.room).emit('call-updated', payload);
  emitCallQueue();
}

function liveCallIdentity(call) {
  if (!call) return null;
  return {
    callId: call.callId,
    call_id: call.callId,
    room: call.room,
    status: call.status,
    conversationId: call.offer?.conversationId || null,
    caller: call.offer?.caller || null,
    location: call.offer?.location || null,
    updatedAt: call.updatedAt,
    socketUrl: 'https://emergency-comm.alertaraqc.com',
    socketPath: '/socket.io',
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
    recentMessagesByRoom.delete(signalRoom);
    const payloadRoom = signalText(source.room, 180);
    if (payloadRoom && payloadRoom !== signalRoom) activeOffersByRoom.delete(payloadRoom);
    console.log(`[signal] hangup room=${signalRoom} callId=${notice.callId || ''}`);
    socket.to(signalRoom).emit('hangup', notice);
    socket.to(signalRoom).emit('call-ended', notice);
    socket.to(signalRoom).emit('call_ended', notice);
  }
  if (callId) {
    const endedCall = activeCallsById.get(callId);
    if (endedCall) emitCallUpdate('ended', { ...endedCall, status: 'ended', updatedAt: Date.now() });
    activeCallsById.delete(callId);
    persistCallState();
    emitCallQueue();
  }
}

restoreCallState();

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
  debugLog(`[socket] connected ${socket.id}`);
  socket.emit('server-ready', { protocolVersion: SIGNALING_PROTOCOL_VERSION, socketId: socket.id });

  socket.on('join', (room, acknowledge) => {
    if (typeof room === 'string' && room.length > 0) {
      pruneExpiredCalls();
      socket.join(room);
      debugLog(`[socket] ${socket.id} joined room=${room}`);

      const cached = activeOffersByRoom.get(room);
      const cachedCallId = cleanText(cached?.payload?.callId, 128);
      const cachedCall = cachedCallId ? activeCallsById.get(cachedCallId) : null;
      const canReplayCachedOffer = cached
        && Date.now() - cached.ts <= OFFER_TTL_MS
        && (!cachedCall || cachedCall.status === 'ringing' || cachedCall.status === 'accepted');
      if (canReplayCachedOffer) {
        socket.emit('offer', cached.payload);
        debugLog(`[socket] replayed cached offer room=${room} callId=${cached.payload?.callId || ''}`);
      }
      if (room === CALL_LOBBY_ROOM) {
        emitCallQueue();
      }
      const roomCall = Array.from(activeCallsById.values()).find((call) => call.room === room) || null;
      // Joining a private room is presence only. A call must move from Open to
      // Assigned only through the explicit claim-call action; otherwise the
      // admin page can accidentally "answer" while just rendering the queue.
      const recentMessages = recentMessagesByRoom.get(room) || [];
      if (recentMessages.length) socket.emit('call-message-history', recentMessages);
      const memberCount = io.sockets.adapter.rooms.get(room)?.size || 0;
      io.to(room).emit('room-presence', {
        room,
        callId: roomCall?.callId || null,
        members: memberCount,
        responseTeamPresent: !!(roomCall?.adminSocketId),
      });
      if (typeof acknowledge === 'function') {
        acknowledge({
          ok: true,
          room,
          members: memberCount,
          callId: roomCall?.callId || null,
          responseTeamPresent: !!(roomCall?.adminSocketId),
          protocolVersion: SIGNALING_PROTOCOL_VERSION,
        });
      }
    } else if (typeof acknowledge === 'function') {
      acknowledge({ ok: false, reason: 'Invalid room.' });
    }
  });

  socket.on('leave', (room, acknowledge) => {
    const signalRoom = cleanText(room, 180);
    if (!signalRoom) {
      if (typeof acknowledge === 'function') acknowledge({ ok: false, reason: 'Invalid room.' });
      return;
    }
    socket.leave(signalRoom);
    const roomCall = Array.from(activeCallsById.values()).find((call) => call.room === signalRoom) || null;
    if (roomCall?.adminSocketId === socket.id) {
      roomCall.adminSocketId = null;
      roomCall.updatedAt = Date.now();
    }
    io.to(signalRoom).emit('room-presence', {
      room: signalRoom,
      callId: roomCall?.callId || null,
      members: io.sockets.adapter.rooms.get(signalRoom)?.size || 0,
      responseTeamPresent: !!(roomCall?.adminSocketId),
    });
    if (typeof acknowledge === 'function') acknowledge({ ok: true, room: signalRoom });
  });

  socket.on('offer', (payload, room) => {
    const signalRoom = typeof payload?.room === 'string' && payload.room.length > 0 ? payload.room : room;
    const announcementRoom = cleanText(room, 180);
    const callId = cleanText(payload?.callId, 128);
    if (typeof signalRoom === 'string' && signalRoom.length > 0) {
      socket.join(signalRoom);
      activeOffersByRoom.set(signalRoom, { payload, ts: Date.now() });
      debugLog(`[signal] offer room=${signalRoom} broadcast=${announcementRoom || signalRoom} callId=${payload?.callId || ''}`);
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
        createdAt: current?.createdAt || Date.now(),
        updatedAt: Date.now(),
      });
      const storedCall = activeCallsById.get(callId);
      if (!current || current.status !== storedCall.status) {
        socket.to(CALL_LOBBY_ROOM).emit('call-created', { call: callQueueSummary(storedCall), updatedAt: Date.now() });
      }
      emitCallUpdate('offered', storedCall);
    }
    // The caller and accepted admin exchange media in the private signalRoom.
    // A new incoming call is first announced to the shared admin lobby so an
    // admin can discover it, claim it, and then join the private room.
    const targetRoom = announcementRoom || signalRoom;
    socket.to(targetRoom).emit('offer', payload);
    const isTransferredOffer = payload?.transferred === true || cleanText(payload?.target, 40) === 'ers';
    if (!isTransferredOffer && targetRoom !== CALL_LOBBY_ROOM) {
      socket.to(CALL_LOBBY_ROOM).emit('offer', payload);
    }
    const activeCall = callId ? activeCallsById.get(callId) : null;
    const peerSocketId = activeCall
      ? (activeCall.callerSocketId === socket.id ? activeCall.adminSocketId : activeCall.callerSocketId)
      : null;
    const targetMembers = io.sockets.adapter.rooms.get(targetRoom);
    if (peerSocketId && peerSocketId !== socket.id && !targetMembers?.has(peerSocketId)) {
      io.to(peerSocketId).emit('offer', payload);
    }
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
    for (const item of activeCallsById.values()) {
      if (item.callId !== callId && item.adminKey === adminKey && item.status === 'accepted') {
        if (typeof acknowledge === 'function') acknowledge({ ok: false, reason: 'You already have an active call.' });
        return;
      }
    }
    call.status = 'accepted';
    call.adminKey = adminKey;
    call.adminSocketId = socket.id;
    call.updatedAt = Date.now();
    socket.join(call.room);

    const cached = activeOffersByRoom.get(call.room);
    if (cached && Date.now() - cached.ts <= OFFER_TTL_MS) {
      socket.emit('offer', {
        ...(cached.payload || {}),
        callId: call.callId,
        call_id: call.callId,
        room: call.room,
      });
      debugLog(`[socket] sent cached offer to claiming admin room=${call.room} callId=${call.callId}`);
    }
    socket.to(call.room).emit('request-offer', {
      callId: call.callId,
      call_id: call.callId,
      room: call.room,
      reason: 'admin-claimed-call',
    });

    socket.to(CALL_LOBBY_ROOM).emit('call-claimed', { callId, adminKey, call: callQueueSummary(call) });
    emitCallUpdate('claimed', call);
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
    const wasMissing = !call;
    if (!call) {
      call = { callId, room, offer: null, adminSocketId: null, adminKey: null, status: payload?.accepted ? 'accepted' : 'ringing' };
      activeCallsById.set(callId, call);
    }
    call.room = room;
    call.callerSocketId = socket.id;
    call.updatedAt = Date.now();
    socket.join(room);
    if (!call.offer) {
      socket.emit('request-offer', { callId, room, reason: 'user-resume' });
    }
    if (wasMissing || call.status === 'ringing') {
      emitCallUpdate('user-resumed', call);
    }
    if (typeof acknowledge === 'function') acknowledge({ ok: true, call: callSummary(call) });
  });

  socket.on('resolve-live-call', (payload, acknowledge) => {
    if (typeof acknowledge !== 'function') return;
    if (!socket.rooms.has(TRANSFER_INBOX_ROOM)) {
      acknowledge({ ok: false, reason: 'ERS transfer inbox membership is required.' });
      return;
    }
    pruneExpiredCalls();
    const requestedCallId = getSignalCallId(payload);
    const requestedRoom = cleanText(payload?.room, 180);
    const requestedConversationId = signalText(payload?.conversationId || payload?.conversation_id, 80);
    let call = requestedCallId ? activeCallsById.get(requestedCallId) : null;
    if (!call && requestedRoom) {
      call = Array.from(activeCallsById.values()).find((item) => item.room === requestedRoom) || null;
    }
    if (!call && requestedConversationId) {
      call = Array.from(activeCallsById.values()).find((item) => (
        signalText(item.offer?.conversationId, 80) === requestedConversationId
      )) || null;
    }
    if (!call) {
      const liveCandidates = Array.from(activeCallsById.values())
        .filter((item) => item.callerSocketId && item.room && Date.now() - item.updatedAt < 10 * 60 * 1000)
        .sort((left, right) => right.updatedAt - left.updatedAt);
      if (liveCandidates.length === 1) call = liveCandidates[0];
    }
    if (!call || !call.callerSocketId || !call.room) {
      acknowledge({ ok: false, reason: 'No matching online mobile caller was found.' });
      return;
    }
    acknowledge({ ok: true, call: liveCallIdentity(call) });
  });

  socket.on('request-offer', (payload, room, acknowledge) => {
    const signalRoom = cleanText(payload?.room, 180) || cleanText(room, 180);
    const callId = getSignalCallId(payload);
    const call = callId ? activeCallsById.get(callId) : null;
    const targetRoom = call?.room || signalRoom;
    const normalizedPayload = call
      ? { ...(payload || {}), callId: call.callId, call_id: call.callId, room: call.room }
      : { ...(payload || {}), room: targetRoom };

    if (targetRoom) socket.join(targetRoom);
    debugLog(`[signal] request-offer room=${targetRoom || ''} callId=${callId || ''}`);
    if (targetRoom) socket.to(targetRoom).emit('request-offer', normalizedPayload);

    const callerSocketId = call?.callerSocketId;
    const roomMembers = targetRoom ? io.sockets.adapter.rooms.get(targetRoom) : null;
    if (callerSocketId && callerSocketId !== socket.id && !roomMembers?.has(callerSocketId)) {
      io.to(callerSocketId).emit('request-offer', normalizedPayload);
    }

    if (typeof acknowledge === 'function') {
      acknowledge({ ok: true, room: targetRoom, callerOnline: !!callerSocketId });
    }
  });
  socket.on('answer', (payload, room) => {
    const signalRoom = cleanText(payload?.room, 180) || cleanText(room, 180);
    debugLog(`[signal] answer room=${signalRoom} callId=${payload?.callId || ''}`);
    const callId = getSignalCallId(payload);
    const call = callId ? activeCallsById.get(callId) : null;
    const normalizedPayload = call
      ? { ...(payload || {}), callId: call.callId, call_id: call.callId, room: call.room }
      : payload;
    const targetRoom = call?.room || signalRoom;
    if (targetRoom) {
      socket.join(targetRoom);
      socket.to(targetRoom).emit('answer', normalizedPayload);
    }
    const peerSocketId = call
      ? (call.callerSocketId === socket.id ? call.adminSocketId : call.callerSocketId)
      : null;
    const roomMembers = targetRoom ? io.sockets.adapter.rooms.get(targetRoom) : null;
    if (peerSocketId && peerSocketId !== socket.id && !roomMembers?.has(peerSocketId)) {
      io.to(peerSocketId).emit('answer', normalizedPayload);
    }
  });

  socket.on('candidate', (candidate, room) => {
    const signalRoom = cleanText(candidate?.room, 180) || cleanText(room, 180);
    const callId = getSignalCallId(candidate);
    const call = callId ? activeCallsById.get(callId) : null;
    const normalizedCandidate = call
      ? { ...(candidate || {}), callId: call.callId, call_id: call.callId, room: call.room }
      : candidate;
    const targetRoom = call?.room || signalRoom;
    if (targetRoom) {
      socket.join(targetRoom);
      socket.to(targetRoom).emit('candidate', normalizedCandidate);
    }
    const peerSocketId = call
      ? (call.callerSocketId === socket.id ? call.adminSocketId : call.callerSocketId)
      : null;
    const roomMembers = targetRoom ? io.sockets.adapter.rooms.get(targetRoom) : null;
    if (peerSocketId && peerSocketId !== socket.id && !roomMembers?.has(peerSocketId)) {
      io.to(peerSocketId).emit('candidate', normalizedCandidate);
    }
  });

  socket.on('hangup', (payload, room) => relayHangup(socket, payload, room));
  socket.on('call-ended', (payload, room) => relayHangup(socket, payload, room));
  socket.on('call_ended', (payload, room) => relayHangup(socket, payload, room));

  socket.on('call-message', (payload, room, acknowledge) => {
    const signalRoom = cleanText(payload?.room, 180) || cleanText(room, 180);
    const text = cleanText(payload?.text || payload?.message, 4000);
    if (!signalRoom || !text) {
      if (typeof acknowledge === 'function') acknowledge({ ok: false, reason: 'Invalid message or room.' });
      return;
    }
    const storedPayload = {
      ...(payload || {}),
      text,
      room: signalRoom,
      messageId: cleanText(payload?.messageId, 160) || `${socket.id}-${Date.now()}-${Math.random().toString(16).slice(2)}`,
      serverTimestamp: Date.now(),
    };
    const messages = recentMessagesByRoom.get(signalRoom) || [];
    if (!messages.some((message) => message.messageId === storedPayload.messageId)) {
      messages.push(storedPayload);
      recentMessagesByRoom.set(signalRoom, messages.slice(-MAX_MESSAGES_PER_ROOM));
    }
    const members = io.sockets.adapter.rooms.get(signalRoom)?.size || 0;
    debugLog(`[message] room=${signalRoom} callId=${payload?.callId || ''} sender=${payload?.sender || 'unknown'} recipients=${Math.max(0, members - 1)}`);
    socket.to(signalRoom).emit('call-message', storedPayload);
    const callId = getSignalCallId(storedPayload);
    const call = callId ? activeCallsById.get(callId) : null;
    const targetSocketId = storedPayload.sender === 'response_team'
      ? call?.callerSocketId
      : call?.adminSocketId;
    const roomMembers = io.sockets.adapter.rooms.get(signalRoom);
    let directRecipient = false;
    if (targetSocketId && targetSocketId !== socket.id && !roomMembers?.has(targetSocketId)) {
      io.to(targetSocketId).emit('call-message', storedPayload);
      directRecipient = true;
    }
    const recipientCount = Math.max(0, members - 1) + (directRecipient ? 1 : 0);
    if (typeof acknowledge === 'function') {
      acknowledge({ ok: true, queued: recipientCount === 0, recipients: recipientCount, messageId: storedPayload.messageId });
    }
  });

  const forwardTransferControl = (eventName) => {
    socket.on(eventName, (payload, room, acknowledge) => {
      const signalRoom = cleanText(payload?.room, 180) || cleanText(room, 180);
      if (!signalRoom) {
        if (typeof acknowledge === 'function') acknowledge({ ok: false, reason: 'Missing call room.' });
        return;
      }
      debugLog(`[transfer-control] ${eventName} room=${signalRoom} callId=${payload?.callId || ''}`);
      socket.to(signalRoom).emit(eventName, payload);
      // A reconnect creates a new Socket.IO connection and briefly drops room
      // membership. resume-user-call records the new caller socket, so deliver
      // directly as a fallback when it is not yet present in the room.
      const callId = getSignalCallId(payload);
      const call = callId ? activeCallsById.get(callId) : null;
      if (call && eventName !== 'request-transfer-offer') {
        call.adminSocketId = socket.id;
        call.status = 'accepted';
        call.updatedAt = Date.now();
      }
      const callerSocketId = call?.callerSocketId;
      const roomMembers = io.sockets.adapter.rooms.get(signalRoom);
      if (callerSocketId && callerSocketId !== socket.id && !roomMembers?.has(callerSocketId)) {
        io.to(callerSocketId).emit(eventName, payload);
      }
      if (typeof acknowledge === 'function') {
        acknowledge({ ok: true, room: signalRoom, callerOnline: !!callerSocketId });
      }
    });
  };

  ['dispatcher-ready', 'call-accepted', 'accepted', 'request-transfer-offer'].forEach(forwardTransferControl);

  socket.on('call-transfer', (payload, room) => {
    const transferRoom = cleanText(payload?.room, 180) || cleanText(room, 180);
    const transferType = cleanText(payload?.transfer_type, 40) || cleanText(payload?.transferType, 40);
    const isLiveTransfer = transferType === 'live_call' || !!transferRoom;
    if (transferRoom) {
      debugLog(`[transfer] room=${transferRoom} callId=${payload?.callId || ''}`);
      if (!isLiveTransfer) activeOffersByRoom.delete(transferRoom);
    }
    if (payload?.callId && !isLiveTransfer) activeCallsById.delete(String(payload.callId));
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

  // Manual admin route only. Incoming callers must first be answered by
  // Emergency Communication, then an admin can transfer the live call to ERS.
  socket.on('route-call-to-ers', (payload, acknowledge) => {
    const callId = getSignalCallId(payload);
    const room = cleanText(payload?.room, 180);
    const existing = callId ? activeCallsById.get(callId) : null;
    if (!callId || !room) {
      if (typeof acknowledge === 'function') {
        acknowledge({ ok: false, reason: 'callId and private room are required.' });
      }
      return;
    }
    if (!existing) {
      if (typeof acknowledge === 'function') {
        acknowledge({ ok: false, reason: 'The call is no longer available.' });
      }
      return;
    }
    if (existing.callerSocketId === socket.id) {
      if (typeof acknowledge === 'function') {
        acknowledge({ ok: false, reason: 'Emergency Communication admin must answer and transfer this call.' });
      }
      return;
    }
    if (!existing.adminSocketId || existing.adminSocketId !== socket.id) {
      if (typeof acknowledge === 'function') {
        acknowledge({ ok: false, reason: 'Only the assigned Emergency Communication admin can transfer this call.' });
      }
      return;
    }
    const transferNotice = {
      ...(payload || {}),
      event: 'emergency_call_transfer',
      transfer_type: 'live_call',
      transferType: 'live_call',
      callId,
      call_id: callId,
      call_id_external: callId,
      transferId: getSignalCallId(payload) || callId,
      transfer_id: getSignalCallId(payload) || callId,
      room,
      socketUrl: cleanText(payload?.socketUrl, 255) || 'https://emergency-comm.alertaraqc.com',
      socketPath: cleanText(payload?.socketPath, 100) || '/socket.io',
      source_system: 'AlertaraQC Emergency Communication',
      route: 'emergency-com-call-relay',
      transferredAt: payload?.transferredAt || new Date().toISOString(),
    };
    existing.room = room;
    existing.offer = {
      ...(existing.offer || {}),
      callId,
      room,
      caller: transferNotice.caller || existing.offer?.caller || null,
      location: transferNotice.locationData || transferNotice.location || existing.offer?.location || null,
    };
    existing.status = 'ringing';
    existing.updatedAt = Date.now();
    socket.join(room);
    io.to(TRANSFER_INBOX_ROOM).emit('incoming-transfer', transferNotice);
    io.to(TRANSFER_INBOX_ROOM).emit('ers-transfer-notify', transferNotice);
    socket.emit('call-transferred-to-ers', { callId, room });
    if (typeof acknowledge === 'function') acknowledge({ ok: true, transfer: liveCallIdentity(existing) });
  });

  socket.on('ers-transfer-notify', (payload) => {
    const hasLiveSignal = !!(cleanText(payload?.room, 180) && getSignalCallId(payload));
    const transferType = cleanText(payload?.transfer_type, 40) || cleanText(payload?.transferType, 40) || (hasLiveSignal ? 'live_call' : 'report');
    const transferNotice = {
      ...(payload || {}),
      event: cleanText(payload?.event, 80) || (transferType === 'live_call' ? 'emergency_call_transfer' : 'emergency_report_transfer'),
      transfer_type: transferType,
      transferType,
      source_system: cleanText(payload?.source_system, 180) || 'AlertaraQC Emergency Communication',
      transferredAt: payload?.transferredAt || payload?.transferred_at || new Date().toISOString(),
    };
    if (hasLiveSignal) {
      const callId = getSignalCallId(transferNotice);
      const room = cleanText(transferNotice.room, 180);
      const existing = activeCallsById.get(callId);
      const offer = {
        ...(existing?.offer || {}),
        callId,
        room,
        conversationId: transferNotice.conversationId || transferNotice.conversation_id || existing?.offer?.conversationId || null,
        caller: transferNotice.caller || existing?.offer?.caller || null,
        location: transferNotice.locationData || transferNotice.location || existing?.offer?.location || null,
      };
      activeCallsById.set(callId, {
        callId,
        room,
        offer,
        callerSocketId: socket.id,
        adminSocketId: existing?.adminSocketId || null,
        adminKey: existing?.adminKey || null,
        createdAt: existing?.createdAt || Date.now(),
        status: existing?.status || 'ringing',
        updatedAt: Date.now(),
      });
      socket.join(room);
      persistCallState();
      emitCallQueue();
    }
    debugLog(`[transfer-notify] type=${transferType} transferId=${payload?.transferId || payload?.transfer_id || ''}`);
    io.to(TRANSFER_INBOX_ROOM).emit('incoming-transfer', transferNotice);
    io.to(TRANSFER_INBOX_ROOM).emit('ers-transfer-notify', transferNotice);
  });

  socket.on('disconnect', (reason) => {
    let queueChanged = false;
    for (const call of activeCallsById.values()) {
      if (call.callerSocketId === socket.id) {
        call.callerSocketId = null;
        call.updatedAt = Date.now();
        queueChanged = true;
      }
      if (call.adminSocketId === socket.id) {
        call.adminSocketId = null;
        call.updatedAt = Date.now();
        queueChanged = true;
      }
    }
    if (queueChanged) {
      persistCallState();
      emitCallQueue();
    }
    debugLog(`[socket] disconnected ${socket.id} reason=${reason}`);
  });
});

setInterval(pruneExpiredCalls, 60 * 1000).unref();

const PORT = process.env.SOCKET_PORT ? Number(process.env.SOCKET_PORT) : 3000;
const HOST = process.env.SOCKET_HOST || '0.0.0.0'; // Listen on all interfaces for production
server.listen(PORT, HOST, () => {
  console.log(`Socket.IO signaling server listening on ${HOST}:${PORT}`);
});

