document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('user-manager-edit-form-container');
    const content = container.querySelector('.user-manager-edit-form-content');
    const tableContainer = document.getElementById('user-table-container');

    document.querySelectorAll('.edit-user-btn').forEach(button => {
        button.addEventListener('click', async () => {
            const userId = button.dataset.userId;
            const url = userEditUrl.replace('__ID__', userId);

            content.innerHTML = `<div class="text-center text-muted">...</div>`;
            container.classList.remove('hidden');
            container.classList.add('visible');
            tableContainer.style.width = 'calc(100% - 400px)';

            try {
                const response = await fetch(url);
                const html = await response.text();

                if (!response.ok) {
                    content.innerHTML = `<div class="alert alert-danger">${html}</div>`;
                } else {
                    content.innerHTML = `
                        <button class="close-btn" id="close-user-manager-edit-form">&times;</button>
                        ${html}
                    `;
                    closeHandler();
                }
            } catch (error) {
                content.innerHTML = `<div class="alert alert-danger">Erreur</div>`;
                console.error(error);
            }
        });
    });

    const createBtn = document.getElementById('create-user-btn');
    if (createBtn) {
        createBtn.addEventListener('click', async () => {
            const url = createBtn.dataset.url;
            content.innerHTML = `<div class="text-center text-muted">...</div>`;
            container.classList.remove('hidden');
            container.classList.add('visible');
            tableContainer.style.width = 'calc(100% - 400px)';

            try {
                const response = await fetch(url);
                const html = await response.text();

                if (!response.ok) {
                    content.innerHTML = `<div class="alert alert-danger">${html}</div>`;
                } else {
                    content.innerHTML = `
                        <button class="close-btn" id="close-user-manager-edit-form">&times;</button>
                        ${html}`;
                    closeHandler();
                }
            } catch (error) {
                content.innerHTML = `<div class="alert alert-danger">Erreur</div>`;
                console.error(error);
            }
        });
    }

    function closeHandler() {
        const closeBtn = document.getElementById('close-user-manager-edit-form');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                container.classList.remove('visible');
                setTimeout(() => {
                    container.classList.add('hidden');
                    content.innerHTML = '';
                    tableContainer.style.width = '100%';
                }, 400);
            });
        }
    }
});
