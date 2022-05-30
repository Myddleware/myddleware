import { Controller } from '@hotwired/stimulus';
import axios from 'axios';

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    static targets = ['solution', 'credential'];
    static values = {
        infoUrl: String
    }

    connect() {
        console.log(this.element);
    }

    async onSelect(event)
    {
        // event.preventDefault();
        // Solution ID 
        console.log(event.currentTarget.value);
        await axios.get(this.infoUrlValue, {
            params: {
                // page: this.pageValue,
                // offset: this.offsetValue
            }
        })
            .then((response) => {
                // console.log(response.data);
                // console.log(this.credentialTarget);
                // this.credentialTarget.innerHTML += response.data;
                this.element.parentNode.innerHTML += response.data;
                console.log(this.element);
            });
    }
}