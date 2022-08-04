import {Controller} from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    static targets = ['rule', 'module', 'field'];
    static values = {
        infoUrl: String,
        connectorSourceId: Number,
        connectorTargetId: Number,
        sourceModuleId: Number,
        targetModuleId: Number,
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
    // console.log(params.get('connectorSourceId'));
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
         const response = await this.loadSourceFields(this.sourceModuleIdValue);
    }

    async onSelectModuleTarget(event) {
        this.targetModuleIdValue = event.currentTarget.value;
         const response = await this.loadTargetFields(this.targetModuleIdValue);
    }

    async loadSourceFields(sourceModuleId) {
        const params = new URLSearchParams({
            sourceModuleId: this.sourceModuleIdValue,
            connectorSourceId: this.connectorSourceIdValue,
        })
        const connectorIdDiv = document.querySelector('[data-rule-connector-source-id-value]');
        this.connectorSourceIdValue = connectorIdDiv.getAttribute("data-rule-connector-source-id-value");
//         console.log(connectorIdDiv.getAttribute("data-rule-connector-source-id-value"));
//         console.log(params.get('connectorSourceId'));
// console.log(params.keys());
        // TODO TBC : at the moment, connectorSourceId value remains empty (instead of the ID & isn't sent to the controller)
        const response = await fetch(`get-fields/source/${this.connectorSourceIdValue.toString()}/module/${sourceModuleId.toString()}`)
            .then(response => response.text())
            .then((html) => {
                this.htmlOutput.innerHTML = html;
                this.element.append(this.htmlOutput);
            })
            .catch(function(err) {
                console.log('Failed to fetch response: ', err);
            });
    }

    async loadTargetFields(targetModuleId) {
        const params = new URLSearchParams({
            connectorTargetId: this.connectorTargetIdValue,
            targetModuleId: this.targetModuleIdValue,
        })

        const connectorIdDiv = document.querySelector('[data-rule-connector-target-id-value]');
        this.connectorTargetIdValue = connectorIdDiv.getAttribute("data-rule-connector-target-id-value");

        const response = await fetch(`get-fields/target/${this.connectorTargetIdValue.toString()}/module/${targetModuleId.toString()}`)
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