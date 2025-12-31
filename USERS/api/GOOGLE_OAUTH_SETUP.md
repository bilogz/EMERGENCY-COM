# Google OAuth Setup Guide

This guide explains how to set up Google OAuth authentication for the Emergency Communication Portal.

## Prerequisites

1. A Google Cloud Platform (GCP) account
2. Access to Google Cloud Console
3. A web application project in GCP

## Step 1: Create OAuth 2.0 Credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select your project (or create a new one)
3. Navigate to **APIs & Services** > **Credentials**
4. Click **Create Credentials** > **OAuth client ID**
5. If prompted, configure the OAuth consent screen:
   - Choose **External** (unless you have a Google Workspace)
   - Fill in the required information (App name, User support email, etc.)
   - Add your email to **Test users** if in testing mode
   - Click **Save and Continue**
6. For the OAuth client:
   - Application type: **Web application**
   - Name: Emergency Communication Portal (or your preferred name)
   - Authorized JavaScript origins:
     - `http://localhost` (for local development)
     - `http://localhost:8000` (if using PHP built-in server)
     - Your production domain (e.g., `https://yourdomain.com`)
   - Authorized redirect URIs:
     - `http://localhost/USERS/api/google-oauth.php` (for local development)
     - Your production URL (e.g., `https://yourdomain.com/USERS/api/google-oauth.php`)
   - Click **Create**
7. Copy the **Client ID** and **Client Secret**

## Step 2: Configure the Application

You have two options for storing credentials:

### Option A: Using .env file (Recommended)

1. Create a file named `.env` in `USERS/api/` directory
2. Add the following lines:
```
GOOGLE_CLIENT_ID=your-client-id-here.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret-here
```
3. Make sure `.env` is in `.gitignore` to prevent committing credentials

### Option B: Using config.local.php

1. Copy `config.local.example.php` to `config.local.php` in `USERS/api/` directory
2. Edit `config.local.php` and set:
```php
<?php
return [
    'GOOGLE_CLIENT_ID' => 'your-client-id-here.apps.googleusercontent.com',
    'GOOGLE_CLIENT_SECRET' => 'your-client-secret-here',
];
```
3. Make sure `config.local.php` is in `.gitignore`

## Step 3: Database Setup

The system will automatically create the `google_id` column in the `users` table if it doesn't exist. However, you can manually add it:

```sql
ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL UNIQUE;
```

## Step 4: Test the Configuration

1. Visit `USERS/api/get-google-config.php` in your browser
2. You should see:
```json
{
    "success": true,
    "client_id": "your-client-id-here"
}
```

If you see an error, check:
- The config file exists and is readable
- The `GOOGLE_CLIENT_ID` is set correctly
- File permissions are correct

## Step 5: Test Authentication

1. Go to the login page (`USERS/login.php`)
2. Click "Sign in with Google"
3. You should be redirected to Google's sign-in page
4. After signing in, you should be redirected back and logged in

## Troubleshooting

### "Google OAuth is not configured"
- Check that `GOOGLE_CLIENT_ID` is set in `.env` or `config.local.php`
- Verify the file path is correct
- Check file permissions

### "Invalid client" error
- Verify the Client ID is correct
- Check that authorized JavaScript origins include your domain
- Ensure redirect URIs are correctly configured

### Session not persisting
- Check PHP session configuration
- Verify `session_start()` is called before setting session variables
- Check browser cookies are enabled

### Database errors
- Ensure database connection is working
- Check that `users` table exists
- Verify `google_id` column exists or can be created

## Security Notes

1. **Never commit credentials** to version control
2. Use environment variables or secure config files
3. Keep your Client Secret secure (never expose it to frontend)
4. Regularly rotate credentials
5. Use HTTPS in production
6. Configure proper CORS headers if needed

## API Endpoints

- `GET /USERS/api/get-google-config.php` - Returns Client ID (safe for frontend)
- `POST /USERS/api/google-oauth.php` - Handles OAuth verification and user creation/login

## Session Variables Set

After successful authentication, the following session variables are set:
- `user_logged_in` = true
- `user_id` = user database ID
- `user_name` = user's display name
- `user_email` = user's email
- `user_phone` = user's phone (if available)
- `user_type` = 'registered'
- `login_method` = 'google'
- `user_token` = random token for security

