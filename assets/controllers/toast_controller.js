
import { Controller } from '@hotwired/stimulus';
import * as bootstrap from 'bootstrap';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['toast'];

    connect() {
        this.toastTargets.map(function (toastEl) {
            return new bootstrap.Toast(toastEl);
        });

        this.toastTargets.forEach((toast) =>
            this.showThenHideToast(toast)
        );
    }

    showThenHideToast(toast) {
        setTimeout(function () {
            toast.classList.remove('show');
            toast.classList.add('hide');
        }, 3500);
    }


}
