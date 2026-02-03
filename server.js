 const express = require('express');
const http = require('http');
const { Server } = require('socket.io');

const app = express();
const server = http.createServer(app);

const activeOffersByRoom = new Map();
const OFFER_TTL_MS = 60 * 1000;

const io = new Server(server, {
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
      socket.join(room);
      console.log(`[socket] ${socket.id} joined room=${room}`);

      const cached = activeOffersByRoom.get(room);
      if (cached && Date.now() - cached.ts <= OFFER_TTL_MS) {
        socket.emit('offer', cached.payload);
        console.log(`[socket] replayed cached offer room=${room} callId=${cached.payload?.callId || ''}`);
      }
    }
  });

  socket.on('offer', (payload, room) => {
    if (typeof room === 'string' && room.length > 0) {
      activeOffersByRoom.set(room, { payload, ts: Date.now() });
      console.log(`[signal] offer room=${room} callId=${payload?.callId || ''}`);
    }
    socket.to(room).emit('offer', payload);
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
      console.log(`[signal] hangup room=${room} callId=${payload?.callId || ''}`);
    }
    socket.to(room).emit('hangup', payload);
  });

  socket.on('call-message', (payload, room) => {
    if (typeof room === 'string' && room.length > 0) {
      console.log(`[message] room=${room} callId=${payload?.callId || ''} sender=${payload?.sender || 'unknown'}`);
      socket.to(room).emit('call-message', payload);
    }
  });

  socket.on('disconnect', (reason) => {
    console.log(`[socket] disconnected ${socket.id} reason=${reason}`);
  });
});

const PORT = process.env.SOCKET_PORT ? Number(process.env.SOCKET_PORT) : 3000;
server.listen(PORT, () => {
  console.log(`Socket.IO signaling server listening on port ${PORT}`);
});
