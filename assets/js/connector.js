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
	// Get data from database
	let isLoadingData = false;

	$('#get_from_database').on('click', function(){
		// Prevent multiple simultaneous requests
		if (isLoadingData) {
			return false;
		}

		// Get the connector ID from the form action URL
		const form = $('form[method="POST"]');
		const actionUrl = form.attr('action');
		const connectorId = actionUrl.match(/\/(\d+)$/)[1];

		// get the window location
		const windowLocation = window.location.href;
		// console.log('Window Location:', windowLocation);

		// Create the API URL based on the connector ID and the window location
		const apiUrl = windowLocation.replace(/\/connector\/view\/\d+$/, '/api/connector/get-data/' + connectorId);
		// console.log('API URL:', apiUrl);
		// const apiUrlModel = "http://localhost/myddleware_NORMAL/public/rule/api/connector/get-data/11"
		// console.log('API URL Model:', apiUrlModel);

		function showAlert(message, isSuccess) {
			// Remove any existing alerts
			$('#get_from_database').siblings('.alert').remove();

			// Create new alert
			const alertClass = isSuccess ? 'alert-success' : 'alert-danger';
			const alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
				message +
				'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
			'</div>';
			$('#get_from_database').before(alertHtml);

			// Auto-dismiss after 5000ms
			setTimeout(function() {
				$('#get_from_database').siblings('.alert').fadeOut(300, function() {
					$(this).remove();
				});
			}, 5000);
		}

		$.ajax({
			type: "POST",
			url: apiUrl,
			dataType: 'json',
			beforeSend: function() {
				// Show loading state
				isLoadingData = true;
				$('#get_from_database').prop('disabled', true).text('Loading...');
			},
			success: function(data){
				if(data.success) {
					// Populate the connector name field
					$('#connector_name, #label').val(data.name);

					// Populate connector parameters
					$.each(data.params, function(paramName, paramValue) {
						// Find the input field by data-param attribute
						const paramInput = $('input[data-param="' + paramName + '"], textarea[data-param="' + paramName + '"], input[type="password"][data-param="' + paramName + '"]');
						if (paramInput.length > 0) {
							paramInput.val(paramValue);
						}
					});

					// Show success message
					showAlert('Connector data loaded successfully from database', true);
				} else {
					// Show error message
					showAlert('Error: ' + data.message, false);
				}
			},
			error: function(xhr, status, error){
				// Show error message
				const errorMsg = xhr.status === 404 ? 'Connector not found' : 'Error loading connector data';
				showAlert(errorMsg, false);
			},
			complete: function() {
				// Restore button state
				isLoadingData = false;
				$('#get_from_database').prop('disabled', false).text('Get from database');
			}
		});

		return false;
	});

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