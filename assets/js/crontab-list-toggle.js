document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('toggle-crontab-result');
    const container = document.getElementById('cronjobresult_table');

    function showLoading() {
        container.innerHTML = `
            <div class="d-flex align-items-center justify-content-center p-4 text-muted small">
                <div class="spinner-border me-2" role="status" aria-hidden="true"></div>
                <span>Loading…</span>
            </div>
        `;
    }

    async function loadCrontabPage(url) {
        try {
            showLoading();
            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await res.text();
            const tempWrapper = document.createElement('div');
            tempWrapper.innerHTML = html;

            const fragment = tempWrapper.querySelector('.crontab-results-fragment');
            if (!fragment) {
                console.warn('[crontab] fragment manquant dans la réponse');
                return;
            }

            container.innerHTML = '';
            container.appendChild(fragment);

            container.classList.add('show');

            if (toggleBtn) {
                const hideLabel = toggleBtn.dataset.hideLabel || 'Cacher les résultats';
                toggleBtn.innerText = hideLabel;
            }

        } catch (err) {
            console.error('[crontab] erreur AJAX page:', err);
            container.innerHTML = 'Erreur de chargement.';
        }
    }

    if (toggleBtn && container) {
        toggleBtn.addEventListener('click', function () {
            const showLabel = toggleBtn.dataset.showLabel || 'Voir les résultats';
            const hideLabel = toggleBtn.dataset.hideLabel || 'Cacher les résultats';
            const isVisible = container.classList.contains('show');

            if (!isVisible) {
                if (container.innerHTML.trim() === '') {
                    container.innerHTML = 'Chargement...';
                    loadCrontabPage(toggleBtn.dataset.url);
                } else {
                    container.classList.add('show');
                    toggleBtn.innerText = hideLabel;
                }
            } else {
                container.classList.remove('show');
                toggleBtn.innerText = showLabel;

                setTimeout(() => {
                    container.innerHTML = '';
                }, 400);
            }
        });
    }

    document.addEventListener('click', function (evt) {
        const link = evt.target.closest('.crontab-results-fragment .pagination a');
        if (!link) return;

        evt.preventDefault();

        const url = link.getAttribute('href');
        loadCrontabPage(url);
    });

    document.addEventListener('change', async function (evt) {
        const checkbox = evt.target.closest('.toggle-switch-crontab');
        if (!checkbox) return;

        const id = checkbox.dataset.id;
        const enable = checkbox.checked ? 1 : 0;
        const template = checkbox.dataset.url;

        if (!template || !id) {
            console.warn('toggle-switch-crontab: data-url ou data-id manquant');
            return;
        }

        const url = template
            .replace('ID_PLACEHOLDER', encodeURIComponent(id))
            .replace('ENABLE_PLACEHOLDER', encodeURIComponent(enable));

        checkbox.disabled = true;
        const previous = !checkbox.checked;

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!res.ok) {
                checkbox.checked = previous;
                const txt = await res.text().catch(() => '');
                console.error('HTTP error', res.status, txt);
                alert('Échec de la mise à jour (HTTP ' + res.status + ').');
                return;
            }

            const data = await res.json().catch(() => ({}));
            if (!data.success) {
                checkbox.checked = previous;
                alert(data.message || 'Mise à jour refusée.');
            }
        } catch (err) {
            checkbox.checked = previous;
            console.error(err);
            alert('Erreur réseau.');
        } finally {
            checkbox.disabled = false;
        }
    });
});

