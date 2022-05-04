import { Controller } from '@hotwired/stimulus';
// import snarkdown from 'snarkdown';
// const document = window.document;
export default class extends Controller {
    // static targets = ['input'];
    static targets = ['solution'];
    // outputElement = null;
    // initialize() {
    //     this.outputElement = document.createElement('div');
    //     this.outputElement.className = 'markdown-preview';
    //     this.outputElement.textContent = 'MARKDOWN WILL BE RENDERED HERE';
    //     this.element.append(this.outputElement);
    // }
    connect() {

        // const solution = document.getElementById('Connector_solution-ts-control');
        // this.element.innerHTML = "You have clicked me 0 times :'(";
        this.count = 0;
        const counterNumberElement = this.element.getElementsByClassName('solution')[0];
        this.element.addEventListener('click', () => {
            this.count++;
            // this.element.innerHTML = this.count;
            counterNumberElement.innerHTML = this.count;
        });
        // this.render();
    }
    // render() {
    //     const markdownContent = this.inputTarget.value;
    //     this.outputElement.innerHTML = snarkdown(markdownContent);
    // }
}