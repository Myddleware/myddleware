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
    connectorSourceIdDiv = document.querySelector('[data-rule-connector-source-id-value]');
    connectorTargetIdDiv = document.querySelector('[data-rule-connector-target-id-value]');
    moduleSourceIdDiv = document.querySelector('[data-rule-source-module-id-value]');
    moduleTargetIdDiv = document.querySelector('[data-rule-target-module-id-value]');

    connect() {
        this.htmlOutput = document.createElement('div');
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
        // this.dispatch('sourcemodule', { sourceModuleId: this.sourceModuleIdValue});
    }

    async onSelectModuleTarget(event) {
        this.targetModuleIdValue = event.currentTarget.value;
        const response = await this.loadTargetFields(this.targetModuleIdValue);
        // this.dispatch('targetmodule', { targetModuleId: this.targetModuleIdValue});
    }

    async loadSourceFields(sourceModuleId) {
        const params = new URLSearchParams({
            sourceModuleId: this.sourceModuleIdValue,
            connectorSourceId: this.connectorSourceIdValue,
        })
        // const connectorSourceIdDiv = document.querySelector('[data-rule-connector-source-id-value]');
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

        // const connectorIdDiv = document.querySelector('[data-rule-connector-target-id-value]');
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

    async addMoreFields(event) {
        // console.log(event.currentTarget.dataset);
        // console.log(event.target);
        // console.log(this.targetModuleIdValue);
        // console.log(this.sourceModuleIdValue);
        // event.stopPropagation();
        event.preventDefault();
        // At the moment, the source modules are first loaded and displayed, but then they are removed from DOM,
        // this is surely due to the fact that the 2nd promise erases the first one
        const sourceModuleId = document.querySelector('[data-rule-source-module-id-value]');
        const promisesResults = [
            this.loadSourceFields(Number(sourceModuleId.getAttribute('data-rule-source-module-id-value'))),
            this.loadTargetFields(this.targetModuleIdValue)
        ];
        await Promise.allSettled(promisesResults).then((results) =>
            console.log('hi')
        );
        //
        // const [sourceFieldsResult, targetFieldsResult] = await Promise.all([
        //     this.loadSourceFields(Number(sourceModuleId.getAttribu te('data-rule-source-module-id-value'))),
        //     this.loadTargetFields(this.targetModuleIdValue)
        // ]);


        // await this.loadSourceFields(Number(sourceModuleId.getAttribute('data-rule-source-module-id-value')))
        //     .then(
        //     (success) =>  this.loadTargetFields(this.targetModuleIdValue),
        //     (err) => {
        //         console.log('zut', err);
        //     }
        // );
        // await this.loadTargetFields(this.targetModuleIdValue);

        // console.log(sourceModuleId);
        // try {
        //     this.dispatch('add', {
        //         targetModuleId: this.targetModuleIdValue,
        //         sourceModuleId: document.querySelector('[data-rule-source-module-id-value]').getAttribute('data-rule-source-module-id-value')
        //     });
        //     // console.log(event.currentTarget.value);
        //     console.log(this.element);
        //     // const form = new FormData(this.element);
        //     // console.log(form);
        //     // @TODO: this currently sends event.currentTarget as null which then stops execution inside PHP controller
        //     // const source = await this.onSelectModuleSource(event);
        //     // const target = await this.onSelectModuleTarget(event);
        //     // const response = await fetch()
        //     //     .then(response => response.text())
        //     //     .then((html) => {
        //     //         // this.htmlOutput.innerHTML = html;
        //     //         // this.element.append(this.htmlOutput);
        //     //         // this.appendButton(this.element);
        //     //         // this.dispatch('success');
        //     //     })
        //     //     .catch(function(err) {
        //     //         console.log('Failed to fetch response: ', err);
        //     //     });
        //     // this.dispatch('success');
        // } catch (e) {
        //     console.log(e.responseText);
        // }




    }
}