import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    static targets = ['solution'];

    connect() {
        console.log('hlelo');
        console.log(this.element);
        console.log(element);
    }

    onSelect(event)
    {
        console.log(event);
        console.log(this.element);
    }
}