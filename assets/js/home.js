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
google.charts.setOnLoadCallback(drawChart);

function drawChart() {
	if ($('#pie_chart_error_doc').length) {
		$.ajax({
			type: "POST",
			url: 'graph/type/error/doc',
			success: function (dataServ) {
				var data = google.visualization.arrayToDataTable(dataServ);
				var options = {
					is3D: false,
				};

				var chart = new google.visualization.PieChart(document.getElementById('pie_chart_error_doc'));
				chart.draw(data, options);
			}
		});
	}

	if ($('#pie_chart_transfer_rule').length) {
		$.ajax({
			type: "POST",
			url: 'graph/type/transfer/rule',
			success: function (dataServ) {
				
				var data = google.visualization.arrayToDataTable(dataServ);
				var options = {
					is3D: false,
				};
				var chart = new google.visualization.PieChart(document.getElementById('pie_chart_transfer_rule'));
				chart.draw(data, options);
			}
		});
	}

	if ($('#column_chart_histo').length) {
		$.ajax({
			type: "POST",
			url: 'graph/type/transfer/histo',
			success: function (dataServ) {
				var data = google.visualization.arrayToDataTable(dataServ);
				var options = {
					is3D: false,
					isStacked: true,
					legend: { position: 'bottom'},
					height: 400,
					width: 575
				};

				var chart = new google.visualization.ColumnChart(document.getElementById('column_chart_histo'));
				chart.draw(data, options);
			}
		});
	}

	if ($('#column_chart_job_histo').length) {
		$.ajax({
			type: "POST",
			url: 'graph/type/job/histo',
			success: function (dataServ) {
				
				var data = google.visualization.arrayToDataTable(dataServ);
				
				var options = {
					is3D: false,
					isStacked: true,
					legend: { position: 'bottom'},
					height: 400,
					width: 600,
				};

				var chart = new google.visualization.ColumnChart(document.getElementById('column_chart_job_histo'));
				chart.draw(data, options);
			}
		});
	}
}    

//--------------

$(function()  {
	if($('#listing-solutions','#panel').length != 0) {
		$('#listing-solutions','#panel').scrollbox({
			direction: 'h',
			distance: 65
		});
		$('#listing-solutions-backward','#panel').on('click', function () {
			$('#listing-solutions','#panel').trigger('backward');
		});
		$('#listing-solutions-forward','#panel').on('click', function () {
			$('#listing-solutions','#panel').trigger('forward');
		});
		$('#listing-solutions li','#panel').on('hover', function () {
			string = $("img", this).attr('alt')
			$("img", this).qtip({
				content: string.charAt(0).toUpperCase() + string.slice(1) + ": " + trans_click,
				show: {
					delay: 700,
					ready: 'true',
		    	},
				position: {
					my: 'top center',  // Position my top left...
					at: 'bottom center', // at the bottom right of...
				}
			})},
			function () {
				$('#listing-solutions img','#panel').each(function(){
					$(this).qtip("hide");
				});
			}
		);
	}
});	
