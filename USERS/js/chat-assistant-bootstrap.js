/**
 * Chat Assistant Bootstrap
 * Enables AI assistant mode when the floating chat button is used.
 */

(function () {
    'use strict';

    function ensureGuestProfileForAssistant() {
        const existingUserId = sessionStorage.getItem('user_id');
        const isGuest = !existingUserId || String(existingUserId).startsWith('guest_');
        if (!isGuest) {
            return;
        }

        let guestId = localStorage.getItem('guest_user_id');
        if (!guestId) {
            guestId = 'guest_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10);
            localStorage.setItem('guest_user_id', guestId);
        }

        const guestName = sessionStorage.getItem('user_name') || localStorage.getItem('guest_name') || 'Guest User';
        const guestContact = sessionStorage.getItem('user_phone') || localStorage.getItem('guest_contact') || 'N/A';
        const guestLocation = sessionStorage.getItem('user_location') || localStorage.getItem('guest_location') || 'Quezon City';

        sessionStorage.setItem('user_id', guestId);
        sessionStorage.setItem('user_name', guestName);
        sessionStorage.setItem('user_phone', guestContact);
        sessionStorage.setItem('user_location', guestLocation);
        sessionStorage.setItem('user_concern', 'chatbot_assistant');

        localStorage.setItem('guest_user_id', guestId);
        localStorage.setItem('guest_info_provided', 'true');
        localStorage.setItem('guest_name', guestName);
        localStorage.setItem('guest_contact', guestContact);
        localStorage.setItem('guest_location', guestLocation);
        localStorage.setItem('guest_concern', 'chatbot_assistant');
    }

    function enableAssistantMode() {
        window.chatAssistantMode = true;
        window.chatInitialized = false;
        if (typeof window.stopChatPolling === 'function') {
            window.stopChatPolling();
        }
        sessionStorage.removeItem('conversation_id');
        window.currentConversationId = null;
        ensureGuestProfileForAssistant();
    }

    function disableAssistantMode() {
        window.chatAssistantMode = false;
    }

    window.enableChatAssistantMode = window.enableChatAssistantMode || enableAssistantMode;
    window.disableChatAssistantMode = window.disableChatAssistantMode || disableAssistantMode;
    if (typeof window.chatAssistantMode === 'undefined') {
        window.chatAssistantMode = false;
    }

    // Capture phase so assistant mode is prepared before existing chat button handlers run.
    document.addEventListener('click', function (event) {
        const trigger = event.target && event.target.closest ? event.target.closest('#chatFab') : null;
        if (!trigger) {
            return;
        }
        enableAssistantMode();
    }, true);
})();
