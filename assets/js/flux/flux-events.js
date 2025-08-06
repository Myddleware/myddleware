import axios from 'axios';

export class FluxEvents {
    static getBaseUrl() {
        return window.location.pathname.includes('/public/') 
            ? window.location.pathname.split('/public/')[0] + '/public'
            : '';
    }

    static setupEventListeners() {
        document.addEventListener('click', (e) => {
            if (e.target.id === 'run-same-record') {
                FluxEvents.handleRunSameRecord();
            }
        });
    }

    static async handleRunSameRecord() {
        try {
            const documentId = FluxEvents.getDocumentId();
            
            if (!documentId) {
                alert('Document ID not found');
                return;
            }

            const baseUrl = FluxEvents.getBaseUrl();
            const response = await axios.get(`${baseUrl}/rule/flux/readrecord/${documentId}`);
            
            if (response.status === 200) {
                window.location.href = `${baseUrl}/rule/flux/modern/${documentId}`;
            }
        } catch (error) {
            console.error('Error running same record:', error);
            alert('Error occurred while rerunning the record');
        }
    }

    static getDocumentId() {
        const urlParts = window.location.pathname.split('/');
        return urlParts[urlParts.length - 1];
    }
}