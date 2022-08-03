import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    static targets = ['rule', 'module', 'field'];
    static values = {
        infoUrl: String,
        connectorSourceId: Number,
        connectorTargetId: Number,
        sourceModuleId: Number,
        targetModuleId: Number
    }

    htmlOutput = null;

    connect() {
        this.htmlOutput = document.createElement('div');
        const params = new URLSearchParams(window.location.search)
    }

    async onSelectSource(event) {
        this.connectorSourceIdValue = event.currentTarget.value;
        const response = await this.loadSource();
    }
    async onSelectTarget(event) {
        this.connectorTargetIdValue = event.currentTarget.value;
        const response = await this.loadTarget();
    }

    async loadSource() {
        const params = new URLSearchParams({
            connectorSourceId: this.connectorSourceIdValue,
        })

        const response = await fetch(`get-modules/source/${this.connectorSourceIdValue.toString()}`)
            .then(response => response.text())
            .then((html) => {
                this.htmlOutput.innerHTML = html;
                this.element.append(this.htmlOutput);
            })
            .catch(function(err) {
                console.log('Failed to fetch response: ', err);
        });
    }

    async loadTarget() {
        const params = new URLSearchParams({
            connectorTargetId: this.connectorTargetIdValue,
        })

        const response = await fetch(`get-modules/target/${this.connectorTargetIdValue.toString()}`)
            .then(response => response.text())
            .then((html) => {
                this.htmlOutput.innerHTML = html;
                this.element.append(this.htmlOutput);
            })
            .catch(function(err) {
                console.log('Failed to fetch response: ', err);
        });
    }

    async onSelectModuleSource(event) {
        this.sourceModuleIdValue = event.currentTarget.value;
        console.log(event.currentTarget.value);
        console.log(this.sourceModuleIdValue);
         const response = await this.loadSourceFields();
         console.log(response);
    }

    async onSelectModuleTarget(event) {
        this.targetModuleIdValue = event.currentTarget.value;
         const response = await this.loadTargetFields();
    }

    async loadSourceFields() {
        const params = new URLSearchParams({
            sourceModuleId: this.sourceModuleIdValue,
            connectorSourceId: this.connectorSourceIdValue,
        })
    console.log(params);

        // TODO TBC : at the moment, connectorSourceId value remains empty (instead of the ID & isn't sent to the controller)
        const response = await fetch(`get-fields/source/${this.connectorSourceIdValue.toString()}`)
            .then(response => response.text())
            .then((html) => {
                console.log(response.text())
                this.htmlOutput.innerHTML = html;
                this.element.append(this.htmlOutput);
            })
            .catch(function(err) {
                console.log('Failed to fetch response: ', err);
            });
    }

    async loadTargetFields() {
        const params = new URLSearchParams({
            connectorTargetId: this.connectorTargetIdValue,
            targetModuleId: this.targetModuleIdValue,
        })

        const response = await fetch(`get-fields/target/${this.connectorTargetIdValue.toString()}`)
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