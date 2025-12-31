# Google OAuth Callback Setup Guide

This guide explains how to set up Google OAuth 2.0 with the callback file system.

## Files Created

1. **`google-oauth-callback.php`** - Handles the OAuth callback from Google
2. **`google-oauth-init.php`** - Initiates the OAuth flow by redirecting to Google
3. **`google-oauth.php`** - Existing file for token-based authentication (alternative method)

## OAuth Flow

### Method 1: Callback Flow (Recommended for Server-Side)

1. User clicks "Sign in with Google" button
2. User is redirected to `google-oauth-init.php`
3. `google-oauth-init.php` redirects to Google's authorization page
4. User grants permission on Google
5. Google redirects back to `google-oauth-callback.php` with authorization code
6. `google-oauth-callback.php` exchanges code for access token
7. Gets user info from Google
8. Logs user in or creates account
9. Redirects to `index.php`

### Method 2: Token Flow (Client-Side)

1. User clicks "Sign in with Google" button
2. JavaScript gets access token from Google Identity Services
3. Sends token to `google-oauth.php`
4. `google-oauth.php` verifies and processes login

## Configuration Steps

### 1. Get Google OAuth Credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a project or select an existing one
3. Enable **Google+ API** or **Google Identity Services**
4. Go to **Credentials** → **Create Credentials** → **OAuth client ID**
5. Select **Web application**
6. Configure:
   - **Name**: Emergency Communication System (or your app name)
   - **Authorized JavaScript origins**: 
     - `http://localhost` (for local development)
     - `https://yourdomain.com` (for production)
   - **Authorized redirect URIs**:
     - `http://localhost/EMERGENCY-COM/USERS/api/google-oauth-callback.php`
     - `https://yourdomain.com/USERS/api/google-oauth-callback.php`

### 2. Save Credentials

Save your **Client ID** and **Client Secret** to one of these locations:

**Option A: `.env` file** (Recommended)
```
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
```

**Option B: `config.local.php`**
```php
<?php
return [
    'GOOGLE_CLIENT_ID' => 'your-client-id.apps.googleusercontent.com',
    'GOOGLE_CLIENT_SECRET' => 'your-client-secret',
];
```

### 3. Update Your Login/Signup Pages

Add a button that redirects to the init file:

```html
<a href="api/google-oauth-init.php" class="btn btn-google">
    <i class="fab fa-google"></i> Sign in with Google
</a>
```

Or use JavaScript:

```javascript
document.getElementById('googleLoginBtn').addEventListener('click', function() {
    window.location.href = 'api/google-oauth-init.php';
});
```

## Testing

1. Visit your login page
2. Click "Sign in with Google"
3. You should be redirected to Google's authorization page
4. Grant permission
5. You should be redirected back and logged in

## Troubleshooting

### Error: "redirect_uri_mismatch"
- Make sure the redirect URI in Google Console exactly matches your callback URL
- Check for trailing slashes, http vs https, etc.

### Error: "invalid_client"
- Verify your Client ID and Client Secret are correct
- Make sure they're saved in `.env` or `config.local.php`

### Error: "access_denied"
- User cancelled the authorization
- This is normal if user clicks "Cancel" on Google's page

### Error: "no_code"
- Authorization code not received
- Check that the callback URL is correctly configured in Google Console

## Security Notes

1. **Never commit** `.env` or `config.local.php` to Git
2. The **Client Secret** must remain secret - only use it server-side
3. The **Client ID** can be exposed in frontend code
4. The callback file validates the state parameter to prevent CSRF attacks
5. Always use HTTPS in production

## Database Requirements

The system will automatically:
- Add `google_id` column to `users` table if it doesn't exist
- Link Google accounts to existing users by email
- Create new accounts for first-time Google users

## Session Variables Set

After successful login:
- `$_SESSION['user_logged_in'] = true`
- `$_SESSION['user_id']` - User ID
- `$_SESSION['user_name']` - User's name
- `$_SESSION['user_email']` - User's email
- `$_SESSION['user_type'] = 'registered'`
- `$_SESSION['login_method'] = 'google'`

## Redirect URLs

- **Success (existing user)**: `../index.php?login=success`
- **Success (new user)**: `../index.php?signup=success&welcome=1`
- **Error**: `../login.php?error=error_code`

