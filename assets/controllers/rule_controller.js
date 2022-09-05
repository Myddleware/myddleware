import {Controller} from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    static targets = ['rule', 'module', 'field', 'connector'];
    static values = {
        infoUrl: String,
        connectorSourceId: Number,
        connectorTargetId: Number,
        sourceModuleId: Number,
        targetModuleId: Number,
    }

    htmlOutput = null;
    sourceHtmlOutput = null;
    targetHtmlOutput = null;
    moreFieldsSection = null;
    connectorSourceIdDiv = document.querySelector('[data-rule-connector-source-id-value]');
    connectorTargetIdDiv = document.querySelector('[data-rule-connector-target-id-value]');
    moduleSourceIdDiv = document.querySelector('[data-rule-source-module-id-value]');
    moduleTargetIdDiv = document.querySelector('[data-rule-target-module-id-value]');

    connect() {
        this.htmlOutput = document.createElement('div');
        this.sourceHtmlOutput = document.createElement('div');
        this.targetHtmlOutput = document.createElement('div');
        this.moreFieldsSection = document.createElement('section');
        const params = new URLSearchParams(window.location.search)
        useDispatch(this,{ debug: true });
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
        this.connectorSourceIdValue = this.connectorSourceIdDiv.getAttribute('data-rule-connector-source-id-value');
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
        this.connectorTargetIdValue = this.connectorTargetIdDiv.getAttribute('data-rule-connector-target-id-value');
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
    // the row to provide the user with the opportunity to add more pairs of source/target fields
    // 2) when clicking this button, a new row (pair of source/target inputs + same button) must be displayed to allow user
    // to add as many pairs as they wish
    // we could even display an arrow between source & target inputs for each pair to clearly signify that this source field is linked to that target field

    appendButton(element) {
        this.deletePreviousButton();
        const buttonDiv = document.createElement('div');
        buttonDiv.classList.add('d-grid');
        buttonDiv.classList.add('add-more');
        const addButton = document.createElement('button');
        addButton.classList.add('mt-5');
        addButton.classList.add('btn');
        addButton.classList.add('btn-warning');
        addButton.classList.add('btn-lg');
        // addButton.setAttribute('formnovalidate', true);
        addButton.setAttribute('data-action', 'click->rule#addMoreFields');
        addButton.innerText = 'Add more fields to map';
        this.deletePreviousButton();
        buttonDiv.appendChild(addButton);
        element.appendChild(buttonDiv);
    }

    // avoids having multiple buttons displayed on page
    deletePreviousButton() {
        const addMoreButtons = document.getElementsByClassName('add-more');
        const buttonsToBeRemoved = [];
        for (let i = 0 ; i < addMoreButtons.length; i++) {
            if (i >= 0) {
                buttonsToBeRemoved[i] = addMoreButtons[i];
            }
        }
        buttonsToBeRemoved.forEach((div) => {
           div.remove();
        });

    }

    async loadMoreSourceFields(sourceModuleId){
        const params = new URLSearchParams({
            sourceModuleId: this.sourceModuleIdValue,
            connectorSourceId: this.connectorSourceIdValue,
        })
        this.connectorSourceIdValue = this.connectorSourceIdDiv.getAttribute('data-rule-connector-source-id-value');
        const response = await fetch(`get-fields/source/${this.connectorSourceIdValue.toString()}/module/${sourceModuleId.toString()}`)
            .then(response => response.text())
            .then((html) => {
                this.sourceHtmlOutput.innerHTML = html;
                this.moreFieldsSection.appendChild(this.sourceHtmlOutput);
                // this.element.appendChild(this.sourceHtmlOutput);
            })
            .catch(function(err) {
                console.log('Failed to fetch response: ', err);
            });
    }

    async loadMoreTargetFields(targetModuleId){
        const params = new URLSearchParams({
            connectorTargetId: this.connectorTargetIdValue,
            targetModuleId: this.targetModuleIdValue,
        })

        this.connectorTargetIdValue = this.connectorTargetIdDiv.getAttribute('data-rule-connector-target-id-value');
        const response = await fetch(`get-fields/target/${this.connectorTargetIdValue.toString()}/module/${targetModuleId.toString()}`)
            .then(response => response.text())
            .then((html) => {
                this.targetHtmlOutput.innerHTML = html;
                this.moreFieldsSection.appendChild(this.targetHtmlOutput);
                // this.element.appendChild(this.targetHtmlOutput);
            })
            .catch(function(err) {
                console.log('Failed to fetch response: ', err);
            });
    }

    async addMoreFields(event) {
        event.preventDefault();

        const sourceModuleId = document.querySelector('[data-rule-source-module-id-value]');
        const promisesResults = [
             this.loadMoreSourceFields(Number(sourceModuleId.getAttribute('data-rule-source-module-id-value'))),
             this.loadMoreTargetFields(this.targetModuleIdValue)
        ];
        await Promise.allSettled(promisesResults).then((results) =>{
            this.element.append(this.moreFieldsSection);
            this.appendButton(this.moreFieldsSection);
        }).catch(function(err) {
            console.log('Failed to fetch response: ', err);
        });
    }
}