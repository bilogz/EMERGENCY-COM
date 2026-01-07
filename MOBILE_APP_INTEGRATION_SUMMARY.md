# Mobile App Integration Summary

## Overview
This document summarizes the integration of the mobile app with the web-based user system, ensuring unified authentication and feature parity.

## Important Update: Password is Optional ✅

**Password is now optional** during registration, matching the web-based system:
- Users can sign up with Google OAuth (no password)
- Users can sign up with phone OTP (no password)
- Users can optionally set a password during manual signup
- Users without passwords must use Google OAuth or phone OTP to login

## Completed Tasks ✅

### 1. API Endpoints Updated
- **NetworkConfig.kt**: Updated to use `USERS/api/` endpoints instead of `Emergency_Comms_API/api/`
  - Production: `{HOST}/EMERGENCY-COM/USERS/api/`
  - Local: `http://{IP}/EMERGENCY-COM/USERS/api/`

### 2. New API Endpoints Created
- **register.php** (`USERS/api/register.php`): Mobile app registration endpoint
  - Supports all web-based signup fields (district, barangay, house_number, street, nationality)
  - Handles device registration
  - Returns token format for mobile app
  
- **google-oauth-mobile.php** (`USERS/api/google-oauth-mobile.php`): Google OAuth for mobile
  - Accepts Google user info from mobile app
  - Creates/updates user accounts
  - Returns token format compatible with mobile app
  - Handles device registration

### 3. Mobile App Code Updates
- **AuthApiService.kt**: Added endpoints for:
  - `google-oauth-mobile.php` - Google OAuth
  - `send-otp.php` - Phone OTP sending
  - `verify-otp.php` - OTP verification
  - `register-after-otp.php` - Registration after OTP

- **AuthModels.kt**: Added new request models:
  - `GoogleOAuthRequest` - For Google sign-in
  - `PhoneOtpSignupRequest` - For phone OTP signup
  - `PhoneOtpLoginRequest` - For phone OTP login
  - `OtpVerifyRequest` - For OTP verification
  - Updated `RegisterRequest` to include: district, barangay, house_number, street, nationality

- **AuthRepository.kt**: Added methods:
  - `googleOAuth()` - Handle Google OAuth authentication
  - `sendOtp()` - Send OTP to phone
  - `verifyOtp()` - Verify OTP code
  - `registerAfterOtp()` - Complete registration after OTP
  - Updated `register()` to include address fields

- **SignUpViewModel.kt**: Updated to handle new signup fields

- **build.gradle.kts**: Added Google Sign-In dependency
  - `com.google.android.gms:play-services-auth:20.7.0`

### 4. Website Updates
- **index.php**: Fixed APK download link to point to correct file
  - Changed from `emergency-com.apk` to `emergency-comms-app.apk`

## Pending Tasks ⚠️

### 1. SignUpScreen UI Updates
The SignUpScreen needs to be updated to include:
- District dropdown/selector
- Barangay search/autocomplete field
- House number field
- Street field
- Nationality field
- Google OAuth sign-up button
- Phone OTP sign-up option
- Scrollable form (for long form)

**Note**: The ViewModel and API are ready, but the UI needs to be updated to match the web-based signup form.

### 2. Google OAuth Implementation
- Add Google Sign-In button to SignUpScreen and LoginScreen
- Implement Google Sign-In flow using `com.google.android.gms.auth`
- Handle Google Sign-In result and call `googleOAuth()` API
- Store Google credentials securely

### 3. Phone OTP Implementation
- Add phone OTP signup flow to SignUpScreen
- Add phone OTP login flow to LoginScreen
- Implement OTP verification UI
- Handle OTP resend functionality

### 4. LoginScreen Updates
- Add Google OAuth login button
- Add phone OTP login option
- Ensure email/phone/password login works with new endpoints

### 5. Feature Parity Check
Ensure mobile app has all features from web-based user side:
- [ ] Alerts/Notifications
- [ ] Emergency Call
- [ ] Profile Management
- [ ] Language Settings
- [ ] Chat/Messaging
- [ ] Location Services
- [ ] Auto-warning Preferences

## API Endpoints Reference

### Authentication Endpoints
- `POST /USERS/api/login.php` - Email/Phone + Password login (only for users with passwords)
- `POST /USERS/api/register.php` - Manual registration (password is optional)
- `POST /USERS/api/google-oauth-mobile.php` - Google OAuth authentication
- `POST /USERS/api/send-otp.php` - Send OTP to phone
- `POST /USERS/api/verify-otp.php` - Verify OTP code
- `POST /USERS/api/register-after-otp.php` - Complete registration after OTP

**Note**: Password is optional during registration. Users can sign up with:
- Google OAuth (no password required)
- Phone OTP (no password required)
- Manual signup with optional password

### Response Format
All authentication endpoints return:
```json
{
  "success": true/false,
  "message": "Status message",
  "user_id": 123,
  "username": "User Name",
  "email": "user@example.com",
  "phone": "+639123456789",
  "token": "authentication_token_here"
}
```

## Testing Checklist

### Registration
- [ ] Manual signup with all fields (name, email, phone, password, district, barangay, house_number, street, nationality)
- [ ] Google OAuth signup
- [ ] Phone OTP signup
- [ ] Device registration on signup

### Login
- [ ] Email + Password login
- [ ] Phone + Password login
- [ ] Google OAuth login
- [ ] Phone OTP login
- [ ] Device registration on login

### Features
- [ ] All web-based features accessible in mobile app
- [ ] Data sync between web and mobile
- [ ] Push notifications working
- [ ] Location services working

## Next Steps

1. **Complete SignUpScreen UI**: Add all required fields and authentication options
2. **Implement Google OAuth**: Add Google Sign-In SDK integration
3. **Implement Phone OTP**: Add OTP verification flows
4. **Update LoginScreen**: Add Google OAuth and phone OTP options
5. **Test Integration**: Verify all authentication methods work
6. **Feature Parity**: Ensure all web features are available in mobile app
7. **Build APK**: Generate release APK and place in `USERS/downloads/emergency-comms-app.apk`

## Notes

- The mobile app now uses the same database and API endpoints as the web-based system
- All user accounts created via mobile app are compatible with web login and vice versa
- Device tracking is implemented for both registration and login
- The APK download is available on the main website index page

