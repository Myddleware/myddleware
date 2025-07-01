import axios from 'axios';

export class FluxEvents {
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

            const response = await axios.get(`/flux/rerun/${documentId}`);
            
            if (response.status === 200) {
                window.location.href = `/flux/info/${documentId}`;
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