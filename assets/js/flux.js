import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

export default class extends Controller {
    static targets = ['container'];
    static values = {
        fluxId: String
    }

    connect() {
        this.loadFluxData();
    }

    async loadFluxData() {
        try {
            const response = await fetch(`/api/flux/info${this.fluxIdValue ? '/' + this.fluxIdValue : ''}`);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            
            const data = await response.json();
            this.renderFluxContent(data);
        } catch (error) {
            console.error('Error loading flux data:', error);
            this.showError('Failed to load flux data');
        }
    }

    renderFluxContent(data) {
        const { translations, fluxData, currentLocale } = data;
        
        // Create the main content structure
        const content = document.createElement('div');
        content.className = 'flux-content';
        
        // Add title
        const title = document.createElement('h2');
        title.textContent = translations.flux.title;
        content.appendChild(title);

        // Add sections
        const sections = document.createElement('div');
        sections.className = 'flux-sections';
        
        // General section
        const generalSection = this.createSection(
            translations.flux.sections.general,
            this.createGeneralContent(fluxData, translations)
        );
        sections.appendChild(generalSection);

        // Logs section
        const logsSection = this.createSection(
            translations.flux.sections.logs,
            this.createLogsContent(translations)
        );
        sections.appendChild(logsSection);

        // Mapping section
        const mappingSection = this.createSection(
            translations.flux.sections.mapping,
            this.createMappingContent(translations)
        );
        sections.appendChild(mappingSection);

        content.appendChild(sections);
        
        // Replace loading spinner with content
        const container = this.containerTarget;
        container.innerHTML = '';
        container.appendChild(content);
    }

    createSection(title, content) {
        const section = document.createElement('div');
        section.className = 'flux-section';
        
        const sectionTitle = document.createElement('h3');
        sectionTitle.textContent = title;
        section.appendChild(sectionTitle);
        
        section.appendChild(content);
        return section;
    }

    createGeneralContent(fluxData, translations) {
        const content = document.createElement('div');
        content.className = 'general-content';
        
        if (fluxData) {
            // Create form for editing flux data
            const form = document.createElement('form');
            form.className = 'flux-form';
            
            // Add form fields
            const fields = ['name', 'source', 'target', 'status'];
            fields.forEach(field => {
                const fieldGroup = document.createElement('div');
                fieldGroup.className = 'form-group';
                
                const label = document.createElement('label');
                label.textContent = translations.flux.fields[field];
                
                const input = document.createElement('input');
                input.type = 'text';
                input.name = field;
                input.value = fluxData[field] || '';
                input.className = 'form-control';
                
                fieldGroup.appendChild(label);
                fieldGroup.appendChild(input);
                form.appendChild(fieldGroup);
            });
            
            // Add save button
            const saveButton = document.createElement('button');
            saveButton.type = 'submit';
            saveButton.className = 'btn btn-primary';
            saveButton.textContent = translations.flux.buttons.save;
            form.appendChild(saveButton);
            
            content.appendChild(form);
        } else {
            // Show message for no flux selected
            const message = document.createElement('p');
            message.textContent = 'Please select a flux to view its details';
            content.appendChild(message);
        }
        
        return content;
    }

    createLogsContent(translations) {
        const content = document.createElement('div');
        content.className = 'logs-content';
        
        // Add log controls
        const controls = document.createElement('div');
        controls.className = 'log-controls';
        
        const downloadButton = document.createElement('button');
        downloadButton.className = 'btn btn-secondary';
        downloadButton.textContent = translations.flux.buttons.download_logs;
        downloadButton.onclick = () => this.downloadLogs();
        
        const emptyButton = document.createElement('button');
        emptyButton.className = 'btn btn-danger';
        emptyButton.textContent = translations.flux.buttons.empty_logs;
        emptyButton.onclick = () => this.emptyLogs();
        
        controls.appendChild(downloadButton);
        controls.appendChild(emptyButton);
        content.appendChild(controls);
        
        // Add log display area
        const logDisplay = document.createElement('div');
        logDisplay.className = 'log-display';
        content.appendChild(logDisplay);
        
        return content;
    }

    createMappingContent(translations) {
        const content = document.createElement('div');
        content.className = 'mapping-content';
        
        // Add mapping interface here
        const message = document.createElement('p');
        message.textContent = 'Mapping interface will be implemented here';
        content.appendChild(message);
        
        return content;
    }

    async downloadLogs() {
        try {
            window.location.href = `/api/flux/logs/download${this.fluxIdValue ? '/' + this.fluxIdValue : ''}`;
        } catch (error) {
            console.error('Error downloading logs:', error);
            this.showError('Failed to download logs');
        }
    }

    async emptyLogs() {
        if (!confirm('Are you sure you want to empty the logs? This action cannot be undone.')) {
            return;
        }
        
        try {
            const response = await fetch(`/api/flux/logs/empty${this.fluxIdValue ? '/' + this.fluxIdValue : ''}`, {
                method: 'POST'
            });
            
            if (!response.ok) {
                throw new Error('Failed to empty logs');
            }
            
            // Reload the flux data to refresh the view
            this.loadFluxData();
        } catch (error) {
            console.error('Error emptying logs:', error);
            this.showError('Failed to empty logs');
        }
    }

    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger';
        alert.textContent = message;
        
        const container = this.containerTarget;
        container.insertBefore(alert, container.firstChild);
        
        // Remove the alert after 5 seconds
        setTimeout(() => alert.remove(), 5000);
    }
} 