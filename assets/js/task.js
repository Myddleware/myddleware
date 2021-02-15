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
console.log("allo");
$(function() {
	console.log("heheo");
	if($('#task_status','#task').attr('name') != "End"){
		// Sauvegarde du connecteur
		$('#task_refresh','#task').on('click', function(){	
			location.reload();	 
		});
		
		$('#task_stop','#task').on('click', function(){	
			console.log('click');
			$.ajax({
				type: "POST",
				url: path_task_stop,					
				success: function(data){
					console.log(data);
					location.reload();	
				}
			});	    	
		});
	} else {
		$('#task_refresh','#task').hide();
		$('#task_stop','#task').hide();
	}
	
	
});