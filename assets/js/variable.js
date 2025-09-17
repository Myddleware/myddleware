   document.addEventListener('DOMContentLoaded', function () {
    const nameInput = document.querySelector('.variable-name-input');
    if (!nameInput) return;
    nameInput.addEventListener('input', function () {
        const invalidChars = /[.,\s]/;
        if (invalidChars.test(this.value)) {
            this.classList.add('is-invalid');
        } else if (this.value.length > 0) {
            this.classList.remove('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });
    nameInput.addEventListener('blur', function () {
        const invalidChars = /[.,\s]/;
        if (invalidChars.test(this.value)) {
            this.classList.add('is-invalid');
        }
    });
});