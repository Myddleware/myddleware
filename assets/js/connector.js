/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2021  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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
const $ = require('jquery');


// WARNING - ALL ROUTING & IMAGES PATHS HAVE BEEN MODIFIED 'MANUALLY', THIS WILL NEED TO BE LOOKED INTO LATER
// AS TO WHY THE ROUTING & PATHS ARE WRONG SINCE WE'VE CHANGED WEBPACK BUILD PARAMETERS 

$( function() {
	// Test connexion
	$('#connexion').on('click', function(){

		var datas = '';
		var parent = 'source';
		var status = $('#source_status img');
		
		$( '.title' ).each(function(){
						
			if( $(this).text() != 'solution' && $(this).text() != 'nom') {				
				input = $('.params', $(this).parent());                
				if(input.attr('data-param') != undefined){
					datas += input.attr('data-param') + "::" + input.val().replace( /;/g, "" )+ ";";
				}
			}			
		});		
		
		$.ajax({
			type: "POST",
			// url: Routing.generate('regle_inputs'),
			url: '../../inputs',
			data:{
				champs : datas,
				parent : parent,
				solution : $('.vignette').attr('alt'),
				mod : 2 // connexion
			},	
			beforeSend:	function() {				
				status.removeAttr("src");
				status.attr("src", "../" +path_img + "loader.gif");				
			},				
			success: function(json){
				
				//r = data.split(';');
				// Si connexion echoue
				if(!json.success) {							
					status.removeAttr("src");
					status.attr("src", "../" +path_img + "status_offline.png");
					$('#msg_status span.error').html(json.message);
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
					// url: Routing.generate('connector_callback'),
					url: '../callback/',
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
								// url: Routing.generate('connector_callback'),
								url: '../callback/',
								success: function(data){	

									// if 1ere fois
									if(data != 1) {		
										
										var win = window.open( link, 'Connexion','scrollbars=1,resizable=1,height=560,width=770'); 
										if(data != 401) {
											var timer = setInterval(function() {   
												if(win.closed) {  
													clearInterval(timer);  									        
													if (confirm("Reconnect")) {
														$('#connexion').trigger();
													} 
												}  
											}, 1000); 												
										} 
										else {
											$('#connexion').trigger();
										} 																									
																			
										r = data.split(';');							
																	
										status.removeAttr("src");
										status.attr("src", "../" +path_img+"status_offline.png");
										$('#msg_status span.error').html(r[0]);
										$('#msg_status').show();																												
									}
									else {									
										status.removeAttr("src");
                                        console.log('003 myddleware status online')
										status.attr("src", "../"+path_img+"status_online.png");
										$('#msg_status').hide();
										$('#msg_status span.error').html('');	
										$('#step_modules_confirme').removeAttr('disabled');					
									}
								}
							});
						}// sans popup
						else {						
							if(!json.success) {								
								status.removeAttr("src");
								status.attr("src","../" +path_img+"status_offline.png");
								$('#msg_status span.error').html(r[0]);
								$('#msg_status').show();
							}
							else{
								let pathString = "../";
                                // if the window path contains index.php (docker)
                                if (window.location.pathname.includes("index.php")) {
                                    pathString = "../../../../";
                                }	
								status.removeAttr("src");
                                        console.log('004 myddleware status online')
								status.attr("src", pathString + path_img + "status_online.png");
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