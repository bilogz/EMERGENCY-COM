// Theme Toggle JavaScript
class ThemeManager {
    constructor() {
        this.themes = ['system', 'light', 'dark'];
        this.currentTheme = this.getStoredTheme() || 'system';
        this.init();
    }

    init() {
        this.applyTheme(this.currentTheme);
        this.setupEventListeners();
        this.updateActiveButton();
    }

    getStoredTheme() {
        return localStorage.getItem('theme');
    }

    setStoredTheme(theme) {
        localStorage.setItem('theme', theme);
    }

    applyTheme(theme) {
        const root = document.documentElement;
        
        if (theme === 'system') {
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            root.setAttribute('data-theme', systemTheme);
        } else {
            root.setAttribute('data-theme', theme);
        }
        
        this.currentTheme = theme;
        this.setStoredTheme(theme);
        this.updateActiveButton();
    }

    setupEventListeners() {
        // Theme toggle buttons
        const themeButtons = document.querySelectorAll('.theme-toggle-btn');
        themeButtons.forEach(button => {
            // Remove any existing listeners by cloning
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            const freshButton = newButton;
            
            // Ensure button is clickable
            freshButton.style.pointerEvents = 'auto';
            freshButton.style.cursor = 'pointer';
            freshButton.style.touchAction = 'manipulation';
            
            freshButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                const theme = freshButton.getAttribute('data-theme');
                console.log('Theme button clicked:', theme);
                if (theme) {
                    this.applyTheme(theme);
                }
            }, false);
            
            // Also add touch event for mobile
            freshButton.addEventListener('touchend', (e) => {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                const theme = freshButton.getAttribute('data-theme');
                if (theme) {
                    this.applyTheme(theme);
                }
            }, false);
        });

        // Listen for system theme changes
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener('change', () => {
            if (this.currentTheme === 'system') {
                this.applyTheme('system');
            }
        });
    }

    updateActiveButton() {
        const themeButtons = document.querySelectorAll('.theme-toggle-btn');
        themeButtons.forEach(button => {
            const buttonTheme = button.getAttribute('data-theme');
            if (buttonTheme === this.currentTheme) {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }
        });
    }

    getCurrentTheme() {
        return this.currentTheme;
    }
}

// Initialize theme manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.themeManager = new ThemeManager();
});

// Make it globally accessible
window.ThemeManager = ThemeManager;

// Global setTheme function for easy access
window.setTheme = function(theme) {
    if (window.themeManager) {
        window.themeManager.applyTheme(theme);
    } else {
        // Fallback if themeManager not initialized yet
        const root = document.documentElement;
        if (theme === 'system') {
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            root.setAttribute('data-theme', systemTheme);
        } else {
            root.setAttribute('data-theme', theme);
        }
        localStorage.setItem('theme', theme);
    }
};