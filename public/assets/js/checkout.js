document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.checkout-form');
    if (!form) {
        return;
    }
    form.addEventListener('submit', function (event) {
        const required = form.querySelectorAll('input[required], textarea[required]');
        let valid = true;
        required.forEach(function (field) {
            if (!field.value.trim()) {
                valid = false;
            }
        });
        if (!valid) {
            event.preventDefault();
            alert('Please complete all required fields.');
        }
    });
});
