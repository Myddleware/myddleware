import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    static targets = ['solution', 'credential'];
    static values = {
        infoUrl: String,
        solutionId: Number,
        connectorId: Number
    }

    htmlOutput = null;

    connect() {
        this.htmlOutput = document.createElement('div');

        const params = new URLSearchParams(window.location.search)
        const crudAction = params.get('crudAction');
        if (crudAction === 'edit') {
            this.connectorId = params.get('entityId');
            const response = this.loadEditConnector();
        }
    }

    async onSelect(event) {
        // // Solution ID 
        this.solutionIdValue = event.currentTarget.value;
        const response = await this.load();
    }

    async load() {
        const params = new URLSearchParams({
            solutionId: this.solutionIdValue,
        })

        const response = await fetch(`${this.infoUrlValue}/${this.solutionIdValue.toString()}`)
            .then(response => response.text())
            .then((html) => {
                this.htmlOutput.innerHTML = html;
                this.element.append(this.htmlOutput);
            })
            .catch(function(err) {  
                console.log('Failed to fetch response: ', err);  
            });
    }

    async loadEditConnector() {

        const response = await fetch(`${this.infoUrlValue}/${this.connectorId.toString()}`)
            .then(response => response.text())
            .then((html) => {
                this.htmlOutput.innerHTML = html;
                this.element.append(this.htmlOutput);
            })
            .catch(function(err) {
                console.log('Failed to fetch response: ', err);
            });
    }
}