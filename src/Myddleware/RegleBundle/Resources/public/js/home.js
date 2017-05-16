/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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

google.load("visualization", "1", {packages:["corechart"]});
google.setOnLoadCallback(drawChart);
function drawChart() {
	$.ajax({
		type: "POST",
		url: path_graph_type_error_doc,						
		success: function(dataServ){
			var data = google.visualization.arrayToDataTable(JSON.parse(dataServ));	 
			var options = {
			  is3D: true,
			};
		
			var chart = new google.visualization.PieChart(document.getElementById('pie_chart_error_doc'));
			chart.draw(data, options);			
		}			
	});  	
	
	$.ajax({
		type: "POST",
		url: path_graph_type_transfer_rule,						
		success: function(dataServ){
			var data = google.visualization.arrayToDataTable(JSON.parse(dataServ));	 
			var options = {
			  is3D: true,
			};
		
			var chart = new google.visualization.PieChart(document.getElementById('pie_chart_transfer_rule'));
			chart.draw(data, options);			
		}			
	});  

	$.ajax({
		type: "POST",
		url: path_graph_type_transfer_histo,						
		success: function(dataServ){
			var data = google.visualization.arrayToDataTable(JSON.parse(dataServ));	 
			var options = {
			  is3D: true,
			  isStacked: true,
			};
		
			var chart = new google.visualization.ColumnChart(document.getElementById('column_chart_histo'));
			chart.draw(data, options);			
		}			
	});  
	
	$.ajax({
		type: "POST",
		url: path_graph_type_job_histo,						
		success: function(dataServ){
			var data = google.visualization.arrayToDataTable(JSON.parse(dataServ));	 
			var options = {
			  is3D: true,
			  isStacked: true,
			};
		
			var chart = new google.visualization.ColumnChart(document.getElementById('column_chart_job_histo'));
			chart.draw(data, options);			
		}			
	});  
}    
//--------------

$( document ).ready(function() {
	// Refresh home page every 5 minutes
	setTimeout(function() { window.location=window.location;},300000);
	
	if($('#listing-solutions','#panel').length != 0) {
		$('#listing-solutions','#panel').scrollbox({
			direction: 'h',
			distance: 65
		});
		$('#listing-solutions-backward','#panel').click(function () {
		  $('#listing-solutions','#panel').trigger('backward');
		});
		$('#listing-solutions-forward','#panel').click(function () {
		  $('#listing-solutions','#panel').trigger('forward');
		});
		$('#listing-solutions li','#panel').hover(function () {
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


