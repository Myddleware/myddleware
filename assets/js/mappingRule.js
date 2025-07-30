document.addEventListener('DOMContentLoaded', function () {
    // open/close comment-box
    window.toggleCommentBox = function (button) {
        const cardBody = button.closest('.mapping-card-body');
        const commentBox = cardBody.querySelector('.comment-box');

        if (commentBox) {
            commentBox.style.display =
                commentBox.style.display === 'block' ? 'none' : 'block';
        }
    };

    // close comment-box
    window.closeCommentBox = function (icon) {
        icon.closest('.comment-box').style.display = 'none';
    };
    
    document.querySelectorAll('.comment-box form').forEach((form) => {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const formData = new FormData(form);
            const actionUrl = form.getAttribute('action');
            const commentText = form
                .querySelector('.comment-textarea')
                .value.trim();
            let commentOutside = form.closest('.comment-box').nextElementSibling;

            if (commentText === '') {
                alert('Le commentaire ne peut pas être vide.');
                return;
            }

            fetch(actionUrl, {
                method: 'POST',
                body: formData,
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(
                            'Erreur lors de la mise à jour du commentaire'
                        );
                    }
                    return response.text();
                })
                .then(() => {
                    if (!commentOutside) {
                        commentOutside = document.createElement('div');
                        commentOutside.className = 'comment-outside';
                        form.closest('.comment-box').after(commentOutside);
                    }
                    commentOutside.textContent = commentText;
                    form.closest('.comment-box').style.display = 'none';
                })
                .catch((error) => {
                    console.error('Erreur:', error);
                    alert(
                        "Une erreur s'est produite lors de la mise à jour du commentaire."
                    );
                });
        });
    });
});
