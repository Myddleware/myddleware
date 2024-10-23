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

	$(".toggle-btn-homepage").click(function () {
        var extraText = $(this).find(".extra-text");
        if (extraText.is(":visible")) {
            extraText.slideUp();
        } else {
            extraText.slideDown();
        }
    });

	if (typeof countNbDocuments !== 'undefined') {
		// Function for smooth animation of the counter
		function animateCounter(id, start, end, duration) {
			var obj = document.getElementById(id);
			var startTime = null;
	
			function easeOutQuad(t) {
				return t * (2 - t);
			}
	
			function animate(currentTime) {
				if (startTime === null) startTime = currentTime;
				var timeElapsed = currentTime - startTime;
				var progress = Math.min(timeElapsed / duration, 1);
				var easedProgress = easeOutQuad(progress);
				
				var currentValue = Math.round(start + (end - start) * easedProgress);
				obj.innerHTML = currentValue;
	
				if (progress < 1) {
					requestAnimationFrame(animate);
				}
			}
	
			requestAnimationFrame(animate);
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
