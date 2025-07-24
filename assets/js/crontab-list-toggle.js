console.log("crontab-list-toggle.js");

// Add toggle switch functionality for crontabs
document.addEventListener('DOMContentLoaded', function() {
    console.log("DOMContentLoaded");
    const toggleSwitches = document.querySelectorAll('.toggle-switch-crontab');
    console.log(toggleSwitches);
    
    toggleSwitches.forEach(function(toggleSwitch) {
        toggleSwitch.addEventListener('change', function() {
            const crontabId = this.dataset.id;
            const enable = this.checked ? 1 : 0;
            console.log("crontabId", crontabId);
            console.log("enable", enable);
            
            // Get the base URL from the data attribute
            const baseUrl = this.dataset.url;
            console.log("baseUrl", baseUrl);
            const url = baseUrl
                .replace('ID_PLACEHOLDER', crontabId)
                .replace('ENABLE_PLACEHOLDER', enable);
            console.log("final url", url);
            
            // Send AJAX request
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin' // Include cookies
            })
            .then(response => {
                console.log("Response status:", response.status);
                console.log("Response headers:", response.headers);
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Network response was not ok');
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log("Response data:", data);
                if (data.success) {
                    // Show success message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    document.querySelector('#jobsceduler_table').insertBefore(alertDiv, document.querySelector('#jobsceduler_table table'));
                    
                    // Remove alert after 3 seconds
                    setTimeout(() => {
                        alertDiv.remove();
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert the switch if there was an error
                this.checked = !this.checked;
                
                // Show error message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertDiv.innerHTML = `
                    ${error.message || 'Failed to update crontab status'}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.querySelector('#jobsceduler_table').insertBefore(alertDiv, document.querySelector('#jobsceduler_table table'));
                
                // Remove alert after 3 seconds
                setTimeout(() => {
                    alertDiv.remove();
                }, 3000);
            });
        });
    });
    const btn = document.getElementById('load-crontab-result');
    if (btn) {
        btn.addEventListener('click', function () {
            const container = document.getElementById('cronjobresult_table');
            container.innerHTML = '...';

            const url = this.dataset.url;

            fetch(url)
                .then(response => response.text())
                .then(html => {
                    container.innerHTML = html;
                })
                .catch(err => {
                    container.innerHTML = 'Error.';
                    console.error(err);
                });
        });
    }

    document.addEventListener('click', function (e) {
        if (e.target && (e.target.id === 'close-crontab-result' || e.target.closest('#close-crontab-result'))) {
            const resultContainer = document.getElementById('crontab-results-container');
            if (resultContainer) {
                resultContainer.innerHTML = '';
            }
        }
    });

});