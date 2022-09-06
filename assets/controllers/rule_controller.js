import {Controller} from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    static targets = ['rule', 'module', 'field', 'connector', 'more'];
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
        this.moreFieldsSection.classList.add('additional-fields');
        const params = new URLSearchParams(window.location.search)
        useDispatch(this);
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
            console.log(div);
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
                // this.moreFieldsSection.prepend(this.sourceHtmlOutput);
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
                // this.moreFieldsSection.append(this.targetHtmlOutput);
            })
            .catch(function(err) {
                console.log('Failed to fetch response: ', err);
            });
    }

    async addMoreFields(event) {
        // this.dispatch('myevent');
        event.preventDefault();
        const sourceModuleId = document.querySelector('[data-rule-source-module-id-value]');
        const promisesResults = [
             this.loadMoreSourceFields(Number(sourceModuleId.getAttribute('data-rule-source-module-id-value'))),
             this.loadMoreTargetFields(this.targetModuleIdValue)
        ];
        await Promise.allSettled(promisesResults).then((response) =>{
            console.log(response);
            const target = this.hasMoreTarget ? this.moreTarget : this.element;
            console.log(this.sourceHtmlOutput);
            console.log(this.targetHtmlOutput);
            // target.insertAdjacentHTML('afterend', this.sourceHtmlOutput);
            // console.log(target);
            // console.log(this.moreFieldsSection);
            // target.innerHTML  = this.moreFieldsSection;
            // target.appendChild(this.moreFieldsSection);

            // target.parentNode.append(this.sourceHtmlOutput);
            // const newSourceDiv = target.lastChild.after(this.sourceHtmlOutput);
            // newSourceDiv.lastChild.after(this.targetHtmlOutput);

            let p = new Promise((resolve, reject) => {
                setTimeout(() => {
                    resolve(target.lastChild.after(this.sourceHtmlOutput));
                }, 2 * 100);
            });
            p.then((result) => {
                result = target.lastChild
                console.log(result);
                // return result * 2;
            }).then((result) => {
                result = target.lastChild;
                result.lastChild.after(this.targetHtmlOutput)
                console.log(result);

                this.appendButton(result);

                // return result * 3;
            });
            // target.appendChild(this.sourceHtmlOutput);
            // this.element.appendChild(target);  // error : the new child is an ancestor of the parent
            // this.element.append(this.moreFieldsSection);
            // target.after(this.targetHtmlOutput);
            // this.appendButton(this.moreFieldsSection);
            // this.appendButton(target.parentNode);
            // this.appendButton(target);

        // }).then((res) => {
        //     console.log(res);
            // target.after(this.targetHtmlOutput);
            // // this.appendButton(this.moreFieldsSection);
            // this.appendButton(target);
        }).catch(function(err) {
            console.log('Failed to fetch response: ', err);
        });
    }

    // async refreshContent(event) {
    //     const target = this.hasContentTarget ? this.contentTarget : this.element;
    //
    //     target.style.opacity = .5;
    //     const response = await fetch(this.urlValue);
    //     target.innerHTML = await response.text();
    //     target.style.opacity = 1;
    // }
}