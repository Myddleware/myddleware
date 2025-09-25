// console.log('imagemousehoverbutton');


document.addEventListener('DOMContentLoaded', function() {
    const button = document.querySelector('.hover-button');
    const image = document.getElementById('hoverImage');
    let hoverTimeout;

    if (button && image) {
        button.addEventListener('mouseenter', function() {
            hoverTimeout = setTimeout(function() {
                image.style.display = 'block';
            }, 500); // 500 ms delay
        });

        button.addEventListener('mouseleave', function() {
            clearTimeout(hoverTimeout);
            image.style.display = 'none';
        });
    } else {
        // console.warn('hover-button or hoverImage not found in DOM');
    }
});