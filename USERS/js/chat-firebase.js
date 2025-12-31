/**
 * Firebase Chat Integration for User Side
 * Handles real-time chat communication with admin
 */

class UserChatFirebase {
    constructor() {
        this.database = null;
        this.userId = null;
        this.userName = null;
        this.conversationId = null;
        this.messagesRef = null;
        this.isInitialized = false;
    }

    async init() {
        // Wait for Firebase to load
        if (typeof firebase === 'undefined') {
            console.error('Firebase SDK not loaded');
            return false;
        }

        try {
            // Get user info from session
            this.userId = this.getUserId();
            this.userName = this.getUserName();
            
            if (!this.userId) {
                console.warn('User not logged in. Chat will work in guest mode.');
                this.userId = 'guest_' + Date.now();
                this.userName = 'Guest User';
            }

            // Initialize Firebase
            if (!window.firebaseApp) {
                const firebaseConfig = window.firebaseConfig || {
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
            
            // Create or get conversation ID
            this.conversationId = await this.getOrCreateConversation();
            
            // Set up message listener
            this.setupMessageListener();
            
            this.isInitialized = true;
            return true;
        } catch (error) {
            console.error('Firebase Chat initialization error:', error);
            return false;
        }
    }

    getUserId() {
        // Try to get from session or localStorage
        if (typeof sessionStorage !== 'undefined') {
            return sessionStorage.getItem('user_id') || null;
        }
        return null;
    }

    getUserName() {
        if (typeof sessionStorage !== 'undefined') {
            return sessionStorage.getItem('user_name') || 'User';
        }
        return 'User';
    }

    async getOrCreateConversation() {
        const conversationsRef = this.database.ref('conversations');
        const userConversationsRef = conversationsRef.orderByChild('userId').equalTo(this.userId);
        
        return new Promise((resolve) => {
            userConversationsRef.once('value', (snapshot) => {
                if (snapshot.exists()) {
                    const conversations = snapshot.val();
                    const firstKey = Object.keys(conversations)[0];
                    resolve(firstKey);
                } else {
                    // Create new conversation
                    const newConversationRef = conversationsRef.push({
                        userId: this.userId,
                        userName: this.userName,
                        status: 'active',
                        createdAt: firebase.database.ServerValue.TIMESTAMP,
                        updatedAt: firebase.database.ServerValue.TIMESTAMP
                    });
                    resolve(newConversationRef.key);
                }
            });
        });
    }

    setupMessageListener() {
        if (!this.conversationId) return;

        this.messagesRef = this.database.ref(`messages/${this.conversationId}`);
        
        this.messagesRef.on('child_added', (snapshot) => {
            const message = snapshot.val();
            this.onMessageReceived(message);
        });
    }

    onMessageReceived(message) {
        // Dispatch custom event for chat UI to handle
        const event = new CustomEvent('chatMessageReceived', {
            detail: { message }
        });
        window.dispatchEvent(event);
    }

    async sendMessage(text) {
        if (!this.isInitialized || !this.conversationId) {
            console.error('Chat not initialized');
            return false;
        }

        try {
            const messageRef = this.database.ref(`messages/${this.conversationId}`).push({
                text: text,
                senderId: this.userId,
                senderName: this.userName,
                senderType: 'user',
                timestamp: firebase.database.ServerValue.TIMESTAMP,
                read: false
            });

            // Update conversation
            this.database.ref(`conversations/${this.conversationId}`).update({
                lastMessage: text,
                lastMessageTime: firebase.database.ServerValue.TIMESTAMP,
                updatedAt: firebase.database.ServerValue.TIMESTAMP,
                status: 'active'
            });

            // Notify admin of new message
            this.database.ref('chat_queue').push({
                conversationId: this.conversationId,
                userId: this.userId,
                userName: this.userName,
                message: text,
                timestamp: firebase.database.ServerValue.TIMESTAMP,
                status: 'pending'
            });

            return true;
        } catch (error) {
            console.error('Error sending message:', error);
            return false;
        }
    }

    disconnect() {
        if (this.messagesRef) {
            this.messagesRef.off();
        }
    }
}

// Initialize global instance
window.userChatFirebase = new UserChatFirebase();


