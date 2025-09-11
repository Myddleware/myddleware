function initTabsNavigation() {
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

	btnLeft?.addEventListener('click', () => {
		const current = getActiveTabIndex();
		if (current > 0) activateTab(current - 1);
	});

	btnRight?.addEventListener('click', () => {
		const current = getActiveTabIndex();
		if (current < tabs.length - 1) activateTab(current + 1);
	});
}

function initTabsScroll() {
	const tabWrapper = document.getElementById('myd-rule-tabs');
	const leftArrow = document.querySelector('.myd-tab-scroll-left');
	const rightArrow = document.querySelector('.myd-tab-scroll-right');

	if (!tabWrapper) return;

	leftArrow?.addEventListener('click', () => {
		tabWrapper.scrollBy({ left: -200, behavior: 'smooth' });
	});
	rightArrow?.addEventListener('click', () => {
		tabWrapper.scrollBy({ left: 200, behavior: 'smooth' });
	});
}

function initDescriptionBlock() {
	const descriptionField = document.getElementById('description-field-value');
	const readMoreLink = document.getElementById('read-more-link');
	const editBtn = document.querySelector('.edit-button-description');
	const closeBtn = document.querySelector('.close-button-description');
	const form = document.querySelector('.edit-form');

	if (!descriptionField || !readMoreLink) return;

	if (descriptionField.scrollHeight > descriptionField.clientHeight) {
		readMoreLink.classList.remove('d-none');
	} else {
		readMoreLink.classList.add('d-none');
	}

	readMoreLink.addEventListener('click', function (e) {
		e.preventDefault();
		descriptionField.classList.toggle('description-collapsed');
		this.textContent = descriptionField.classList.contains('description-collapsed')
			? "Read all description"
			: "Read less";
	});

	editBtn?.addEventListener('click', function (e) {
		e.preventDefault();
		form.style.display = 'block';
		descriptionField.parentElement.style.display = 'none';
	});

	closeBtn?.addEventListener('click', function () {
		form.style.display = 'none';
		descriptionField.parentElement.style.display = 'block';
	});
}

function initUI() {
	initTabsNavigation();
	initTabsScroll();
	initDescriptionBlock();
}

// Initialisation globale
document.addEventListener('DOMContentLoaded', initUI);
