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

google.charts.load("visualization", "1", { packages: ["corechart"] });

document.addEventListener("DOMContentLoaded", function () {
    $(".toggle-btn-homepage").click(function () {
        var extraText = $(this).find(".extra-text");
        if (extraText.is(":visible")) {
            extraText.slideUp();
        } else {
            extraText.slideDown();
        }
    });

    if (typeof countNbDocuments !== "undefined") {
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

                var currentValue = Math.round(
                    start + (end - start) * easedProgress
                );
                obj.innerHTML = currentValue;

                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            }

            requestAnimationFrame(animate);
        }
		
        animateCounter("countNbDocuments", 0, countNbDocuments, 2000);
    } else {
        console.error("countNbDocuments not defined");
    }
    document.querySelectorAll(".dropdown-toggle").forEach(function (dropdown) {
        dropdown.addEventListener("click", function (e) {
            e.preventDefault();
        });
    });
    const list = document.querySelector(".error-list");
    const btn = document.getElementById("toggleErrorsBtn");
    const maxVisible = 4;

    if (list && btn) {
        const items = list.querySelectorAll("li");

        btn.addEventListener("click", function () {
            const isExpanded = btn.getAttribute("data-expanded") === "true";

            items.forEach((item, index) => {
                if (index >= maxVisible) {
                    item.classList.toggle("d-none", isExpanded);
                }
            });

            btn.textContent = isExpanded ? "Show more" : "Show less";
            btn.setAttribute("data-expanded", !isExpanded);
        });
    }
});
