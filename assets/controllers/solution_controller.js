import { Controller } from '@hotwired/stimulus';
import axios from 'axios';
const document = window.document;

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    static targets = ['solution', 'credential'];
    static values = {
        infoUrl: String,
        solutionId: Number
    }

    outputElement = null;
    loginFieldsResponse = null;

    initialize() {
        this.outputElement = document.createElement('div');
        this.outputElement.className = 'login-fields';
        this.outputElement.textContent = 'I will be here';
        this.element.append(this.outputElement);
    }

    connect() {
        this.render();
    }

    async onSelect(event) {
        // // Solution ID 
        this.solutionIdValue = event.currentTarget.value;
        const response = await this.load();
        // console.log(this.solutionIdValue);
        // await axios.get(this.infoUrlValue, {
        //     params: {
        //         solutionId: this.solutionIdValue
        //         // page: this.pageValue,
        //         // offset: this.offsetValue
        //     }
        // })
        //     .then((response) => {
        //         // this.credentialTarget.innerHTML += response.data;
        //         // this.element.parentNode.innerHTML += response.data;
        //         // this.loginFieldsResponse = response.data;
        //         this.load();
        //     });
    }

    render() {
        const loginFieldsContent = this.credentialTarget.value;
        // console.log(this.loginFieldsResponse);
        this.outputElement.innerHTML = this.loginFieldsResponse;
    }

    async load() {
        const params = new URLSearchParams({
            solutionId: this.solutionIdValue,
        })
        const response = await fetch(`${this.infoUrlValue}/${this.solutionIdValue.toString()}`)
            .then(response => response.text())
            // .then(html => this.element.innerHTML = html)
            .then(html => this.element.append(html))
    }
}