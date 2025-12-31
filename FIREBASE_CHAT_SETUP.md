# Firebase Chat Integration Setup

This document describes the Firebase chat integration between user-side and admin-side.

## Overview

The chat system uses Firebase Realtime Database to enable real-time communication between users and administrators.

## Features Implemented

### User Side
- Real-time chat interface
- Automatic conversation creation
- Message sending to Firebase
- Receiving admin responses in real-time

### Admin Side
- Chat queue for incoming requests
- Modal notifications for new chats
- Sound notifications (customizable)
- Real-time message synchronization
- Accept/respond to chat requests

## Files Created/Modified

### User Side
1. **USERS/js/firebase-config.js** - Firebase configuration
2. **USERS/js/chat-firebase.js** - User chat Firebase integration
3. **USERS/includes/sidebar.php** - Updated chat modal to use Firebase

### Admin Side
1. **ADMIN/js/admin-chat-firebase.js** - Admin chat Firebase integration
2. **ADMIN/sidebar/chat-queue.php** - Chat queue interface with notifications
3. **ADMIN/sidebar/general-settings.php** - Added sound notification customization

## Firebase Database Structure

```
firebase-database/
├── conversations/
│   └── {conversationId}/
│       ├── userId: string
│       ├── userName: string
│       ├── status: "active" | "closed"
│       ├── createdAt: timestamp
│       ├── updatedAt: timestamp
│       ├── lastMessage: string
│       └── assignedTo: string (admin ID)
│
├── messages/
│   └── {conversationId}/
│       └── {messageId}/
│           ├── text: string
│           ├── senderId: string
│           ├── senderName: string
│           ├── senderType: "user" | "admin"
│           ├── timestamp: timestamp
│           └── read: boolean
│
└── chat_queue/
    └── {queueId}/
        ├── conversationId: string
        ├── userId: string
        ├── userName: string
        ├── message: string
        ├── timestamp: timestamp
        └── status: "pending" | "accepted"
```

## Sound Notification Settings

Admins can customize sound notifications in **General Settings**:
- **Enable/Disable**: Toggle sound alerts
- **Sound File**: Choose from Default, Bell, Chime, Notification, Alert
- **Volume**: Adjust volume (0-100%)
- **Test Sound**: Preview the selected sound

Settings are saved to `localStorage` and persist across sessions.

## Usage

### For Users
1. Click the chat button (floating action button)
2. Enter message and send
3. Wait for admin response
4. Messages appear in real-time

### For Admins
1. Go to **Chat Queue** page
2. View incoming chat requests
3. Click on a request to view messages
4. Click "Accept" to take the chat
5. Type and send responses
6. Receive notifications for new chats (modal + sound)

## Notification System

### Modal Notifications
- Appears in top-right corner
- Shows user name and message preview
- Options: Accept or Dismiss
- Auto-closes after 10 seconds

### Sound Notifications
- Plays when new chat arrives
- Customizable sound and volume
- Can be disabled in settings

## Firebase Security Rules

Make sure to set up proper Firebase Realtime Database security rules:

```json
{
  "rules": {
    "conversations": {
      ".read": "auth != null",
      ".write": "auth != null"
    },
    "messages": {
      ".read": "auth != null",
      ".write": "auth != null"
    },
    "chat_queue": {
      ".read": "auth != null",
      ".write": "auth != null"
    }
  }
}
```

## Testing

1. Open user-side page and send a message
2. Check admin chat queue - should see new request
3. Admin receives notification (modal + sound)
4. Admin accepts chat and responds
5. User receives response in real-time

## Troubleshooting

### Firebase not loading
- Check internet connection
- Verify Firebase scripts are loaded
- Check browser console for errors

### Messages not appearing
- Check Firebase database rules
- Verify conversation ID is correct
- Check browser console for errors

### Sound not playing
- Check sound file exists in `sounds/` directory
- Verify volume is not set to 0
- Check browser audio permissions

## Future Enhancements

- [ ] Typing indicators
- [ ] Read receipts
- [ ] File attachments
- [ ] Chat history search
- [ ] Multiple admin support
- [ ] Chat transfer between admins
- [ ] Chat analytics


