import { Controller } from '@hotwired/stimulus';
import axios from 'axios';

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
        axios.get(this.infoUrlValue, {
            params: {
                page: this.pageValue,
                offset: this.offsetValue
            }
        })
            .then((response) => {
                this.tricksTarget.innerHTML += response.data;
                this.removeFrameTitle();
                this.appendLoadingButton(event);
            });
    }
}