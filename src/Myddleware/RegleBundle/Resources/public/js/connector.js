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

$( document ).ready(function() {

	// Sauvegarde du connecteur
	$('#connector_save','#connector').click(function(){	
		params = [];
		$('.params','#tab_connector').each(function(){
			id = $.trim( $(this).attr('data-id') );
			value = $.trim( $(this).val() );
			
			params.push( {value: value, id: id} );
		});	
		
		$.ajax({
		    type: "POST",
			url: path_connector_save,
			data:{
				params : params,
				nom : $('#label').val()
			},
			success: function(data){
				if(data == 0) {
					$('#validation').html('<span class="glyphicon glyphicon-remove"></span>');
				}
				else {
					$('#validation').html('<span class="glyphicon glyphicon-ok"></span>');
					window.location.href = data;
				}
			 }
		});
					
	});

	// Test connexion
	$('#connexion').click(function(){
		var datas = '';
		var parent = 'source';
		var status = $('#source_status img');
		
		$( '.title' ).each(function(){
						
			if( $(this).text() != 'solution' && $(this).text() != 'nom') {				
				input = $('.params', $(this).parent());
				datas += $(this).text() + "::" + input.val() + ";";	
			}			
		});		
		
		$.ajax({
			type: "POST",
			url: inputs,
			data:{
				champs : datas,
				parent : parent,
				solution : $('.vignette').attr('alt'),
				mod : 2 // connexion
			},	
			beforeSend:	function() {				
				status.removeAttr("src");
				status.attr("src",path_img + "loader.gif");							
			},				
			success: function(data){
				
				r = data.split(';');
				// Si connexion echoue
				if(r[1] == 0) {							
					status.removeAttr("src");
					status.attr("src",path_img + "status_offline.png");
					$('#msg_status span.error').html(r[0]);
					$('#msg_status').show();
					return false;
				}
				// Si connexion ok alors on test si la solution à besoin de la pop-up	
				$.ajax({
					type: "POST",
					data:{
						solutionjs : true,
						detectjs : true
					}, 				
					url: callback,							
					success: function(data){
						param = data.split(';');
						
						// si popup
						if(param[0] == 1) {	
							
							link = param[1];
										
							$.ajax({
								type: "POST",
								data:{
									solutionjs : true
								},					
								url: callback,							
								success: function(data){	

									// if 1ere fois
									if(data != 1) {		
										
										var win = window.open( link, 'Connexion','scrollbars=1,resizable=1,height=560,width=770'); 
										if(data != 401) {
											var timer = setInterval(function() {   
											    if(win.closed) {  
											        clearInterval(timer);  									        
											        if (confirm("Reconnect")) {
											        	$('#connexion').click();
											        } 
											    }  
											}, 1000); 												
										} 
										else {
											$('#connexion').click();
										} 																									
																			
										r = data.split(';');							
																	
										status.removeAttr("src");
										status.attr("src",path_img+"status_offline.png");
										$('#msg_status span.error').html(r[0]);
										$('#msg_status').show();																												
									}
									else {									
										status.removeAttr("src");
										status.attr("src",path_img+"status_online.png");
										$('#msg_status').hide();
										$('#msg_status span.error').html('');	
										$('#step_modules_confirme').removeAttr('disabled');					
									}
								}
							});
						}// sans popup
						else {						
							if(r[1] == 0) {								
								status.removeAttr("src");
								status.attr("src",path_img+"status_offline.png");
								$('#msg_status span.error').html(r[0]);
								$('#msg_status').show();
							}
							else{
								status.removeAttr("src");
								status.attr("src",path_img+"status_online.png");
								$('#msg_status').hide();
								$('#msg_status span.error').html('');
							}						
						}							
						
						
					}
				});				

			 }
		});		 
	});

});