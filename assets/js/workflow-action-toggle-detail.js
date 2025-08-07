document.addEventListener("DOMContentLoaded", function () {

    const toggleButton = document.querySelector('.minus-workflow-actions');
    const actionsContent = document.getElementById('actions-content');
    const actionsTableBody = document.querySelector('.workflow-actions-collapse-body');
    const icon = toggleButton?.querySelector('i.fa');
    const actionsUrl = actionsTableBody?.dataset.url;

    let actionsLoaded = false;

    const collapseInstance = new bootstrap.Collapse(actionsContent, {
        toggle: false
    });

    if (!toggleButton || !actionsContent || !actionsTableBody || !actionsUrl || !icon) {
        return;
    }

    icon.classList.remove('fa-minus');
    icon.classList.add('fa-plus');
    actionsContent.classList.remove('show'); 

    toggleButton.addEventListener('click', async function () {
        const isOpen = actionsContent.classList.contains('show');

        if (isOpen) {
            collapseInstance.hide();
            icon.classList.remove('fa-minus');
            icon.classList.add('fa-plus');
        } else {
            collapseInstance.show();
            icon.classList.remove('fa-plus');
            icon.classList.add('fa-minus');

            if (!actionsLoaded) {

                try {
                    const response = await fetch(actionsUrl);

                    if (!response.ok) throw new Error("Erreur HTTP");
                    const html = await response.text();
                    actionsTableBody.innerHTML = html;
                    actionsLoaded = true;
                } catch (error) {
                    actionsTableBody.innerHTML = `<tr><td colspan="10">Erreur logs.</td></tr>`;
                }
            }
        }
    });
});
