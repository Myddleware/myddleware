document.addEventListener('DOMContentLoaded', function () {
    window.toggleCommentBox = function (button) {
        const commentBox = button.nextElementSibling;
        commentBox.style.display = commentBox.style.display === "block" ? "none" : "block";
    };

    window.closeCommentBox = function (icon) {
        icon.parentElement.style.display = "none";
    };

    document.querySelectorAll('.comment-box form').forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const formData = new FormData(form);
            const actionUrl = form.getAttribute('action');
            const commentText = form.querySelector('.comment-textarea').value.trim();
            let commentOutside = form.closest('.comment-box').nextElementSibling;

            if (commentText === "") {
                alert("Le commentaire ne peut pas être vide.");
                return;
            }

            fetch(actionUrl, {
                method: 'POST',
                body: formData,
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur lors de la mise à jour du commentaire');
                    }
                    return response.text();
                })
                .then(data => {
                    if (!commentOutside) {
                        commentOutside = document.createElement('div');
                        commentOutside.className = 'comment-outside';
                        form.closest('.comment-box').after(commentOutside);
                    }
                    commentOutside.textContent = commentText;
                    form.closest('.comment-box').style.display = 'none';
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert("Une erreur s'est produite lors de la mise à jour du commentaire.");
                });
        });
    });

    const toggle = document.getElementById('info-toggle');
    let isFixed = false;
    let hoverTimeout;

    function expand() {
        toggle.classList.add('expanded');
    }

    function collapse() {
        if (!isFixed) toggle.classList.remove('expanded');
    }

    toggle.addEventListener('mouseenter', () => {
        clearTimeout(hoverTimeout);
        expand();
    });

    toggle.addEventListener('mouseleave', () => {
        if (!isFixed) {
        hoverTimeout = setTimeout(collapse, 200);
        }
    });

    toggle.addEventListener('click', () => {
        isFixed = !isFixed;
        if (isFixed) {
        expand();
        } else {
        collapse();
        }
    });

    window.toggleFormula = function (button) {
        const preview = button.parentElement.querySelector('.formula-preview');
        const full = button.parentElement.querySelector('.formula-full');

        const isExpanded = !full.classList.contains('d-none');

        if (isExpanded) {
            full.classList.add('d-none');
            preview.classList.remove('d-none');
            button.innerHTML = '<i class="fa fa-plus" aria-hidden="true"></i>';
        } else {
            full.classList.remove('d-none');
            preview.classList.add('d-none');
            button.innerHTML = '<i class="fa fa-minus"></i>';
        }
    };
});
