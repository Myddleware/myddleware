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
        const response = await fetch(`get-fields/source/${this.connectorSourceIdValue.toString()}/module/${sourceModuleId.toString()}`)
            .then(response => response.text())
            .then((html) => {
                this.htmlOutput.innerHTML = html;
                this.element.append(this.htmlOutput);
                this.appendButton(this.element);
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
                this.appendButton(this.element);
            })
            .catch(function(err) {
                console.log('Failed to fetch response: ', err);
            });
    }

    // TODO: 1) each time a pair of source/target fields are chosen, a '+' button must also be displayed at the end of
    // the row to provide the user with the opportunity to add morer pairs of source/target fields
    // 2) when clicking this button, a new row (pair of source/target inputs + same button) must be displayed to allow user
    // to add as many pairs as they wish
    // we could even display an arrow between source & target inputs for each pair to clearly signify that this source field is linked to that target field

    appendButton(element) {
        this.deletePreviousButton();
        const addButton = document.createElement('button');
        addButton.classList.add('ms-3');
        addButton.classList.add('btn');
        addButton.classList.add('btn-warning');
        addButton.classList.add('add-more');
        addButton.innerText = '+';
        element.appendChild(addButton);
    }

    // avoids having multiple buttons displayed on page
    deletePreviousButton() {
        const addMoreButtons = document.getElementsByClassName('add-more');
        const buttonsToBeRemoved = [];
        for (let i = 0 ; i < addMoreButtons.length; i++) {
            if (i > 0) {
                buttonsToBeRemoved[i] = addMoreButtons[i];
            }
        }
        buttonsToBeRemoved.forEach((btn) => {
           btn.remove();
        });

    }

    addMoreFields(event) {
        event.preventDefault();
        console.log(event);
    }
}