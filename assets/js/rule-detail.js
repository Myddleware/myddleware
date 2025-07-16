document.addEventListener('DOMContentLoaded', function () {
    const tabContainer = document.getElementById('myd-rule-tabs');
    const btnLeft = document.querySelector('.myd-tab-scroll-left');
    const btnRight = document.querySelector('.myd-tab-scroll-right');
    const tabs = tabContainer.querySelectorAll('.nav-link');

    function getActiveTabIndex() {
        return Array.from(tabs).findIndex(tab => tab.classList.contains('active'));
    }

    function activateTab(index) {
        if (index >= 0 && index < tabs.length) {
            tabs[index].click(); // simulate Bootstrap tab click
        }
    }

    btnLeft.addEventListener('click', () => {
        const current = getActiveTabIndex();
        if (current > 0) {
            activateTab(current - 1);
        }
    });

    btnRight.addEventListener('click', () => {
        const current = getActiveTabIndex();
        if (current < tabs.length - 1) {
            activateTab(current + 1);
        }
    });
});
