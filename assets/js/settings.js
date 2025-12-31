/*********************************************************************************
 * This file is part of Myddleware.
 *
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stephane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stephane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com
 *
 * This file is part of Myddleware.
 *
 * Myddleware is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Myddleware is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
 *********************************************************************************/

import axios from 'axios';

class SettingsManager {
    constructor() {
        this.baseUrl = this.getBaseUrl();
        this.apiEndpoints = {
            getConfig: `${this.baseUrl}/rule/api/account/config`,
            updateConfig: `${this.baseUrl}/rule/api/account/config/update`,
            getElasticsearch: `${this.baseUrl}/rule/api/settings/elasticsearch`,
            updateElasticsearch: `${this.baseUrl}/rule/api/settings/elasticsearch/update`
        };

        this.init();
    }

    getBaseUrl() {
        if (window.location.pathname.includes('/public/')) {
            return window.location.pathname.split('/public/')[0] + '/public';
        } else if (window.location.pathname.includes('/index.php/')) {
            return '/index.php';
        }
        return '';
    }

    async init() {
        await this.loadSettings();
        this.setupEventListeners();
        this.setupSmtpFormToggle();
    }

    async loadSettings() {
        try {
            // Load table settings
            const configResponse = await axios.get(this.apiEndpoints.getConfig);
            if (configResponse.data.success) {
                const config = configResponse.data.config;
                const rowsPerPage = document.getElementById('rows-per-page');
                const maximumResults = document.getElementById('maximum-results');

                if (rowsPerPage) {
                    rowsPerPage.value = config.pager || '20';
                }
                if (maximumResults) {
                    maximumResults.value = config.search_limit || '1000';
                }
            }
        } catch (error) {
            console.error('Failed to load config:', error);
        }

        try {
            // Load Elasticsearch status
            const esResponse = await axios.get(this.apiEndpoints.getElasticsearch);
            if (esResponse.data.success) {
                const esEnabled = document.getElementById('elasticsearch-enabled');
                if (esEnabled) {
                    esEnabled.checked = esResponse.data.enabled;
                }
            }
        } catch (error) {
            console.error('Failed to load Elasticsearch status:', error);
        }
    }

    setupEventListeners() {
        // Table settings form submission
        const tableSettingsForm = document.getElementById('table-settings-form');
        if (tableSettingsForm) {
            tableSettingsForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveTableSettings();
            });
        }

        // Elasticsearch toggle
        const esToggle = document.getElementById('elasticsearch-enabled');
        if (esToggle) {
            esToggle.addEventListener('change', () => {
                this.toggleElasticsearch(esToggle.checked);
            });
        }
    }

    async saveTableSettings() {
        const rowsPerPage = document.getElementById('rows-per-page').value;
        const maximumResults = document.getElementById('maximum-results').value;

        try {
            const response = await axios.post(this.apiEndpoints.updateConfig, {
                rowsPerPage: rowsPerPage,
                maximumResults: maximumResults
            });

            if (response.data.success) {
                this.showNotification('success', response.data.message || 'Settings saved successfully');
            } else {
                this.showNotification('danger', response.data.error || 'Failed to save settings');
            }
        } catch (error) {
            console.error('Failed to save table settings:', error);
            this.showNotification('danger', error.response?.data?.error || 'Failed to save settings');
        }
    }

    async toggleElasticsearch(enabled) {
        try {
            const response = await axios.post(this.apiEndpoints.updateElasticsearch, {
                enabled: enabled
            });

            if (response.data.success) {
                this.showNotification('success', response.data.message);
            } else {
                this.showNotification('danger', response.data.error || 'Failed to update Elasticsearch setting');
                // Revert toggle on error
                const esToggle = document.getElementById('elasticsearch-enabled');
                if (esToggle) {
                    esToggle.checked = !enabled;
                }
            }
        } catch (error) {
            console.error('Failed to toggle Elasticsearch:', error);
            this.showNotification('danger', error.response?.data?.error || 'Failed to update Elasticsearch setting');
            // Revert toggle on error
            const esToggle = document.getElementById('elasticsearch-enabled');
            if (esToggle) {
                esToggle.checked = !enabled;
            }
        }
    }

    showNotification(type, message) {
        // Map type to mdw-alert variant
        const variantMap = {
            'success': 'success',
            'danger': 'danger',
            'error': 'danger',
            'warning': 'warning',
            'info': 'info'
        };
        const variant = variantMap[type] || 'info';

        // Icon map
        const iconMap = {
            'success': '✔',
            'danger': '✖',
            'warning': '!',
            'info': 'ℹ'
        };
        const icon = iconMap[variant] || 'ℹ';

        // Create mdw-alert notification element
        const notification = document.createElement('div');
        notification.className = `mdw-alert mdw-alert--${variant} m-4`;
        notification.setAttribute('role', 'alert');
        notification.setAttribute('aria-live', 'polite');
        notification.innerHTML = `
            <span class="mdw-alert__bar"></span>
            <span class="mdw-alert__icon" aria-hidden="true">${icon}</span>
            <div class="mdw-alert__content">${message}</div>
            <div>
                <button type="button" class="mdw-alert__close" aria-label="Close">✕</button>
            </div>
        `;

        // Find notification container
        let container = document.querySelector('.container.p-5');
        if (!container) {
            container = document.querySelector('.container');
        }

        if (container) {
            container.insertBefore(notification, container.firstChild);

            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
    }

    // SMTP form field toggle logic (similar to smtp.js)
    setupSmtpFormToggle() {
        const transportSelect = document.getElementById('regle_bundlemanagement_smtp_transport');

        if (!transportSelect) {
            return; // SMTP form not present
        }

        // Field IDs for SMTP form
        const apiFields = ['regle_bundlemanagement_smtp_ApiKey'];
        const smtpFields = [
            'regle_bundlemanagement_smtp_port',
            'regle_bundlemanagement_smtp_host',
            'regle_bundlemanagement_smtp_auth_mode',
            'regle_bundlemanagement_smtp_encryption',
            'regle_bundlemanagement_smtp_user',
            'regle_bundlemanagement_smtp_password'
        ];

        // Initial state
        this.updateSmtpFieldVisibility(transportSelect.value, apiFields, smtpFields);

        // On change
        transportSelect.addEventListener('change', () => {
            this.updateSmtpFieldVisibility(transportSelect.value, apiFields, smtpFields);
        });

        // Setup SMTP form AJAX submission
        this.setupSmtpFormAjax();
    }

    setupSmtpFormAjax() {
        const smtpForm = document.querySelector('form[name="regle_bundlemanagement_smtp"]');
        if (!smtpForm) {
            return;
        }

        // Find submit buttons
        const saveButton = document.getElementById('regle_bundlemanagement_smtp_submit');
        const testButton = document.getElementById('regle_bundlemanagement_smtp_submit_test');

        // Intercept save button
        if (saveButton) {
            saveButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleSmtpSave();
            });
        }

        // Intercept test button
        if (testButton) {
            testButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleSmtpTest();
            });
        }
    }

    getSmtpFormData() {
        return {
            transport: document.getElementById('regle_bundlemanagement_smtp_transport')?.value || '',
            host: document.getElementById('regle_bundlemanagement_smtp_host')?.value || '',
            port: document.getElementById('regle_bundlemanagement_smtp_port')?.value || '',
            ApiKey: document.getElementById('regle_bundlemanagement_smtp_ApiKey')?.value || '',
            auth_mode: document.getElementById('regle_bundlemanagement_smtp_auth_mode')?.value || '',
            encryption: document.getElementById('regle_bundlemanagement_smtp_encryption')?.value || '',
            user: document.getElementById('regle_bundlemanagement_smtp_user')?.value || '',
            password: document.getElementById('regle_bundlemanagement_smtp_password')?.value || '',
            sender: document.getElementById('regle_bundlemanagement_smtp_sender')?.value || ''
        };
    }

    async handleSmtpSave() {
        const saveButton = document.getElementById('regle_bundlemanagement_smtp_submit');
        const originalText = saveButton?.textContent;

        try {
            // Show loading state
            if (saveButton) {
                saveButton.disabled = true;
                saveButton.textContent = 'Saving...';
            }

            const data = this.getSmtpFormData();
            const response = await axios.post(`${this.baseUrl}/rule/api/smtp/save`, data);

            if (response.data.success) {
                this.showNotification('success', response.data.message || 'SMTP configuration saved successfully');
            } else {
                this.showNotification('danger', response.data.error || 'Failed to save SMTP configuration');
            }
        } catch (error) {
            console.error('Failed to save SMTP config:', error);
            const errorMessage = error.response?.data?.error || 'Failed to save SMTP configuration';
            this.showNotification('danger', errorMessage);
        } finally {
            // Restore button state
            if (saveButton) {
                saveButton.disabled = false;
                saveButton.textContent = originalText;
            }
        }
    }

    async handleSmtpTest() {
        const testButton = document.getElementById('regle_bundlemanagement_smtp_submit_test');
        const originalText = testButton?.textContent;

        try {
            // Show loading state
            if (testButton) {
                testButton.disabled = true;
                testButton.textContent = 'Sending test email...';
            }

            const data = this.getSmtpFormData();
            const response = await axios.post(`${this.baseUrl}/rule/api/smtp/test`, data);

            if (response.data.success) {
                this.showNotification('success', response.data.message || 'Test email sent successfully');
            } else {
                this.showNotification('danger', response.data.error || 'Failed to send test email');
            }
        } catch (error) {
            console.error('Failed to test SMTP config:', error);
            const errorMessage = error.response?.data?.error || 'Failed to send test email';
            this.showNotification('danger', errorMessage);
        } finally {
            // Restore button state
            if (testButton) {
                testButton.disabled = false;
                testButton.textContent = originalText;
            }
        }
    }

    updateSmtpFieldVisibility(transport, apiFields, smtpFields) {
        if (transport === 'sendinblue') {
            this.showFields(apiFields);
            this.hideFields(smtpFields);
        } else {
            this.showFields(smtpFields);
            this.hideFields(apiFields);
        }
    }

    showFields(fieldIds) {
        fieldIds.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.style.display = '';
                if (element.parentElement) {
                    element.parentElement.style.display = '';
                }
                if (element.parentElement?.parentElement) {
                    element.parentElement.parentElement.style.display = '';
                }
            }
        });
    }

    hideFields(fieldIds) {
        fieldIds.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.style.display = 'none';
                if (element.parentElement) {
                    element.parentElement.style.display = 'none';
                }
                if (element.parentElement?.parentElement) {
                    element.parentElement.parentElement.style.display = 'none';
                }
            }
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new SettingsManager();
});
