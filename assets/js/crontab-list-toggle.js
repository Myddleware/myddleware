document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('toggle-crontab-result');
    const container = document.getElementById('cronjobresult_table');

    if (toggleBtn && container) {
        toggleBtn.addEventListener('click', function () {
            const showLabel = toggleBtn.dataset.showLabel || 'Voir les résultats';
            const hideLabel = toggleBtn.dataset.hideLabel || 'Cacher les résultats';
            const isVisible = container.classList.contains('show');

            if (!isVisible) {
                if (container.innerHTML.trim() === '') {
                    container.innerHTML = 'Chargement...';

                    fetch(toggleBtn.dataset.url)
                        .then(response => response.text())
                        .then(html => {
                            // Étape 1 : créer un wrapper temporaire
                            const tempWrapper = document.createElement('div');
                            tempWrapper.innerHTML = html;

                            // Étape 2 : remplacer le contenu réel
                            container.innerHTML = '';
                            container.appendChild(tempWrapper);

                            // Étape 3 : laisser le navigateur respirer
                            requestAnimationFrame(() => {
                                container.classList.add('show');
                                toggleBtn.innerText = hideLabel;
                            });
                        })
                        .catch(err => {
                            container.innerHTML = 'Erreur de chargement.';
                            console.error(err);
                        });

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
});
