document.addEventListener('DOMContentLoaded', function () { 
    const tabContainer = document.getElementById('myd-rule-tabs');
    if (!tabContainer) return;

    const btnLeft = document.querySelector('.myd-tab-scroll-left');
    const btnRight = document.querySelector('.myd-tab-scroll-right');
    const tabs = tabContainer.querySelectorAll('.nav-link');

    function getActiveTabIndex() {
        return Array.from(tabs).findIndex(tab => tab.classList.contains('active'));
    }

    function activateTab(index) {
        if (index >= 0 && index < tabs.length) {
            tabs[index].click();
        }
    }

    if (btnLeft) {
        btnLeft.addEventListener('click', () => {
            const current = getActiveTabIndex();
            if (current > 0) {
                activateTab(current - 1);
            }
        });
    }

    if (btnRight) {
        btnRight.addEventListener('click', () => {
            const current = getActiveTabIndex();
            if (current < tabs.length - 1) {
                activateTab(current + 1);
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
	const tabWrapper = document.getElementById('myd-rule-tabs');
	const leftArrow = document.querySelector('.myd-tab-scroll-left');
	const rightArrow = document.querySelector('.myd-tab-scroll-right');

	if (leftArrow && rightArrow && tabWrapper) {
		leftArrow.addEventListener('click', () => {
			tabWrapper.scrollBy({ left: -200, behavior: 'smooth' });
		});

		rightArrow.addEventListener('click', () => {
			tabWrapper.scrollBy({ left: 200, behavior: 'smooth' });
		});
	}
});
