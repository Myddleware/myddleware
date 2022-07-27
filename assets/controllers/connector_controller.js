import { Controller } from '@hotwired/stimulus';
import * as bootstrap from "bootstrap";

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    static targets = ['connector', 'testing'];
    static values = {
        fields: Array,
        url: String,
        solution: String,
    }

    htmlOutputTestInfos = null;

    connect() {
        this.htmlOutputTestInfos = document.createElement('div');
    }

    async testConnector() {
        let loginFields = [];
        this.fieldsValue.map((field) => {
            loginFields.push({
                'name' : field['name'],
                'value': document.getElementById('form_'+field['name']).value
            })
        })

        const response = await fetch(this.urlValue, {
            method: 'POST',
            headers: {
                Accept: 'application.json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                solution: this.solutionValue,
                connectorParams: loginFields
            })
        })
            .then(response => {

                let buttons = document.getElementsByTagName("button");
                if (response.status !== 200) {
                    for (let item of buttons) {
                        item.disabled = true;
                    }
                } else {
                    for (let item of buttons) {
                        item.disabled = false;
                    }
                }

                return response.text()}
            )
            .then((html) => {
                this.htmlOutputTestInfos.innerHTML = html;
                this.element.append(this.htmlOutputTestInfos);
            })
            .catch(function(err) {
                console.log('Failed to fetch response: ', err);
            });
    }
}