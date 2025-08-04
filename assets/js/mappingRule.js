document.addEventListener('DOMContentLoaded', function () {
    // Function to toggle the comment box visibility
    window.toggleCommentBox = function(button) {
        const commentBox = button.nextElementSibling;
        commentBox.style.display = commentBox.style.display === "block" ? "none" : "block";
    };

    // Function to hide the comment box
    window.closeCommentBox = function(icon) {
        icon.parentElement.style.display = "none";
    };

    document.querySelectorAll('.comment-box form').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission

            const formData = new FormData(form);
            const actionUrl = form.getAttribute('action');
            const commentText = form.querySelector('.comment-textarea').value.trim();
            let commentOutside = form.closest('.comment-box').nextElementSibling;

            if (commentText === "") {
                alert("Le commentaire ne peut pas être vide."); // Alert if comment is empty
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
});
