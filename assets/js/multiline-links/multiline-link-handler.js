export class MultilineLinkHandler {
    constructor() {
        this.initialized = false;
        this.observer = null;
        this.init();
    }

    init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupHandler());
        } else {
            this.setupHandler();
        }

        document.addEventListener('fluxDataUpdated', () => {
            setTimeout(() => this.checkAllLinks(), 100);
        });
    }

    setupHandler() {
        this.checkAllLinks();

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
                setTimeout(() => this.checkAllLinks(), 50);
            }
        });

        const fluxContainer = document.getElementById('flux-container');
        if (fluxContainer) {
            this.observer.observe(fluxContainer, {
                childList: true,
                subtree: true
            });
        }
    }

    checkAllLinks() {
        const docNameLinks = document.querySelectorAll('.doc-name');

        docNameLinks.forEach(link => this.processLink(link));

        const otherLinks = document.querySelectorAll('.log-reference, .log-job, .doc-id');
        otherLinks.forEach(link => this.processLink(link));
    }

    processLink(link) {
        if (!link || !link.offsetParent) {
            return;
        }

        link.classList.remove('multiline-detected', 'single-line-detected');

        link.style.display = 'inline';

        const isMultiline = this.isLinkMultiline(link);

        if (isMultiline) {
            link.classList.add('multiline-detected');
            this.applyMultilineStyles(link);
        } else {
            link.classList.add('single-line-detected');
            this.applySingleLineStyles(link);
        }
    }

    isLinkMultiline(link) {
        const temp = document.createElement('span');
        temp.style.visibility = 'hidden';
        temp.style.position = 'absolute';
        temp.style.whiteSpace = 'nowrap';
        temp.style.font = window.getComputedStyle(link).font;
        temp.textContent = 'A';

        document.body.appendChild(temp);
        const singleLineHeight = temp.offsetHeight;
        document.body.removeChild(temp);

        const actualHeight = link.offsetHeight;

        const threshold = singleLineHeight * 1.3;
        const isMultiline = actualHeight > threshold;

        return isMultiline;
    }

    applyMultilineStyles(link) {
        link.style.setProperty('--use-background-animation', '1');
    }

    applySingleLineStyles(link) {
        link.style.removeProperty('--use-background-animation');
    }

    recheckLinks() {
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
