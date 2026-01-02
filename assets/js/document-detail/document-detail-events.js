import axios from 'axios';
import { getBaseUrl } from './document-detail-url-utils.js';

export class DocumentDetailEvents {
    static getBaseUrl() {
        return getBaseUrl();
    }

    static setupEventListeners() {
        document.addEventListener('click', (e) => {
            if (e.target.id === 'run-same-record') {
                DocumentDetailEvents.handleRunSameRecord();
            }
        });
    }

    static async handleRunSameRecord() {
        try {
            const documentId = DocumentDetailEvents.getDocumentId();
            
            if (!documentId) {
                alert('Document ID not found');
                return;
            }

            const baseUrl = DocumentDetailEvents.getBaseUrl();
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