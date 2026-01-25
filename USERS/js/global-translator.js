/**
 * Global Translation System
 * Automatically translates all UI text, including elements without data-translate attributes
 */

class GlobalTranslator {
    constructor() {
        this.translationCache = new Map();
        this.autoTranslateEnabled = true;
        this.excludedSelectors = [
            'script', 'style', 'noscript', '[data-no-translate]', 
            '.no-translate', 'input[type="hidden"]', '.translation-ignore',
            '.sidebar-toggle-btn', 'button[onclick]',
            '.sidebar-link', 'a.sidebar-link', '.sidebar-menu-item a'
        ];
        this.init();
    }
    
    init() {
        // Listen for language changes
        document.addEventListener('languageChanged', () => {
            // Wait a bit for translations to load
            setTimeout(() => this.translateAll(), 200);
        });
        
        // Apply translations when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                // Wait for translations.js to load translations first
                setTimeout(() => this.translateAll(), 1000);
            });
        } else {
            // Wait for translations.js to load translations first
            setTimeout(() => this.translateAll(), 1000);
        }
        
        // Watch for dynamically added content
        this.observeDOM();
    }
    
    /**
     * Observe DOM changes to translate new content
     */
    observeDOM() {
        const observer = new MutationObserver((mutations) => {
            let shouldTranslate = false;
            
            mutations.forEach((mutation) => {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1 && !this.isExcluded(node)) {
                            shouldTranslate = true;
                        }
                    });
                }
            });
            
            if (shouldTranslate) {
                // Debounce translation
                clearTimeout(this.translateTimeout);
                this.translateTimeout = setTimeout(() => {
                    this.translateAll();
                }, 300);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    /**
     * Check if element should be excluded from translation
     */
    isExcluded(element) {
        if (!element || element.nodeType !== 1) return true;
        
        // Check excluded selectors
        for (const selector of this.excludedSelectors) {
            if (element.matches && element.matches(selector)) {
                return true;
            }
            if (element.closest && element.closest(selector)) {
                return true;
            }
        }
        
        // Check for no-translate attribute
        if (element.hasAttribute('data-no-translate') || 
            element.classList.contains('no-translate')) {
            return true;
        }
        
        // Exclude sidebar navigation links (but allow spans with data-translate inside them)
        if (element.classList.contains('sidebar-link') || 
            (element.tagName === 'A' && element.closest('.sidebar-nav'))) {
            // Only exclude if it's not a span with data-translate (those should be translated)
            if (!(element.tagName === 'SPAN' && element.hasAttribute('data-translate'))) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Translate all text content on the page
     */
    async translateAll() {
        const lang = getCurrentLanguage();
        console.log(`🌍 GlobalTranslator: Translating to ${lang}`);
        
        // Get translation object
        let translation = translations[lang];
        
        // If not in static translations, fetch from API
        if (!translation && lang !== 'en') {
            console.log(`🌍 GlobalTranslator: Fetching translations for ${lang} from API...`);
            try {
                const apiPath = getApiPath(`api/get-translations.php?lang=${encodeURIComponent(lang)}`);
                const response = await fetch(apiPath, {
                    cache: 'no-cache',
                    headers: { 'Cache-Control': 'no-cache' }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.translations) {
                        translation = data.translations;
                        translations[lang] = translation;
                        console.log(`✓ GlobalTranslator: Loaded ${Object.keys(translation).length} translations for ${lang}`);
                    }
                }
            } catch (error) {
                console.error('Error fetching translations:', error);
            }
        }
        
        // If still no translation, use English as fallback
        if (!translation) {
            translation = translations.en || {};
            if (lang !== 'en') {
                console.warn(`⚠️ GlobalTranslator: No translation found for ${lang}, using English`);
            }
        }
        
        // Translate elements with data-translate attributes FIRST (existing system)
        console.log(`🌍 GlobalTranslator: Translating ${document.querySelectorAll('[data-translate]').length} elements with data-translate`);
        this.translateWithAttributes(translation);
        
        // Translate button text ONLY (preserve functionality)
        this.translateButtonText(translation);
        
        // Translate buttons, labels, and other common elements
        this.translateCommonElements(translation);
        
        // Translate ALL text nodes (comprehensive translation) - but exclude interactive elements
        this.translateAllTextNodes(translation);
        
        console.log(`✓ GlobalTranslator: Translation complete for ${lang}`);
    }
    
    /**
     * Translate all text nodes in the document
     * This is the comprehensive translation that catches everything
     */
    translateAllTextNodes(translation) {
        if (!translation) return;
        
        // Create a comprehensive map of all known text patterns
        const textMap = {
            // Sidebar
            'User': 'sidebar.user',
            'Emergency': 'sidebar.emergency',
            'Home': 'nav.home',
            'Alerts': 'nav.alerts',
            'Support': 'nav.support',
            'Emergency Call': 'nav.emergency',
            'Edit Information': 'sidebar.editInfo',
            'Log Out': 'sidebar.logOut',
            'Login / Sign Up': 'nav.login',
            
            // Chat
            'Quick Assistance': 'chat.title',
            'Please provide your information to start chatting': 'chat.hint',
            'Full Name': 'chat.fullName',
            'Contact Number': 'chat.contactNumber',
            'Location': 'chat.location',
            'What is your concern?': 'chat.concern',
            'Select a concern...': 'chat.selectConcern',
            'Emergency': 'chat.emergency',
            'Medical Assistance': 'chat.medical',
            'Fire Emergency': 'chat.fire',
            'Police Assistance': 'chat.police',
            'Disaster/Weather': 'chat.disaster',
            'General Inquiry': 'chat.general',
            'Complaint': 'chat.complaint',
            'Other': 'chat.other',
            'Start Chat': 'chat.startChat',
            'Type your message...': 'chat.typeMessage',
            'Send': 'chat.send',
            
            // Login
            'Login': 'login.login',
            'Create Account': 'login.createAccount',
            'OR': 'login.or',
            'Login with Google': 'login.withGoogle',
            'Login with Phone Number (OTP)': 'login.withPhone',
            'Verify Your Email': 'login.verifyEmail',
            'Verify Your Phone': 'login.verifyPhone',
            'Verification Code': 'login.verificationCode',
            'Enter 6-digit code': 'login.enterCode',
            'Enter the 6-digit code sent to your email': 'login.codeHint',
            'Enter the 6-digit code sent to your phone': 'login.codeHintPhone',
            'Verify & Login': 'login.verifyLogin',
            'Resend Code': 'login.resendCode',
            'Back': 'login.back',
            'Send OTP to Email': 'login.sendOtpEmail',
            'We\'ll send you a verification code via email': 'login.emailHint',
            'Search barangay...': 'form.select',
            'Enter your full name': 'form.enterName',
            '09XX XXX XXXX': 'form.enterPhone'
        };
        
        // Translate all text nodes
        const walker = document.createTreeWalker(
            document.body,
            NodeFilter.SHOW_TEXT,
            {
                acceptNode: (node) => {
                    // Skip if parent is excluded
                    if (this.isExcluded(node.parentElement)) {
                        return NodeFilter.FILTER_REJECT;
                    }
                    // Skip if already translated
                    if (node.parentElement && node.parentElement.hasAttribute('data-translated')) {
                        return NodeFilter.FILTER_REJECT;
                    }
                    // Skip empty or whitespace-only nodes
                    if (!node.textContent || !node.textContent.trim()) {
                        return NodeFilter.FILTER_REJECT;
                    }
                    // Skip if parent is a button with onclick or sidebar toggle - don't interfere with button functionality
                    const parent = node.parentElement;
                    if (parent && (parent.classList.contains('sidebar-toggle-btn') || parent.hasAttribute('onclick'))) {
                        return NodeFilter.FILTER_REJECT;
                    }
                    // Skip if inside sidebar navigation links - they use data-translate on child spans
                    if (parent && (parent.closest('.sidebar-nav') || parent.closest('.sidebar-link'))) {
                        // Only allow if it's a span with data-translate (those should be translated)
                        if (!(parent.tagName === 'SPAN' && parent.hasAttribute('data-translate'))) {
                            return NodeFilter.FILTER_REJECT;
                        }
                    }
                    // Don't skip data-translate elements - they need translation too
                    return NodeFilter.FILTER_ACCEPT;
                }
            }
        );
        
        const textNodes = [];
        let node;
        while (node = walker.nextNode()) {
            textNodes.push(node);
        }
        
        textNodes.forEach(textNode => {
            const text = textNode.textContent.trim();
            if (!text) return;
            
            // Check exact matches first
            if (textMap[text] && translation[textMap[text]]) {
                const parent = textNode.parentElement;
                if (parent && !parent.hasAttribute('data-translated')) {
                    parent.setAttribute('data-translated', 'true');
                    if (!parent.hasAttribute('data-original-text')) {
                        parent.setAttribute('data-original-text', text);
                    }
                    textNode.textContent = translation[textMap[text]];
                    return;
                }
            }
            
            // Check partial matches for common patterns
            for (const [key, transKey] of Object.entries(textMap)) {
                if (text.includes(key) && translation[transKey]) {
                    const parent = textNode.parentElement;
                    if (parent && !parent.hasAttribute('data-translated')) {
                        // Only translate if it's a close match (not just substring)
                        if (text === key || text.startsWith(key + ' ') || text.endsWith(' ' + key)) {
                            parent.setAttribute('data-translated', 'true');
                            if (!parent.hasAttribute('data-original-text')) {
                                parent.setAttribute('data-original-text', text);
                            }
                            textNode.textContent = text.replace(key, translation[transKey]);
                            return;
                        }
                    }
                }
            }
        });
        
        // Also translate specific element types (exclude buttons and links with onclick, and sidebar links)
        // But include buttons for comprehensive translation
        ['span', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'li', 'td', 'th', 'button'].forEach(tag => {
            document.querySelectorAll(tag).forEach(element => {
                if (this.isExcluded(element)) return;
                if (element.hasAttribute('data-translate') || element.hasAttribute('data-translated')) return;
                
                // For buttons, skip if it's a sidebar toggle or has onclick (unless it has data-translate)
                if (tag === 'button') {
                    if (element.classList.contains('sidebar-toggle-btn') || 
                        (element.hasAttribute('onclick') && !element.hasAttribute('data-translate'))) {
                        return;
                    }
                }
                
                // Skip if inside a button with onclick or is a sidebar toggle button
                if (element.closest('button[onclick]') || element.closest('.sidebar-toggle-btn')) {
                    return;
                }
                // Skip if inside sidebar navigation links - they use data-translate on child spans
                if (element.closest('.sidebar-nav') || element.closest('.sidebar-link')) {
                    // Only skip if it's not a span with data-translate (those should be translated)
                    if (!(element.tagName === 'SPAN' && element.hasAttribute('data-translate'))) {
                        return;
                    }
                }
                
                const text = element.textContent.trim();
                if (!text || text.length < 2) return;
                
                // For buttons, check if it has .btn-text child first
                if (tag === 'button' && element.querySelector('.btn-text')) {
                    const btnTextElement = element.querySelector('.btn-text');
                    const btnText = btnTextElement.textContent.trim();
                    if (textMap[btnText] && translation[textMap[btnText]]) {
                        btnTextElement.setAttribute('data-translated', 'true');
                        if (!btnTextElement.hasAttribute('data-original-text')) {
                            btnTextElement.setAttribute('data-original-text', btnText);
                        }
                        btnTextElement.textContent = translation[textMap[btnText]];
                    }
                    return;
                }
                
                // Check exact matches
                if (textMap[text] && translation[textMap[text]]) {
                    element.setAttribute('data-translated', 'true');
                    if (!element.hasAttribute('data-original-text')) {
                        element.setAttribute('data-original-text', text);
                    }
                    element.textContent = translation[textMap[text]];
                }
            });
        });
        
        // Translate links (a tags) but exclude those with onclick handlers, sidebar toggles, or sidebar links
        document.querySelectorAll('a:not([onclick]):not(.sidebar-toggle-btn):not(.sidebar-link)').forEach(element => {
            if (this.isExcluded(element)) return;
            // Skip sidebar navigation links - they use data-translate on child spans
            if (element.closest('.sidebar-nav') || element.classList.contains('sidebar-link')) return;
            if (element.hasAttribute('data-translate') || element.hasAttribute('data-translated')) return;
            
            const text = element.textContent.trim();
            if (!text || text.length < 2) return;
            
            // Check exact matches
            if (textMap[text] && translation[textMap[text]]) {
                element.setAttribute('data-translated', 'true');
                if (!element.hasAttribute('data-original-text')) {
                    element.setAttribute('data-original-text', text);
                }
                // For links, only translate if they don't have icons/elements as children
                if (element.children.length === 0) {
                    element.textContent = translation[textMap[text]];
                } else {
                    // Has icons - only replace text nodes, preserve children
                    const textNodes = Array.from(element.childNodes).filter(node => node.nodeType === Node.TEXT_NODE);
                    textNodes.forEach(node => node.remove());
                    if (element.firstChild) {
                        element.insertBefore(document.createTextNode(translation[textMap[text]]), element.firstChild);
                    }
                }
            }
        });
    }
    
    /**
     * Translate elements with data-translate attributes (existing system)
     */
    translateWithAttributes(translation) {
        if (!translation) {
            console.warn('GlobalTranslator: No translation object available');
            return;
        }
        
        // Text content - translate ALL elements with data-translate
        const elementsToTranslate = document.querySelectorAll('[data-translate]');
        console.log(`Found ${elementsToTranslate.length} elements with data-translate attribute`);
        
        elementsToTranslate.forEach(element => {
            if (this.isExcluded(element)) {
                console.debug(`Element excluded:`, element);
                return;
            }
            
            const key = element.getAttribute('data-translate');
            if (!key) {
                console.debug(`Element has no translation key:`, element);
                return;
            }
            
            if (translation[key]) {
                // Store original if not already stored
                const originalText = element.textContent.trim();
                if (!element.hasAttribute('data-original-text') && originalText) {
                    element.setAttribute('data-original-text', originalText);
                }
                
                // Handle buttons specifically
                if (element.tagName === 'BUTTON' || element.classList.contains('btn') || element.getAttribute('role') === 'button') {
                    // Check if button has .btn-text child element
                    const btnTextElement = element.querySelector('.btn-text');
                    if (btnTextElement) {
                        // Translate the .btn-text element
                        if (!btnTextElement.hasAttribute('data-original-text')) {
                            btnTextElement.setAttribute('data-original-text', btnTextElement.textContent.trim());
                        }
                        btnTextElement.textContent = translation[key];
                        btnTextElement.setAttribute('data-translated', 'true');
                    } else {
                        // Button doesn't have .btn-text, translate button directly
                        const textNodes = Array.from(element.childNodes).filter(node => node.nodeType === Node.TEXT_NODE);
                        if (textNodes.length > 0 && element.children.length === 0) {
                            // Button has only text, safe to replace
                            element.textContent = translation[key];
                        } else if (textNodes.length > 0 && element.children.length > 0) {
                            // Button has icons/elements, only replace text nodes
                            textNodes.forEach(node => node.remove());
                            if (element.firstChild) {
                                element.insertBefore(document.createTextNode(translation[key]), element.firstChild);
                            } else {
                                element.textContent = translation[key];
                            }
                        } else {
                            // No text nodes, append translation
                            element.textContent = translation[key];
                        }
                    }
                    element.setAttribute('data-translated', 'true');
                }
                // For spans (like in sidebar), just replace textContent
                // Spans with data-translate typically only contain text, icons are siblings
                else if (element.tagName === 'SPAN') {
                    // Simple replacement for spans - they usually only have text
                    // Preserve any child elements (like icons) if they exist
                    const hasChildren = element.children.length > 0;
                    if (hasChildren) {
                        // If span has children, only replace text nodes
                        const textNodes = Array.from(element.childNodes).filter(node => node.nodeType === Node.TEXT_NODE);
                        textNodes.forEach(node => node.remove());
                        // Insert translation before any child elements
                        if (element.firstChild) {
                            element.insertBefore(document.createTextNode(translation[key]), element.firstChild);
                        } else {
                            element.textContent = translation[key];
                        }
                    } else {
                        // No children, safe to replace all content
                        element.textContent = translation[key];
                    }
                    // Mark as translated to prevent further interference
                    element.setAttribute('data-translated', 'true');
                } else if (element.children.length === 0) {
                    // Element has no children, safe to replace textContent
                    element.textContent = translation[key];
                    element.setAttribute('data-translated', 'true');
                } else {
                    // Element has children, be more careful
                    // Find text nodes and replace them
                    const textNodes = Array.from(element.childNodes).filter(node => node.nodeType === Node.TEXT_NODE);
                    if (textNodes.length > 0) {
                        // Replace first text node
                        textNodes[0].textContent = translation[key];
                        // Remove other text nodes
                        for (let i = 1; i < textNodes.length; i++) {
                            textNodes[i].remove();
                        }
                    } else {
                        // No text nodes, append translation
                        element.insertBefore(document.createTextNode(translation[key]), element.firstChild);
                    }
                    element.setAttribute('data-translated', 'true');
                }
                
                console.debug(`Translated "${key}": "${originalText}" -> "${translation[key]}"`);
            } else {
                console.warn(`Translation key "${key}" not found in translation object for language ${getCurrentLanguage()}`);
            }
        });
        
        // HTML content
        document.querySelectorAll('[data-translate-html]').forEach(element => {
            if (this.isExcluded(element)) return;
            
            const key = element.getAttribute('data-translate-html');
            if (translation[key]) {
                if (!element.hasAttribute('data-original-html')) {
                    element.setAttribute('data-original-html', element.innerHTML);
                }
                element.innerHTML = translation[key];
            }
        });
        
        // Placeholders
        document.querySelectorAll('[data-translate-placeholder]').forEach(element => {
            if (this.isExcluded(element)) return;
            
            const key = element.getAttribute('data-translate-placeholder');
            if (translation[key]) {
                if (!element.hasAttribute('data-original-placeholder')) {
                    element.setAttribute('data-original-placeholder', element.placeholder);
                }
                element.placeholder = translation[key];
            }
        });
    }
    
    /**
     * Translate button text only (preserve functionality)
     * Now handles ALL button text, including buttons with data-translate and buttons with child elements
     */
    translateButtonText(translation) {
        if (!translation) return;
        
        // Common button texts mapping
        const buttonTexts = {
            'Save': 'common.save',
            'Cancel': 'common.cancel',
            'Close': 'common.close',
            'Submit': 'common.submit',
            'Delete': 'common.delete',
            'Edit': 'common.edit',
            'Update': 'common.update',
            'Add': 'common.add',
            'Remove': 'common.remove',
            'Search': 'common.search',
            'Filter': 'common.filter',
            'Clear': 'common.clear',
            'Apply': 'common.apply',
            'Reset': 'common.reset',
            'Loading...': 'common.loading',
            'Please wait...': 'common.pleaseWait',
            'Error': 'common.error',
            'Success': 'common.success',
            'Warning': 'common.warning',
            'Info': 'common.info',
            'Yes': 'common.yes',
            'No': 'common.no',
            'OK': 'common.ok',
            'Confirm': 'common.confirm',
            'Back': 'common.back',
            'Next': 'common.next',
            'Previous': 'common.previous',
            'Continue': 'common.continue',
            'Finish': 'common.finish',
            'Select': 'common.select',
            'Choose': 'common.choose',
            'View': 'common.view',
            'Details': 'common.details',
            'More': 'common.more',
            'Less': 'common.less',
            'Show': 'common.show',
            'Hide': 'common.hide',
            'Download': 'common.download',
            'Upload': 'common.upload',
            'Send': 'common.send',
            'Receive': 'common.receive',
            'Refresh': 'common.refresh',
            'Reload': 'common.reload'
        };
        
        // Find ALL buttons (including those with data-translate, but exclude sidebar toggle and onclick buttons)
        const allButtons = document.querySelectorAll('button:not(.sidebar-toggle-btn):not([data-no-translate]), .btn:not(.sidebar-toggle-btn):not([data-no-translate]), [role="button"]:not([data-no-translate]), a.btn:not([data-no-translate])');
        
        allButtons.forEach(btn => {
            if (this.isExcluded(btn)) return;
            
            // Skip buttons with onclick handlers (unless they have data-translate)
            if (btn.hasAttribute('onclick') && !btn.hasAttribute('data-translate')) {
                return;
            }
            
            // If button has data-translate, it's already handled by translateWithAttributes
            // But we still need to handle buttons with child elements that have data-translate
            if (btn.hasAttribute('data-translate')) {
                // Already handled, but check for child elements with text that need translation
                const childSpans = btn.querySelectorAll('span[data-translate]');
                childSpans.forEach(span => {
                    const key = span.getAttribute('data-translate');
                    if (translation[key] && !span.hasAttribute('data-translated')) {
                        const originalText = span.textContent.trim();
                        if (!span.hasAttribute('data-original-text') && originalText) {
                            span.setAttribute('data-original-text', originalText);
                        }
                        span.textContent = translation[key];
                        span.setAttribute('data-translated', 'true');
                    }
                });
                return; // Skip further processing for buttons with data-translate
            }
            
            // Handle buttons with child spans that have data-translate
            const childSpanWithTranslate = btn.querySelector('span[data-translate]');
            if (childSpanWithTranslate) {
                const key = childSpanWithTranslate.getAttribute('data-translate');
                if (translation[key] && !childSpanWithTranslate.hasAttribute('data-translated')) {
                    const originalText = childSpanWithTranslate.textContent.trim();
                    if (!childSpanWithTranslate.hasAttribute('data-original-text') && originalText) {
                        childSpanWithTranslate.setAttribute('data-original-text', originalText);
                    }
                    childSpanWithTranslate.textContent = translation[key];
                    childSpanWithTranslate.setAttribute('data-translated', 'true');
                }
                return; // Child span handles translation
            }
            
            // Handle buttons with .btn-text class (common pattern)
            const btnTextElement = btn.querySelector('.btn-text');
            if (btnTextElement && !btnTextElement.hasAttribute('data-translated')) {
                const btnText = btnTextElement.textContent.trim();
                if (buttonTexts[btnText] && translation[buttonTexts[btnText]]) {
                    if (!btnTextElement.hasAttribute('data-original-text')) {
                        btnTextElement.setAttribute('data-original-text', btnText);
                    }
                    btnTextElement.textContent = translation[buttonTexts[btnText]];
                    btnTextElement.setAttribute('data-translated', 'true');
                    return;
                }
            }
            
            // Get all text nodes in the button
            const textNodes = Array.from(btn.childNodes).filter(node => node.nodeType === Node.TEXT_NODE);
            const buttonText = btn.textContent.trim();
            
            // Skip if button is empty or only has whitespace
            if (!buttonText || buttonText.length < 1) return;
            
            // Skip if already translated
            if (btn.hasAttribute('data-translated')) return;
            
            // Try to find translation for the button text
            if (buttonTexts[buttonText] && translation[buttonTexts[buttonText]]) {
                // Store original if not already stored
                if (!btn.hasAttribute('data-original-text')) {
                    btn.setAttribute('data-original-text', buttonText);
                }
                
                // If button has only text nodes (no icons or other elements), replace textContent
                if (textNodes.length > 0 && btn.children.length === 0) {
                    // Button has only text, safe to replace
                    btn.textContent = translation[buttonTexts[buttonText]];
                    btn.setAttribute('data-translated', 'true');
                } else if (textNodes.length > 0 && btn.children.length > 0) {
                    // Button has icons/elements, only replace text nodes
                    textNodes.forEach(node => node.remove());
                    // Insert translated text before first child element
                    if (btn.firstChild) {
                        btn.insertBefore(document.createTextNode(translation[buttonTexts[buttonText]]), btn.firstChild);
                    } else {
                        btn.textContent = translation[buttonTexts[buttonText]];
                    }
                    btn.setAttribute('data-translated', 'true');
                }
            } else {
                // Button text not in predefined list - try to find it in translation object
                // Look for common patterns like "button.{text}" or "{text}.btn"
                const normalizedText = buttonText.toLowerCase().replace(/\s+/g, '');
                for (const [key, value] of Object.entries(translation)) {
                    if (typeof value === 'string') {
                        const normalizedValue = value.toLowerCase().replace(/\s+/g, '');
                        if (normalizedValue === normalizedText && (key.includes('btn') || key.includes('button'))) {
                            // Found a match, translate it
                            if (!btn.hasAttribute('data-original-text')) {
                                btn.setAttribute('data-original-text', buttonText);
                            }
                            if (textNodes.length > 0 && btn.children.length === 0) {
                                btn.textContent = value;
                                btn.setAttribute('data-translated', 'true');
                            } else if (textNodes.length > 0 && btn.children.length > 0) {
                                textNodes.forEach(node => node.remove());
                                if (btn.firstChild) {
                                    btn.insertBefore(document.createTextNode(value), btn.firstChild);
                                } else {
                                    btn.textContent = value;
                                }
                                btn.setAttribute('data-translated', 'true');
                            }
                            break;
                        }
                    }
                }
            }
        });
        
        // Translate labels
        document.querySelectorAll('label').forEach(label => {
            if (this.isExcluded(label)) return;
            
            const text = label.textContent.trim();
            if (buttonTexts[text] && translation[buttonTexts[text]]) {
                if (!label.hasAttribute('data-original-text')) {
                    label.setAttribute('data-original-text', text);
                }
                label.textContent = translation[buttonTexts[text]];
            }
        });
    }
    
    /**
     * Translate common form elements and UI components
     */
    translateCommonElements(translation) {
        if (!translation) return;
        
        // Common form labels and placeholders
        const formTexts = {
            'Full Name': 'form.fullName',
            'Email Address': 'form.email',
            'Mobile Number': 'form.mobileNumber',
            'Phone Number': 'form.phoneNumber',
            'Address': 'form.address',
            'City': 'form.city',
            'Country': 'form.country',
            'Nationality': 'form.nationality',
            'Password': 'form.password',
            'Confirm Password': 'form.confirmPassword',
            'Username': 'form.username',
            'Enter your name': 'form.enterName',
            'Enter your email': 'form.enterEmail',
            'Enter your phone': 'form.enterPhone',
            'Select...': 'form.select',
            'Choose...': 'form.choose',
            'Required': 'form.required',
            'Optional': 'form.optional'
        };
        
        // Translate input placeholders
        document.querySelectorAll('input[placeholder], textarea[placeholder]').forEach(input => {
            if (this.isExcluded(input)) return;
            
            const placeholder = input.placeholder.trim();
            if (formTexts[placeholder] && translation[formTexts[placeholder]]) {
                if (!input.hasAttribute('data-original-placeholder')) {
                    input.setAttribute('data-original-placeholder', placeholder);
                }
                input.placeholder = translation[formTexts[placeholder]];
            }
        });
        
        // Translate option texts
        document.querySelectorAll('option').forEach(option => {
            if (this.isExcluded(option)) return;
            
            const text = option.textContent.trim();
            if (formTexts[text] && translation[formTexts[text]]) {
                if (!option.hasAttribute('data-original-text')) {
                    option.setAttribute('data-original-text', text);
                }
                option.textContent = translation[formTexts[text]];
            }
        });
    }
    
    /**
     * Translate specific text using AI translation API
     */
    async translateText(text, targetLang, sourceLang = 'en') {
        if (!text || !text.trim() || targetLang === sourceLang) {
            return text;
        }
        
        // Check cache
        const cacheKey = `${sourceLang}_${targetLang}_${text}`;
        if (this.translationCache.has(cacheKey)) {
            return this.translationCache.get(cacheKey);
        }
        
        try {
            const apiPath = getApiPath('api/translate-alert-text.php');
            const response = await fetch(apiPath, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    text: text,
                    target_lang: targetLang,
                    source_lang: sourceLang
                })
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success && data.translated_text) {
                    this.translationCache.set(cacheKey, data.translated_text);
                    return data.translated_text;
                }
            }
        } catch (error) {
            console.error('Translation API error:', error);
        }
        
        return text; // Return original if translation fails
    }
    
    /**
     * Translate element text content using AI
     */
    async translateElement(element, targetLang) {
        if (this.isExcluded(element)) return;
        
        const originalText = element.textContent.trim();
        if (!originalText || originalText.length < 2) return;
        
        // Skip if already translated
        if (element.hasAttribute('data-ai-translated')) return;
        
        // Skip common UI elements that should use translation keys
        const skipPatterns = [
            /^\d+$/, // Numbers
            /^[A-Z]{2,}$/, // Acronyms
            /^[a-z]+:\/\/.+/, // URLs
            /^[\w\-\.]+@[\w\-\.]+$/, // Email addresses
            /^\+\d+$/, // Phone numbers
        ];
        
        if (skipPatterns.some(pattern => pattern.test(originalText))) {
            return;
        }
        
        try {
            const translated = await this.translateText(originalText, targetLang);
            if (translated && translated !== originalText) {
                element.setAttribute('data-original-text', originalText);
                element.setAttribute('data-ai-translated', 'true');
                element.textContent = translated;
            }
        } catch (error) {
            console.error('Error translating element:', error);
        }
    }
}

// Initialize global translator when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof window.globalTranslator === 'undefined') {
            window.globalTranslator = new GlobalTranslator();
        }
    });
} else {
    if (typeof window.globalTranslator === 'undefined') {
        window.globalTranslator = new GlobalTranslator();
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = GlobalTranslator;
}

