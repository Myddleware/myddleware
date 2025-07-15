document.addEventListener('DOMContentLoaded', function () {
    const tabContainer = document.getElementById('myd-rule-tabs');
    const btnLeft = document.querySelector('.myd-tab-scroll-left');
    const btnRight = document.querySelector('.myd-tab-scroll-right');

    function updateTabArrows() {
        const scrollLeft = tabContainer.scrollLeft;
        const maxScroll = tabContainer.scrollWidth - tabContainer.clientWidth;

        btnLeft.classList.toggle('d-none', scrollLeft <= 0);
        btnRight.classList.toggle('d-none', scrollLeft >= maxScroll - 1);
    }

    btnLeft.addEventListener('click', () => {
        tabContainer.scrollBy({ left: -150, behavior: 'smooth' });
    });

    btnRight.addEventListener('click', () => {
        tabContainer.scrollBy({ left: 150, behavior: 'smooth' });
    });

    tabContainer.addEventListener('scroll', updateTabArrows);
    window.addEventListener('resize', updateTabArrows);
    updateTabArrows();
});