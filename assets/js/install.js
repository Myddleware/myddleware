const $ = require('jquery');
require('../css/install.css');
require('../app');

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.copy-btn').forEach(button => {
        button.addEventListener('click', () => {
            const command = button.getAttribute('data-command');
            navigator.clipboard.writeText(command).then(() => {
                // Visual feedback
                const originalIcon = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    button.innerHTML = originalIcon;
                }, 1000);
            }).catch(err => {
                console.error('Failed to copy to clipboard:', err);
                // Fallback for older browsers or HTTPS requirement
                const textArea = document.createElement('textarea');
                textArea.value = command;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    const originalIcon = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-check"></i>';
                    setTimeout(() => {
                        button.innerHTML = originalIcon;
                    }, 1000);
                } catch (e) {
                    console.error('Fallback copy failed:', e);
                }
                document.body.removeChild(textArea);
            });
        });
    });
});

