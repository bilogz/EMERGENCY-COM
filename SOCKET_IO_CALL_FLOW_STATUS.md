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
Current status on this machine: **WORKING** (as of Jul 13, 2026).

The Socket.IO signaling server is now running on port 3000. The issue was that Node.js dependencies (express and socket.io) were not installed. Running `npm install` resolved the dependency issue, and the server is now operational.

## Production deployment caveat
`server.js` is a plain HTTP Socket.IO server on port 3000. The browser should not connect to `https://...:3000` directly in production. The public HTTPS site must reverse proxy:
- `/socket.io/` -> `http://127.0.0.1:3000/socket.io/`
- `/health` -> `http://127.0.0.1:3000/health`

Current frontend logic uses the same HTTPS origin as the page:
- `https://emergency-comm.alertaraqc.com/socket.io`

If the browser console shows `502 Bad Gateway` for `/socket.io`, the PHP/frontend code is reaching the web server correctly, but the web server cannot reach the Node signaling process.

## Ubuntu live-server fix for `/socket.io` 502
Run these on the Ubuntu server:

1. Confirm the Node process is running:
   - `pm2 status`
   - `pm2 logs emergency-com --lines 100`
   - If PM2 is not used: `ps aux | grep "node server.js"`

2. Start or restart the signaling server from the project directory:
   - `cd /path/to/EMERGENCY-COM`
   - `npm install`
   - `pm2 start server.js --name emergency-com`
   - `pm2 save`
   - `pm2 startup`

3. Confirm port 3000 responds locally:
   - `curl -i http://127.0.0.1:3000/health`
   - Expected: `{"ok":true}`
   - `curl -i "http://127.0.0.1:3000/socket.io/?EIO=4&transport=polling"`
   - Expected: HTTP 200 with a Socket.IO open packet.

4. Confirm the public HTTPS proxy works:
   - `curl -i https://emergency-comm.alertaraqc.com/health`
   - `curl -i "https://emergency-comm.alertaraqc.com/socket.io/?EIO=4&transport=polling"`
   - Expected: HTTP 200. If this returns 502, fix the web-server proxy.

5. Nginx proxy example:
   ```nginx
   location /socket.io/ {
       proxy_pass http://127.0.0.1:3000/socket.io/;
       proxy_http_version 1.1;
       proxy_set_header Upgrade $http_upgrade;
       proxy_set_header Connection "upgrade";
       proxy_set_header Host $host;
       proxy_set_header X-Real-IP $remote_addr;
       proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
       proxy_set_header X-Forwarded-Proto $scheme;
       proxy_read_timeout 86400;
   }

   location /health {
       proxy_pass http://127.0.0.1:3000/health;
       proxy_http_version 1.1;
       proxy_set_header Host $host;
       proxy_set_header X-Forwarded-Proto $scheme;
   }
   ```

   Then reload:
   - `sudo nginx -t`
   - `sudo systemctl reload nginx`

6. Apache proxy example:
   ```apache
   ProxyPreserveHost On
   ProxyPass /socket.io/ http://127.0.0.1:3000/socket.io/
   ProxyPassReverse /socket.io/ http://127.0.0.1:3000/socket.io/
   ProxyPass /health http://127.0.0.1:3000/health
   ProxyPassReverse /health http://127.0.0.1:3000/health
   ```

   Required modules:
   - `sudo a2enmod proxy proxy_http proxy_wstunnel rewrite headers`
   - `sudo systemctl reload apache2`

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
