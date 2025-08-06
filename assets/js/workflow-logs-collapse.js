document.addEventListener("DOMContentLoaded", function () {
    const toggleButton = document.querySelector('.minus-workflow-logs');
    const logsContent = document.getElementById('logs-content');
    const logsTableBody = document.querySelector('.workflow-logs-collapse-body');
    const icon = toggleButton?.querySelector('i.fa');
    const logsUrl = logsTableBody?.dataset.url;

    let logsLoaded = false;
    const collapseInstance = new bootstrap.Collapse(logsContent, {
        toggle: false
    });

    if (!toggleButton || !logsContent || !logsTableBody || !logsUrl || !icon) {
        console.error("Element not found in DOM.");
        return;
    }
 
    // Hide on startup
    icon.classList.remove('fa-minus');
    icon.classList.add('fa-plus');
    logsTableBody.style.display = 'none';

    toggleButton.addEventListener('click', async function () {
        const isOpen = logsContent.classList.contains('show');

        if (isOpen) {
            collapseInstance.hide();
            logsTableBody.style.display = 'none';
            icon.classList.remove('fa-minus');
            icon.classList.add('fa-plus');
        } else {
            collapseInstance.show();
            logsTableBody.style.display = 'table-row-group';
            icon.classList.remove('fa-plus');
            icon.classList.add('fa-minus');

            if (!logsLoaded) {
                try {
                    const response = await fetch(logsUrl);
                    if (!response.ok) throw new Error("Error loading");

                    const html = await response.text();
                    logsTableBody.innerHTML = html;
                    logsLoaded = true;
                } catch (error) {
                    logsTableBody.innerHTML = `<tr><td colspan="11">Error loading logs</td></tr>`;
                    console.error("Erreur :", error);
                }
            }
        }
    });
});
