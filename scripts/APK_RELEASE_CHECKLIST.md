# APK Release Readiness Checklist

Use this before sharing the APK with other users.

## Backend Stability
- [ ] `https://emergency-comm.alertaraqc.com/PHP/api/login.php` responds (not 5xx).
- [ ] No spikes of PHP-FPM/nginx errors during login tests.
- [ ] Database is writable (new login/device rows are saved).

## Authentication
- [ ] Google login works on release APK.
- [ ] Email/password login works.
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
