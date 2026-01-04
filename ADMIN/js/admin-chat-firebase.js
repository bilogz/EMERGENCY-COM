/**
 * Firebase Chat Integration for Admin Side
 * Handles real-time chat communication, queue management, and notifications
 */

class AdminChatFirebase {
    constructor() {
        this.database = null;
        this.adminId = null;
        this.chatQueueRef = null;
        this.conversationsRef = null;
        this.currentConversationId = null;
        this.soundEnabled = true;
        this.soundVolume = 0.5;
        this.soundFile = 'default';
        this.notificationSound = null;
        this.isInitialized = false;
    }

    async init() {
        if (typeof firebase === 'undefined') {
            console.error('Firebase SDK not loaded');
            return false;
        }

        try {
            // Get admin ID from session
            this.adminId = this.getAdminId();
            
            // Initialize Firebase
            if (!window.firebaseApp) {
                const firebaseConfig = {
                    apiKey: "AIzaSyAvfyPTCsBp0dL76VsEVkiIrIsQkko91os",
                    authDomain: "emergencycommunicationsy-eb828.firebaseapp.com",
                    databaseURL: "https://emergencycommunicationsy-eb828-default-rtdb.asia-southeast1.firebasedatabase.app",
                    projectId: "emergencycommunicationsy-eb828",
                    storageBucket: "emergencycommunicationsy-eb828.firebasestorage.app",
                    messagingSenderId: "201064241540",
                    appId: "1:201064241540:web:4f6d026cd355404ec365d1",
                    measurementId: "G-ESQ63CMP9B"
                };
                window.firebaseApp = firebase.initializeApp(firebaseConfig);
            }

            this.database = firebase.database();
            
            // Load notification settings
            this.loadNotificationSettings();
            
            // Set up chat queue listener
            this.setupChatQueueListener();
            
            // Set up conversations listener
            this.setupConversationsListener();
            
            this.isInitialized = true;
            return true;
        } catch (error) {
            console.error('Admin Chat Firebase initialization error:', error);
            return false;
        }
    }

    getAdminId() {
        // Get from session or generate
        return sessionStorage.getItem('admin_id') || 'admin_' + Date.now();
    }

    loadNotificationSettings() {
        // Load from localStorage
        const settings = JSON.parse(localStorage.getItem('chatNotificationSettings') || '{}');
        this.soundEnabled = settings.soundEnabled !== false;
        this.soundVolume = settings.soundVolume || 0.5;
        this.soundFile = settings.soundFile || 'default';
        
        // Load sound file
        this.loadNotificationSound();
    }

    loadNotificationSound() {
        if (!this.soundEnabled) return;
        
        const soundPath = `sounds/${this.soundFile}.mp3`;
        this.notificationSound = new Audio(soundPath);
        this.notificationSound.volume = this.soundVolume;
        
        // Fallback to default if custom sound fails
        this.notificationSound.addEventListener('error', () => {
            this.notificationSound = new Audio('sounds/default.mp3');
            this.notificationSound.volume = this.soundVolume;
        });
    }

    setupChatQueueListener() {
        // Listen to all queue items, not just pending
        this.chatQueueRef = this.database.ref('chat_queue');
        
        this.chatQueueRef.on('child_added', (snapshot) => {
            const queueItem = snapshot.val();
            if (queueItem.status === 'pending') {
                this.onNewChatInQueue(snapshot.key, queueItem);
            }
        });
        
        // Also listen for updates to existing queue items
        this.chatQueueRef.on('child_changed', (snapshot) => {
            const queueItem = snapshot.val();
            this.updateQueueItem(snapshot.key, queueItem);
        });
    }

    setupConversationsListener() {
        this.conversationsRef = this.database.ref('conversations').orderByChild('updatedAt');
        
        this.conversationsRef.on('child_added', (snapshot) => {
            const conversation = snapshot.val();
            this.onConversationUpdate(snapshot.key, conversation);
        });
        
        this.conversationsRef.on('child_changed', (snapshot) => {
            const conversation = snapshot.val();
            this.onConversationUpdate(snapshot.key, conversation);
        });
    }

    onNewChatInQueue(queueId, queueItem) {
        // Play notification sound
        if (this.soundEnabled && this.notificationSound) {
            this.notificationSound.play().catch(err => {
                console.warn('Could not play notification sound:', err);
            });
        }
        
        // Show modal notification
        this.showChatNotification(queueId, queueItem);
        
        // Update queue UI
        this.updateChatQueue(queueId, queueItem);
    }

    showChatNotification(queueId, queueItem) {
        // Dispatch custom event for UI to handle with full user info
        const event = new CustomEvent('newChatNotification', {
            detail: {
                queueId: queueId,
                conversationId: queueItem.conversationId,
                userId: queueItem.userId,
                userName: queueItem.userName,
                userEmail: queueItem.userEmail || null,
                userPhone: queueItem.userPhone || null,
                userLocation: queueItem.userLocation || null,
                userConcern: queueItem.userConcern || null,
                isGuest: queueItem.isGuest || false,
                message: queueItem.message,
                timestamp: queueItem.timestamp
            }
        });
        window.dispatchEvent(event);
    }

    updateChatQueue(queueId, queueItem) {
        // Dispatch event to update queue UI
        const event = new CustomEvent('chatQueueUpdate', {
            detail: { queueId, queueItem }
        });
        window.dispatchEvent(event);
    }
    
    updateQueueItem(queueId, queueItem) {
        // Update existing queue item in UI
        const event = new CustomEvent('chatQueueUpdate', {
            detail: { queueId, queueItem }
        });
        window.dispatchEvent(event);
    }

    onConversationUpdate(conversationId, conversation) {
        // Listen for new messages in this conversation
        if (conversationId === this.currentConversationId) {
            this.setupMessageListener(conversationId);
        }
    }

    setupMessageListener(conversationId) {
        const messagesRef = this.database.ref(`messages/${conversationId}`);
        
        messagesRef.on('child_added', (snapshot) => {
            const message = snapshot.val();
            if (message.senderType === 'user') {
                this.onNewMessage(message);
            }
        });
    }

    onNewMessage(message) {
        // Play sound if enabled
        if (this.soundEnabled && this.notificationSound) {
            this.notificationSound.play().catch(err => {
                console.warn('Could not play notification sound:', err);
            });
        }
        
        // Dispatch event for UI
        const event = new CustomEvent('newMessageReceived', {
            detail: { message }
        });
        window.dispatchEvent(event);
    }

    async sendMessage(conversationId, text) {
        if (!this.isInitialized || !conversationId) {
            return false;
        }

        try {
            const messageRef = this.database.ref(`messages/${conversationId}`).push({
                text: text,
                senderId: this.adminId,
                senderName: 'Admin',
                senderType: 'admin',
                timestamp: firebase.database.ServerValue.TIMESTAMP,
                read: false
            });

            // Update conversation
            this.database.ref(`conversations/${conversationId}`).update({
                lastMessage: text,
                lastMessageTime: firebase.database.ServerValue.TIMESTAMP,
                updatedAt: firebase.database.ServerValue.TIMESTAMP,
                status: 'active',
                assignedTo: this.adminId
            });

            return true;
        } catch (error) {
            console.error('Error sending message:', error);
            return false;
        }
    }

    async acceptChat(queueId, conversationId) {
        try {
            // Update queue status
            this.database.ref(`chat_queue/${queueId}`).update({
                status: 'accepted',
                acceptedBy: this.adminId,
                acceptedAt: firebase.database.ServerValue.TIMESTAMP
            });

            // Set current conversation
            this.currentConversationId = conversationId;
            this.setupMessageListener(conversationId);

            return true;
        } catch (error) {
            console.error('Error accepting chat:', error);
            return false;
        }
    }

    updateNotificationSettings(settings) {
        this.soundEnabled = settings.soundEnabled !== false;
        this.soundVolume = settings.soundVolume || 0.5;
        this.soundFile = settings.soundFile || 'default';
        
        // Save to localStorage
        localStorage.setItem('chatNotificationSettings', JSON.stringify(settings));
        
        // Reload sound
        this.loadNotificationSound();
    }

    disconnect() {
        if (this.chatQueueRef) {
            this.chatQueueRef.off();
        }
        if (this.conversationsRef) {
            this.conversationsRef.off();
        }
    }
}

// Initialize global instance
window.adminChatFirebase = new AdminChatFirebase();

