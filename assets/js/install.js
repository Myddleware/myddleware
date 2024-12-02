const $ = require('jquery');
require('../css/install.css');
require('../app');

document.querySelectorAll('.copy-btn').forEach(button => {
    button.addEventListener('click', () => {
        const command = button.getAttribute('data-command');
        navigator.clipboard.writeText(command).then(() => {
            // Optional: Visual feedback
            const originalIcon = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            setTimeout(() => {
                button.innerHTML = originalIcon;
            }, 1000);
        });
    });
});

