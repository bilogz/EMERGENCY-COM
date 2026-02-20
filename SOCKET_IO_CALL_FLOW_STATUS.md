# Socket.IO Internet Call Flow and Status

## Scope
This documents the internet call flow between:
- User side: `USERS/emergency-call.php`
- Admin side: `ADMIN/sidebar/two-way-communication.php`
- Signaling server: `server.js`

## How the call works
1. User clicks **Start Internet Call** in `USERS/emergency-call.php`.
2. User creates a Socket.IO connection and joins room `emergency-room`.
3. User creates a WebRTC offer (`RTCPeerConnection`) and emits:
   - `offer` with `{ sdp, callId, conversationId, caller, location }`
4. Admin page is connected to the same room and listens for `offer`.
5. Admin accepts call, sets remote offer, creates answer, emits:
   - `answer` with `{ sdp, callId }`
6. Both sides exchange ICE candidates via:
   - `candidate` with `{ candidate, callId }`
7. Audio media flows peer-to-peer once WebRTC is connected.
8. Either side can end using:
   - `hangup` with `{ callId }`
9. In-call text messages use:
   - `call-message` with `{ text, callId, sender, timestamp }`

## Server behavior (`server.js`)
- Relays signaling events: `offer`, `answer`, `candidate`, `hangup`, `call-message`.
- Uses room broadcasting (`socket.to(room).emit(...)`).
- Caches latest `offer` per room for ~60 seconds and replays it when a new socket joins that room.
- Health endpoint: `GET /health` returns `{ ok: true }`.

## Logging/storage behavior
- User call events are posted to `USERS/api/call-log.php`.
- Admin call events are posted to `ADMIN/api/call-log.php`.
- Completed call summary is posted by admin to `ADMIN/api/save-completed-call.php`.
- Important: your new endpoints in `PHP/api/user/call_event.php` and `PHP/api/user/call_history.php` are **not** currently used by this web call UI flow. The web UI uses `USERS/api/*` and `ADMIN/api/*` paths.

## Is it currently working?
Current status on this machine: **NOT working right now**.

Reason verified:
- `php check-socket-server.php` returned connection refused to `127.0.0.1:3000`.
- That means Socket.IO signaling server is not running, so call setup cannot complete.

## Additional deployment caveat
`server.js` listens on `127.0.0.1` only. This is fine for local development on one machine, but for remote/multi-device usage it will fail unless you:
- run a reverse proxy that forwards `/socket.io` and `/health` to the Node process, or
- bind to `0.0.0.0` and secure access properly.

Also, frontend logic uses:
- local: explicit `http(s)://<host>:3000`
- non-local: `io()` on same host/path (`/socket.io`) with no explicit `:3000`
So production needs proper proxying if Node is not served from the same origin.

## Quick checklist to make it work now
1. Start signaling server:
   - `node server.js`
2. Verify health:
   - `php check-socket-server.php`
3. Open admin call page (`ADMIN/sidebar/two-way-communication.php`) and keep it online.
4. From user page (`USERS/emergency-call.php`), start internet call.
5. Confirm admin receives incoming offer and can accept.
6. Confirm both sides can exchange voice + call messages.

## Key files
- `server.js`
- `check-socket-server.php`
- `USERS/emergency-call.php`
- `ADMIN/sidebar/two-way-communication.php`
- `USERS/api/call-log.php`
- `ADMIN/api/call-log.php`
- `ADMIN/api/save-completed-call.php`
- `PHP/api/user/call_event.php`
- `PHP/api/user/call_history.php`
