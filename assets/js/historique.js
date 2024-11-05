// Function to store the title and URL of the current page
function storePageInHistory() {
    let history = JSON.parse(localStorage.getItem('pageHistory')) || [];
    let currentPageTitle = document.title;
    const currentPageURL = window.location.href;

    if (currentPageTitle.includes('|')) {
        currentPageTitle = currentPageTitle.split('|')[1].trim();
    }

    if (!history.some(item => item.url === currentPageURL)) {
        history.push({ title: currentPageTitle, url: currentPageURL });
        if (history.length > 5) history.shift();
        localStorage.setItem('pageHistory', JSON.stringify(history));
    }
}

function displayHistory() {
    const historyDropdown = document.getElementById('navHistoryDropdown');
    const history = JSON.parse(localStorage.getItem('pageHistory')) || [];

    historyDropdown.innerHTML = '';

    if (history.length === 0) {
        historyDropdown.innerHTML = '<li>No history available</li>';
    } else {
        history.forEach(page => {
            const listItem = document.createElement('li');
            listItem.textContent = page.title;
            listItem.onclick = () => window.location.href = page.url;
            historyDropdown.appendChild(listItem);
        });
    }
}

// Store the current page and set up the event to display history
storePageInHistory();
document.getElementById('dropdownNavHistory').addEventListener('mouseenter', displayHistory);