/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  St�phane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  St�phane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com	
 
 This file is part of Myddleware.
 
 Myddleware is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Myddleware is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
*********************************************************************************/

google.charts.load("visualization", "1", {packages:["corechart"]});

document.addEventListener('DOMContentLoaded', function () {

	if (typeof countNbDocuments !== 'undefined') {
		// Fonction for animation of the counter
		function animateCounter(id, start, end, maxDuration) {
			var obj = document.getElementById(id);
			var current = start;
			var range = end - start;
			var increment = end > start ? 1 : -1;
			var duration = maxDuration;
			
			var stepTime = Math.abs(Math.floor(duration / Math.abs(range)));
			stepTime = Math.max(stepTime, 1);

			var timer = setInterval(function() {
				current += increment;
				obj.innerHTML = current;
				if (current == end) {
					clearInterval(timer);
				}
			}, stepTime);
		}

		animateCounter('countNbDocuments', 0, countNbDocuments, 2000);
	} else {
		console.error("countNbDocuments not defined");
	}

	const showMoreBtn = document.getElementById('show-more-btn');
	const showLessBtn = document.getElementById('show-less-btn');
	const hiddenItems = document.querySelectorAll('#error-list .list-group-item.d-none');
	const allItems = document.querySelectorAll('#error-list .list-group-item');

	if (showMoreBtn) {
		showMoreBtn.addEventListener('click', function () {
			hiddenItems.forEach(item => item.classList.remove('d-none'));
			showMoreBtn.classList.add('d-none');
			showLessBtn.classList.remove('d-none');
		});
	}

	if (showLessBtn) {
		showLessBtn.addEventListener('click', function () {
			allItems.forEach((item, index) => {
				if (index >= 5) {
					item.classList.add('d-none');
				}
			});
			showLessBtn.classList.add('d-none');
			showMoreBtn.classList.remove('d-none');
		});
	}

    document.querySelectorAll('.dropdown-toggle').forEach(function (dropdown) {
        dropdown.addEventListener('click', function (e) {
            e.preventDefault();
        });
    });
});
