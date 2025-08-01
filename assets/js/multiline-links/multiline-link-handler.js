console.log('multiline-link-handler.js loaded');

export class MultilineLinkHandler {
    constructor() {
        this.initialized = false;
        this.observer = null;
        this.init();
    }

    init() {
        console.log('🔗 Initializing MultilineLinkHandler');
        
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupHandler());
        } else {
            this.setupHandler();
        }

        // Listen for dynamic content updates
        document.addEventListener('fluxDataUpdated', () => {
            console.log('📢 Received fluxDataUpdated event, re-checking links');
            setTimeout(() => this.checkAllLinks(), 100);
        });
    }

    setupHandler() {
        console.log('🎯 Setting up multiline link handler');
        
        // Initial check for existing links
        this.checkAllLinks();
        
        // Set up mutation observer for dynamically added content
        this.setupMutationObserver();
        
        this.initialized = true;
    }

    setupMutationObserver() {
        if (this.observer) {
            this.observer.disconnect();
        }

        this.observer = new MutationObserver((mutations) => {
            let shouldCheck = false;
            
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // Check if any added nodes contain our target links
                    for (let node of mutation.addedNodes) {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            const hasTargetLinks = node.querySelector && (
                                node.querySelector('.doc-name') ||
                                node.matches && node.matches('.doc-name')
                            );
                            if (hasTargetLinks) {
                                shouldCheck = true;
                                break;
                            }
                        }
                    }
                }
            });

            if (shouldCheck) {
                console.log('🔄 New content detected, checking links');
                setTimeout(() => this.checkAllLinks(), 50);
            }
        });

        // Observe the flux container for changes
        const fluxContainer = document.getElementById('flux-container');
        if (fluxContainer) {
            this.observer.observe(fluxContainer, {
                childList: true,
                subtree: true
            });
        }
    }

    checkAllLinks() {
        // Focus specifically on .doc-name links as they're most likely to be multi-line
        const docNameLinks = document.querySelectorAll('.doc-name');
        
        console.log(`🔍 Checking ${docNameLinks.length} .doc-name links for multi-line behavior`);
        
        docNameLinks.forEach(link => this.processLink(link));

        // Also check other link types that might be long
        const otherLinks = document.querySelectorAll('.log-reference, .log-job, .doc-id');
        otherLinks.forEach(link => this.processLink(link));
    }

    processLink(link) {
        if (!link || !link.offsetParent) {
            // Link is not visible, skip
            return;
        }

        // Reset any previous multiline classes
        link.classList.remove('multiline-detected', 'single-line-detected');

        // Force a reflow to get accurate measurements
        link.style.display = 'inline';
        
        // Check if the link spans multiple lines
        const isMultiline = this.isLinkMultiline(link);
        
        if (isMultiline) {
            console.log('📏 Multi-line link detected:', link.textContent.substring(0, 30) + '...');
            link.classList.add('multiline-detected');
            this.applyMultilineStyles(link);
        } else {
            link.classList.add('single-line-detected');
            this.applySingleLineStyles(link);
        }
    }

    isLinkMultiline(link) {
        // Create a temporary element to measure single-line height
        const temp = document.createElement('span');
        temp.style.visibility = 'hidden';
        temp.style.position = 'absolute';
        temp.style.whiteSpace = 'nowrap';
        temp.style.font = window.getComputedStyle(link).font;
        temp.textContent = 'A'; // Single character for baseline
        
        document.body.appendChild(temp);
        const singleLineHeight = temp.offsetHeight;
        document.body.removeChild(temp);

        // Get the actual height of the link
        const actualHeight = link.offsetHeight;
        
        // If actual height is significantly more than single line height, it's multiline
        const threshold = singleLineHeight * 1.3; // 30% tolerance
        const isMultiline = actualHeight > threshold;
        
        console.log(`📐 Link height: ${actualHeight}px, single-line: ${singleLineHeight}px, multiline: ${isMultiline}`);
        
        return isMultiline;
    }

    applyMultilineStyles(link) {
        // Remove the ::after pseudo-element by switching to background-based animation
        link.style.setProperty('--use-background-animation', '1');
    }

    applySingleLineStyles(link) {
        // Use the standard ::after pseudo-element animation
        link.style.removeProperty('--use-background-animation');
    }

    // Public method to manually trigger a check (useful for debugging)
    recheckLinks() {
        console.log('🔄 Manual recheck triggered');
        this.checkAllLinks();
    }
}

// Auto-initialize when loaded
let multilineLinkHandler;
document.addEventListener('DOMContentLoaded', () => {
    multilineLinkHandler = new MultilineLinkHandler();
});

// Export for manual access if needed
window.MultilineLinkHandler = MultilineLinkHandler;