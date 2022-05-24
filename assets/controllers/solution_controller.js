import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    static targets = ['solution'];

    connect() {
        console.log(this.element);
    }

    onSelect(event)
    {
        // Solution ID 
        console.log(event.currentTarget.value);
        
        // console.log(this.element);
    }
}