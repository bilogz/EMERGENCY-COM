# APK Release Readiness Checklist

Use this before sharing the APK with other users.

## Backend Stability
- [x] `https://emergency-comm.alertaraqc.com/PHP/api/login.php` responds (not 5xx).
- [ ] No spikes of PHP-FPM/nginx errors during login tests.
- [ ] Database is writable (new login/device rows are saved).

## Authentication
- [x] Google login works on release APK.
- [ ] Email/password login works.
- [x] Invalid method/content-type handling returns proper 4xx (405/400), not 500.
- [ ] Invalid credentials return 401 (not 500).

## Device Token + Push
- [ ] `user_devices.fcm_token` updates after login.
- [ ] Admin broadcast creates alert and queues push jobs.
- [ ] Device receives notification in foreground and background.

## APK Real-World Test
- [ ] Fresh install on phone A, login works.
- [ ] Fresh install on phone B, login works.
- [ ] Reinstall app, login still works, push still arrives.
- [ ] App works on mobile data (not only Wi-Fi).

## Security / Config
- [ ] Production has valid TLS cert.
- [ ] `.env`/service account keys are not publicly accessible.
- [ ] `APP_DEBUG=false` in production.

## Current Progress (Feb 12, 2026)
- [x] `login.php` now enforces POST and JSON content type with clear errors.
- [x] Google ID token audience fallback is in place for current recovery.
- [x] Admin broadcast endpoint already includes `fcm_helper.php` trigger path.

## Next Step (Do These Now)
1. Pull latest `main` on live server and update `PHP/api/login.php`.
2. Test login from APK on two devices (Wi-Fi and mobile data).
3. Confirm `user_devices.fcm_token` updates for both users/devices.
4. Send one admin broadcast and verify push receipt in foreground/background.
5. Watch nginx/php-fpm logs for 30 minutes; if no 500s, freeze this release state.
