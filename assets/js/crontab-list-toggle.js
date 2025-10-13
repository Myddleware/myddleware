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

