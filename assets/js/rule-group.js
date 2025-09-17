document.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll(".toggle-button").forEach((button, index) => {
        const targetSelector = button.getAttribute("data-target");
        const target = document.querySelector(targetSelector);

        if (!target) return;
        button.addEventListener("click", function (e) {
            e.preventDefault();
            new bootstrap.Collapse(target, { toggle: true });
        });

        ["shown.bs.collapse", "hidden.bs.collapse"].forEach(eventName => {
            target.addEventListener(eventName, function (e) {
                if (e.target !== target) return;
                updateIcon(button, target);
            });
        });
    });

    function updateIcon(button, target) {
        const isOpen = target.classList.contains("show");
        button.innerHTML = isOpen
            ? '<i class="fa fa-minus"></i>'
            : '<i class="fa fa-plus"></i>';
    }
});
