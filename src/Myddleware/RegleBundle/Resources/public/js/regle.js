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

// Rev 1.1.0 Upload Files ------------------------------
// Fermeture de la fancybox
$(".fancybox_upload").fancybox({
	maxWidth	: 800,
	maxHeight	: 600,
	fitToView	: false,
	width		: '70%',
	height		: '70%',
	autoSize	: false,
	closeClick  	: false,
    openEffect   : 'elastic',
    closeEffect  : 'elastic',
    'beforeClose': function() { 
    	name_file_upload();
    },
    'beforeLoad ': function() { 
    	if(!confirm_upload()) {
    		$.fancybox.cancel();
    	}
    },    
    'closeClick': true	
});	


function name_file_upload() {
	$.ajax({
		type: 'POST',
		url: path_upload,									
		success: function(data){
			r = data.split(';');	
			if(r[0] == 1) {
				$('#param_wsdl').val(r[1]);
			}
			
			// Createout
			$('#source_test').removeAttr('disabled');	

		}			
	});			
}
// Rev 1.1.0 Upload Files ------------------------------

function confirm_upload() {
	$('#link_wsdl').click(function(e){
		if( $('#param_wsdl').val() != '' ) {
			if(confirm("Souhaitez-vous abandonner l'ancien fichier de configuration ?")) {
				return true;				
			}
			else {
				return false;
			}			
		}
	});
}

confirm_upload();

function htmlentities(value){
    if (value) {
        return jQuery("<div />").text(value).html();
    } else {
        return '';
    }
}

// rev 1.08 --------
var TabSpec = {"à":"a","á":"a","â":"a","ã":"a","ä":"a","å":"a","ò":"o","ó":"o","ô":"o","õ":"o","ö":"o","ø":"o","è":"e","é":"e","ê":"e","ë":"e","ç":"c","ì":"i","í":"i","î":"i","ï":"i","ù":"u","ú":"u","û":"u","ü":"u","ÿ":"y","ñ":"n","-":" ","_":" "}; 
 
function replaceSpec(Texte){
	var reg=/[àáäâèéêëçìíîïòóôõöøùúûüÿñ-]/gi; 
	return Texte.replace(reg,function(){ return TabSpec[arguments[0].toLowerCase()];}).toLowerCase();
 	}

function removeSpace(string) {
	string = replaceSpec(string);
	string = string.replace(/\s/g,"");	
	string = string.replace(/[^a-zA-Z0-9_\s]/gi, '')
	return string;
}
// rev 1.08 --------

$( document ).ready(function() {
	// ----------------------------- AFFICHAGE DU LOADING LANCEMENT REGLE / ANNULER FLUX
	$( window ).load(function(){
		// Bouton action "Exécuter règles actives"
		$( "#exec_all", "#rule").click(function(e) {
			if (confirm(confirm_exec_all)) { // Clic sur OK
				btn_action_fct();
			} else {
				e.preventDefault();
			}
		});
		// Bouton action "Relancer les erreurs"
		$( "#exec_error", "#rule").click(function(e) {
			if (confirm(confirm_exec_error)) { // Clic sur OK
				btn_action_fct();
			} else {
				e.preventDefault();
			}
		});
		// ----------------------------- Boutons d'actions (affichage d'un loading)
		// Appelé pour: "Annuler transfert" et "Exécuter la règle"
		$( ".btn_action_loading" ).click(function(e) {
			btn_action_fct();
		});
		
		$(window).resize(function(){
			$('#myd_loading').css({
				"width": $(window).width()+"px",
				"height": $(window).height()+"px"
			});
			$('#myd_loading > p').css({
				"top": $(window).height()/2 - 100,
				"left": $(window).width()/2 - 65,
			});	
		});
	});
	
	/* infobulle mapping des champs */
	function fields_target_hover() {
		$( '.ch' ).hover(		
			function() {
				$( this ).append( $( '<div class="info_delete_fields"><span class="glyphicon glyphicon-info-sign"></span> '+ infobulle_fields +'</div>' ) );
			}, function() {
				$( this ).find( "div:last" ).remove();
			}
		);	
		
		$( '.ch' ).click(function(){
			$( this ).find( "div:last" ).remove();
		});		
	}
	
	// rev 1.08 ----------------------------
	function previous_next(tab) {
		// tab 0 : default
		// tab 1 : plus
		// tab 2 : manual tab 		
		name_current = $('.ui-state-active').attr('aria-labelledby');
		number = 0;		
		number = name_current.split('-');	
		number = parseInt(number[2]);
		
		$('#rule_previous').show();		
		$('#rule_next').show();
			
		if(number == 3) {
			$('#rule_previous').hide();
			$('#rule_next').show();
		}	
		
		if(number == 7) {
			$('#rule_next').hide();
			$('#rule_previous').show();
		}
		
		if(tab == 1) {
			number_next = number + 1;	
			$('#ui-id-'+number_next).click();	
		}
		else if(tab == 0) {
			number_previous = number - 1;	
			$('#ui-id-'+number_previous).click();			
		}
		
	}
		
	$('#rule_previous').click(function(){
		previous_next(0);
	});

	$('#rule_next').click(function(){
		previous_next(1);
	});	

	$( '#tabs','#rule_mapping' ).tabs({ activate: function( event, ui ) { 
		previous_next(2);
	} });
	// rev 1.08 ----------------------------
		
	function btn_action_fct() {
		// IMPORTANT
		//e.preventDefault();

		$(window).scrollTop(0);
		$('body').css('overflow', 'hidden');
		var ww = $(window).width() / 2 - 33 + "px";
		var wh = $(window).height() / 2 - 33 + "px";
		var divrule = $("#rule");
		if(!(divrule.length)) {
			var divrule = $("#flux");
		}
		var loading = $("<div></div>");
		loading.empty(); // on le vide
		loading.attr('id', 'myd_loading');
		loading.css({
			"position": "absolute",
			"display": "block",
			"top": 0,
			"left": 0,
			"width": $(window).width()+"px",
			"height": $(window).height()+"px",
			"background-color": "white",
			"text-align": "center",
			"z-index": 100
		});
		loading.attr('class', 'myd_div_loading');
		
		var p = $('<p>Please wait. This can take few minutes.</p>');
		p.css({
			"position": "absolute",
			"top": $(window).height()/2 - 100,
			"left": $(window).width()/2 - 65,
			"width": "130px",
			"height": "60px",
			"font-weight": "bold"
		});
		loading.append(p);
		
		var img = $("<div></div>");
		img.attr('class', 'myd_div_loading_logo');
		img.css({
			"position": "absolute",
			"top": "5px",
			"left": "5px",
			"height": "150px",
			"width": "150px"
		});
		loading.append(img);
		divrule.append(loading);
	}
	
	function notification() {
		notification = $.trim( $('#zone_notification','#notification').html() );
		
		if( notification != '' ) {
			$('#notification').fadeIn();
		}

	}

	
	$('.tooltip').qtip(); // Infobulle
	
	
	notification();
	

	// ----------------------------- List rule
	if ( typeof question !== "undefined" && question) {
		$('#listrule .delete').on( "click", function() {	
			var answer = confirm(question)
			if (answer){
				return true;
			}
			else{
				return false;
			}
		});			
	}

	// ----------------------------- Step 
    if ( typeof onglets !== "undefined" && onglets) {
          $( "#tabs" ).tabs(onglets);
    }
		     
	// ----------------------------- Step 1

	// Test si le name de la règle existe ou non
	$( '#rulename' , '#connexion').keyup(function() {
		var error = 1;
		if($( this ).val().length > 2 ) {
			$.ajax({
				type: "POST",
				url: inputs_rule_name,
				data:{
					name : $( this ).val()
				},							
				success: function(msg){
					if( msg == 0 ) {
						$('#rulename').css('border',' 3px solid #0DF409');
						error = 0;	
					}
					else {					
						$('#rulename').css('border','3px solid #E81919');
						error++;
					}
					
					next_step(error);			
				}			
			});
		}
		else {
			$('#rulename').css('background','#202020');	
			$('#rulename').css('border','3px solid transparent');
			next_step(0);		
		}
	});
		
	$('#source_msg').hide(); // message retour
	$('#cible_msg').hide();  // message retour	

	// Tentative de connexion
	$(document).on('change', '#soluce_cible, #soluce_source', function() {
		
		var val = $( this ).val();
		var parent = $( this ).parent().attr( 'id' );
		var val2 = val.split('_');
		
		$('#msg_status').hide();
	
		if(val == '') {
			$('#'+parent+'_msg').hide();
			$( this ).parent().find('.picture').empty();	
			$( this ).parent().find('.champs').empty();
			$( this ).parent().find('.help').empty();			
		}
		else
			{ 
				$( this ).parent().find('.picture img').remove();
				$( this ).parent().find('.help').empty();
				var solution = ((val2[0]) ? val2[0] : val );				
				$( this ).parent().find('.picture').append( '<img src="'+path_img+'solution/'+solution+'.png" alt="'+solution+'" />' ); 
				
				$( this ).parent().find('.help').append( '<span class="glyphicon glyphicon-info-sign"></span> <a href="'+path_link_fr+solution+'" target="_blank">'+help_connector+'</a>' ); 
									
				if($.isNumeric(val2[1])) {
						
					$.ajax({
						type: "POST",
						url: inputs,
						data:{
							solution : val,
							parent : parent,
							name : $('#rulename').val(),
							mod : 3
						},
						beforeSend:	function() {
							$('#'+parent+'_status img').removeAttr("src");
							$('#'+parent+'_status img').attr("src",path_img+"loader.gif");								
						},							
						success: function(msg){
							r = msg.split(';');
							
							if(r[1] == 0) {		
								$('#'+parent+'_status img').removeAttr("src");
								$('#'+parent+'_status img').attr("src",path_img+"status_offline.png");
								$('#'+parent+'_msg span').html(r[0]);
								$('#'+parent+'_msg').show();								
							}
							else{
								$('#'+parent+'_status img').removeAttr("src");
								$('#'+parent+'_status img').attr("src",path_img+"status_online.png");
								$('#'+parent+'_msg').hide();
								$('#'+parent+'_msg span').html('');								
							} 
							
							next_step(0);
						 }
					});	
											
				}
				else {
					// Recupere tous les champs de connexion
					champs(val,$( this ).parent().find('.champs'), parent);
				}
			}
	});	
	// Si nom de règle est vide alors retour false du formulaire
	$('#connexion #step_modules_confirme').on( "click", function() {	
		if(!$('#rulename').val()) {
			return false;
		}	
	});
	// Préparation de l'étape suivante
	function next_step(error) {
		$('.status').find('img').each(function(){
			if($(this).attr("src") !=path_img+'status_online.png') {
				error++;
			}
		});
				
		var connector = $("#connexion_connector");	 
		if(connector.length){
			// create connector
		} else {
			// other
			if( $('#rulename').val() == '' || $('#rulename').val().length < 3 ) {
				error++;
			}
		}		
	
		if(error == 0) {
			$('#step_modules_confirme').removeAttr('disabled');
		}
		else {
			$('#step_modules_confirme').attr("disabled","disabled");
		}
	}
	
	$('#msg_status').hide(); // message retour	
		
	// vérification our la création d'un connecteur
	function verif(div_clock) {
		$('.testing', div_clock).on( "click", function() {	
						
			var parent = $(this).parent().parent().attr( "id" );
			var datas="";
			var status = $(div_clock).parent().find('.status img');
			var solution = $(div_clock).parent().find('.liste_solution').val();
			
			$( $(this).parent() ).find( "input" ).each(function(){

				datas += $(this).attr("name")+"::"+$(this).val()+";";	
			});		
			
			$.ajax({
				type: "POST",
				url: inputs,
				data:{
					champs : datas,
					parent : parent,
					solution : solution,
					mod : 2
				},	
				beforeSend:	function() {
					$(status).removeAttr("src");
					$(status).attr("src",path_img+"loader.gif");								
				},				
				success: function(data){
					
					r = data.split(';');
					
					if(r[1] == 0) {							
						$(status).removeAttr("src");
						$(status).attr("src",path_img+"status_offline.png");
						$('#msg_status span.error').html(r[0]);
						$('#msg_status').show();
						return false;
					}
					
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
											
											data_error_without_popup = data.split(';');
											data_error_with_popup = data.split('-');
											
											data = data_error_with_popup[0];
										
											if(data != 401 && data_error_without_popup[0] != 2 ) {
												var win = window.open(link, 'Connexion','scrollbars=1,resizable=1,height=560,width=770'); 
												var timer = setInterval(function() {   
												    if(win.closed) {  
												        clearInterval(timer);  									        
												        if (confirm("Reconnect")) {
												        	$('#source_test').click();
												        } 
												    }  
												}, 1000); 												
											}

											response = data.split(';');							
																		
											$(status).removeAttr("src");
											$(status).attr("src",path_img+"status_offline.png");
											
											if(typeof data_error_without_popup[0] !== "undefined" && data_error_without_popup[0] == 2) {
												response[0] = data_error_without_popup[1];
											}

											$('#msg_status span.error').html(response[0]);
											$('#msg_status').show();												
																									
										}
										else {									
											$(status).removeAttr("src");
											$(status).attr("src",path_img+"status_online.png");
											$('#msg_status').hide();
											$('#msg_status span.error').html('');	
											$('#step_modules_confirme').removeAttr('disabled');					
										}
									}
								});
							}// sans popup
							else {						
								if(r[1] == 0) {								
									$(status).removeAttr("src");
									$(status).attr("src",path_img+"status_offline.png");
									$('#msg_status span.error').html(r[0]);
									$('#msg_status').show();
								}
								else{
									$(status).removeAttr("src");
									$(status).attr("src",path_img+"status_online.png");
									$('#msg_status').hide();
									$('#msg_status span.error').html('');
									$('#step_modules_confirme').removeAttr('disabled');	
								}						
							}							
							
							
						}
					});
					
					next_step(0);
				 }
			});

		});	
		
		$( div_clock , 'input').keyup(function() {
			
			var err = 0;
			var btn = $( $(this) ).find( ".testing" );
			
			$( $(this) ).find( "input" ).each(function(){
				if($(this).val().length == 0) {
					err++;
				}
			});
			
			if(err == 0) {
				$(btn).removeAttr("disabled");
			}
			else {
				$(btn).attr("disabled","disabled");
			}
		});					
	}
							
	function champs(solution,champs,parent) {	
		$.ajax({
			type: "POST",
			url: inputs,
			data:{
				solution : solution,
				parent : parent,
				mod : 1
			},
			success: function(data){		
				$( champs ).html(data);
				verif( champs );
			 }
		});									
	}	
	
	// ----------------------------- Step 3
	

	
	if ( typeof lang !== "undefined" && lang) {

			$('#datereference, .calendar').datepicker({
				dateFormat: 'yy-mm-dd'
			});
		
		if(lang == 'fr') {

			$.datepicker.regional['fr'] = {
				closeText: 'Fermer',
				prevText: 'Précédent',
				nextText: 'Suivant',
				currentText: 'Aujourd\'hui',
				monthNames: ['janvier', 'février', 'mars', 'avril', 'mai', 'juin',
				'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'],
				monthNamesShort: ['janv.', 'févr.', 'mars', 'avril', 'mai', 'juin',
				'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'],
				dayNames: ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'],
				dayNamesShort: ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'],
				dayNamesMin: ['D','L','M','M','J','V','S'],
				weekHeader: 'Sem.',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: ''};
				$.datepicker.setDefaults($.datepicker.regional['fr']);			
		}
		else {
	        $.datepicker.regional['en-GB'] = {
	                closeText: 'Done',
	                prevText: 'Prev',
	                nextText: 'Next',
	                currentText: 'Today',
	                monthNames: ['January','February','March','April','May','June',
	                'July','August','September','October','November','December'],
	                monthNamesShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
	                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
	                dayNames: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
	                dayNamesShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
	                dayNamesMin: ['Su','Mo','Tu','We','Th','Fr','Sa'],
	                weekHeader: 'Wk',
	                dateFormat: 'dd/mm/yy',
	                firstDay: 1,
	                isRTL: false,
	                showMonthAfterYear: false,
	                yearSuffix: ''};
	        $.datepicker.setDefaults($.datepicker.regional['en-GB']);
			
		}
			
	}

// ---- PREPARATION DE LA ZONE DRAGGABLE ------------------------------------

function prepareDrag() {
		// détecte la position du curseur dans une zone	
		(function ($, undefined) {
		    $.fn.getCursorPosition = function () {
		        var el = $(this).get(0);
		        var pos = 0;
		        if ('selectionStart' in el) {
		            pos = el.selectionStart;
		        } else if ('selection' in document) {
		            el.focus();
		            var Sel = document.selection.createRange();
		            var SelLength = document.selection.createRange().text.length;
		            Sel.moveStart('character', -el.value.length);
		            pos = Sel.text.length - SelLength;
		        }
		        return pos;
		    }
		})(jQuery);			
			
			$( "#catalog" ).accordion({
				collapsible: true,
				heightStyle: "content"
			}); // liste des modules : source
			 
			$( "#catalog li" ).draggable({
				"appendTo" : "body",
				"helper" : "clone"
			});
			 
			$( ".champs ol" )
				.droppable({
					"activeClass" : "ui-state-default",
					"hoverClass" : "ui-state-hover",
					"accept" : ":not(.ui-sortable-helper)",
					"drop" : function( event, ui ){
						var dragId = ui.draggable.find('a').attr('id');
						var	str = ui.draggable.text();
			 
						$( this ).find( '.placeholder' ).remove();
			 
						if ( $( "li.ch:contains(" + str + ")", this ).length < 1 ){
							$( this ).append( '<li value="' + dragId + '" class="ch">' + str + '</li>' );
							
							/*
							var formule_in = $( this ).parent().find( ".formule" );
							$( formule_in ).css('opacity','1');	*/
							
							addFilter( dragId,path_info_field );	
							
							$('li',"#fields_duplicate_target").each(function() {
								if( $(this).attr('data-active') == 'false') {
									if( $(this).attr('class') == 'no_active' ){
										$(this).removeAttr('class');
										$(this).click();
									}
								}
							});	
						}
						
						fields_target_hover();
					}
				})
				.sortable({
					"items" : "li.ch:not(.placeholder)",
					"sort" : function(){
						$( this ).removeClass( "ui-state-default" );
					}
				});
			 
				$( ".champs ol" ).on( "dblclick", "li", function(){
					if(typeof placeholder !== "undefined") {
						if ( $( this ).parent().children().length < 2 ){
							$( this ).parent().append( '<li class="placeholder">'+placeholder+'</li>' );
							
							/*
							var formule_in = $( this ).parent().parent().find( '.formule' );
							$( formule_in ).css('opacity','0.5'); */	
							
							var formule_text = $( this ).parent().parent().find( 'ul' );
							formule_text.children().empty();
							$('#area_color').empty();	
										
						}
						
						target = $.trim( $(this).parent().parent().parent().find('h1').text() );
						fields = $( "#fields_duplicate_target" ).find( "li:contains(" + target + ")" );		
						fields.removeAttr('class');
						fields.attr('data-active','false');	
						
					 	removeFilter( $.trim($( this ).attr('value')) );	
						$( this ).remove();
					}
				});	
}

prepareDrag();

				
// ---- PREPARATION DE LA ZONE DRAGGABLE -----------------------------------

// ---- FORMULE ------------------------------------------------------------
if ( typeof style_template !== "undefined" && typeof formula_error !== "undefined" ) {

	//-- syntax color
	// coloration syntaxique des formules
	function theme(style_template) {
		
		$('#area_color .operateur').css('letter-spacing','5px');
		$('#area_color .chaine').css('letter-spacing','2px');
		$('#area_color .variable').css('letter-spacing','2px');
		
		if(style_template == 'dark') {
			$('#area_color').css('background-color','#272822');
			$('#area_color').css('color','#f8f8f8');
			$('#area_color .operateur').css('color','#f92665');		
			$('#area_color .chaine').css('color','#c8bf6f');
			$('#area_color .variable').css('color','#8966c9');						
		}
		else if(style_template == 'light') {
			$('#area_color').css('background-color','#fdf6e3');
			$('#area_color').css('color','#61b5ac');
			$('#area_color .operateur').css('color','#d33613');		
			$('#area_color .chaine').css('color','#268bd2');
			$('#area_color .variable').css('color','#8966c9');				
		}
		else if(style_template == 'myddleware') {
			$('#area_color').css('background-color','#EDEDED');
			$('#area_color').css('color','#444446');
			$('#area_color .operateur').css('color','#EC8709');		
			$('#area_color .chaine').css('color','#268bd2');
			$('#area_color .variable').css('color','#CCC31C');				
		}			
	}	

	$( '#area_insert' ).keyup(function() {		
		colorationSyntax();
		theme(style_template);	
	});	
	
	function colorationSyntax() {	
			
		$('#area_color').html( $('#area_insert').val() );
		
		if($('#area_insert').val() == '') {
			$('#area_color').empty();
		}
			
		$("#area_color").each(function() {
		    var text = $(this).html();
    		    
		    //---
		    text = text.replace(/\===/g, "[begin] class='operateur'[f]===[end]");
		    text = text.replace(/\==/g, "[begin] class='operateur'[f]==[end]");
		    text = text.replace(/\!==/g, "[begin] class='operateur'[f]!==[end]");
		    text = text.replace(/\!=/g, "[begin] class='operateur'[f]!=[end]");
		    //---	
		    text = text.replace(/\!/g, "[begin] class='operateur'[f]![end]");	
		    text = text.replace(/\./g, "[begin] class='operateur'[f].[end]");
		    text = text.replace(/\:/g, "[begin] class='operateur'[f]:[end]");
		    text = text.replace(/\?/g, "[begin] class='operateur'[f]?[end]");
			text = text.replace(/\(/g, "[begin] class='operateur'[f]([end]");
			text = text.replace(/\)/g, "[begin] class='operateur'[f])[end]");
			//--
			text = text.replace(/\//g, "[begin] class='operateur'[f]/[end]");				
			text = text.replace(/\+/g, "[begin] class='operateur'[f]+[end]");
			text = text.replace(/\-/g, "[begin] class='operateur'[f]-[end]");
			text = text.replace(/\*/g, "[begin] class='operateur'[f]*[end]");
			//---
			text = text.replace(/\&gt;=/g, "[begin] class='operateur'[f]>=[end]");
			text = text.replace(/\&gt;/g, "[begin] class='operateur'[f]>[end]");
			text = text.replace(/\&lt;=/g, "[begin] class='operateur'[f]<=[end]");
			text = text.replace(/\&lt;/g, "[begin] class='operateur'[f]<[end]");						
			//---
			text = text.replace(/\{/g, "[begin] class='variable'[f]{");
			text = text.replace(/\}/g, "}[end]");
			//---
			text = text.replace(/\"([\s\S]*?)\"/g, '[begin] class=\'chaine\'[f]"$1"[end]');	
			//---
			text = text.replace(/\[begin\]/g, "<span");
			text = text.replace(/\[f\]/g, ">");
			text = text.replace(/\[end\]/g, "</span>");
			$('#area_color').html(text);						    
		});
				
		// supprime les doublons
		$('.operateur','#area_color').each(function() {
			if( $(this).parent().attr('class') == 'chaine' ) {
				$(this).before($(this).html());
				$(this).remove();
			}
		});	
		
		// supprime les doublons
		$('.variable','#area_color').each(function() {
			if( $(this).parent().attr('class') == 'chaine' ) {
				$(this).before($(this).html());
				$(this).remove();
			}
		});				
	}	
	//-- syntax color

		// Filtre des fonctions pour les formules
		$( '#filter' ).change(function() {
			var cat = $("select[name='filter_functions'] > option:selected").attr("data-type");

			if( cat >= 1 ) {
				$('.func','#functions').each(function(){
				  if( $(this).attr("data-type") != cat ) {
				  	$(this).fadeOut(200);
				  }
				  else {
				  	$(this).fadeIn(200); 
				  }	
				});			
			}
			else {
				$('.func','#functions').fadeIn(200); 	
			}	
		});	

		$( "#test" ).on( "click", function(){
			recup_formule();
		});
		
		
		function openFormula() {
			// Dialogue formule ouverture
			$( ".formule" ).on( "click", function(){
				
				var li = $( this ).parent().parent(); // block cible
	
				var champ_nom = $( this ).parent().parent().parent().find( "h1" ).text(); //nom du champ cible
				
				$("li.ch", li ).each(function(){
					$( '#champs_insert' ).append( '<option value="'+$( this ).attr('value')+'">'+$( this ).text()+'</option>' );
				});
					
				// récupération de la formule existante si rééouverture
				var formuleExistante = $('#formule_'+$.trim(champ_nom) ).text();
				$('#area_insert').val( formuleExistante );
				//colorationSyntax();
				//theme(style_template);	
				$( '#formule_table' ).empty();				
				$( '#formule_table' ).append( champ_nom ); // nom du champ en titre	
				
				$("#formule").dialog({
					width: 'auto',
					height:'auto',
				    draggable: false,
				    modal: true,
				    resizable: false,
					close: function( event, ui ) {
						$( '#champs_insert option' ).remove();
						$('#area_insert').val('');
						$( '#formule_table' ).empty();
					}				    								
				});		
			});			
		}
		
		openFormula();
		
		// Ajouter un champ dans la zone de formule
		$( "#champs_insert" ).on( "dblclick", 'option', function(){		
			var position = $("#area_insert").getCursorPosition();
			var content = $('#area_insert').val();
			var newContent = content.substr(0, position) + '{' + $.trim($( this ).attr('value')) + '}' + content.substr(position);
			$('#area_insert').val(newContent);
			colorationSyntax();
			theme(style_template);				
		});			

		// Ajouter une fonction dans la zone de formule
		$( "#functions" ).on( "dblclick", 'li', function(){		
			var position = $("#area_insert").getCursorPosition();
			var content = $('#area_insert').val();
			var newContent = content.substr(0, position) + $.trim($( this ).text()) + '( ' + content.substr(position);
			$('#area_insert').val(newContent);
			colorationSyntax();
			theme(style_template);				
		});				

		// Btn clear dialogue formule
		$( "#area_eff" ).on( "click", function(){
			$('#area_insert').val('');
			$('#area_color').empty();
		});
		
		// Btn fermer la doite de dialogue
		$( "#area_quit" ).on( "click", function(){
			$( "#formule" ).dialog("close");		
		});

		// Btn confirmation dialogue formule
		$( "#area_confirm" ).on( "click", function(){
			
			// Avant de confirmer la formule il faut la valider
			var myFormula = $( '#area_insert' ).val(); // l'id du champ
			var zone = $( this ).parent().parent().find( "#formule_table" ).text();
			
			$.ajax({
			    type: "POST",
				url: "formula/",
				data:{
  					formula : myFormula
				},
				success: function(error){
					if(error == 0) {
						
						zone = $.trim(zone);

						$( '#formule_'+zone+' li' ).remove();
						$( '#formule_'+zone ).append( '<li>' + myFormula + '</li>' );
						$( "#formule" ).dialog("close");							// Aucune erreur
					}
					else {
						alert( formula_error );
					}
				 }
			});				

		});


		// 
		$( "button","#source_info" ).on( "click", function(){			
			var position = $("#area_insert").getCursorPosition();
			var content = $('#area_insert').val();
			var newContent = content.substr(0, position) + '"' + $.trim($('select',"#source_info").val()) + '"' + content.substr(position);
			$('#area_insert').val(newContent);
			colorationSyntax();
			theme(style_template);			
		});
		
		// 
		$( "button", "#target_info" ).on( "click", function(){
			var position = $("#area_insert").getCursorPosition();
			var content = $('#area_insert').val();
			var newContent = content.substr(0, position) + '"' + $.trim($('select',"#target_info").val()) + '"' + content.substr(position);
			$('#area_insert').val(newContent);
			colorationSyntax();
			theme(style_template);	
		});



}
// ---- FORMULE ------------------------------------------------------------


// ---- SIMULATION DE DONNEES ------------------------------------------------------------

	// Avant la validation du formulaire on peut simuler les données pour contrôler le résultat
	$( "#validation_simulation" ).on( "click", function(){
	
		if( require() ){	
			
			$.ajax({
			    type: "POST",
				url: "simulation/",
				data:{
					champs : recup_champs(),
  					formules : recup_formule(),
  					params : recup_params(),
  					relations : recup_relation()
				},
				beforeSend:	function() {
					$('#simulation_tab').html( '<span class="glyphicon glyphicon-info-sign"></span> ' + data_wait );									
				},	    					
				success: function(data){
					
					if(data == 0) {
						$('#simulation_tab').html('error');	
					}
					else {
						$('#simulation_tab').html(data);	
					}
				 }
			});					
		} 
	});
// ---- SIMULATION DE DONNEES ------------------------------------------------------------

// ---- AJOUT CHAMP CIBLE  ---------------------------------------------------------------

function existField(name) {
	result = 0;
	$( '#targetfields' ).children( 'div' ).each(function(){
		
		if( $(this).attr('id') == $.trim(name) ) {		
			result++;
		}	
	});
	
	return ((result == 0) ? true : false );
}

$( '#addField' ).click(function(){	
	 $( "#formatfield" ).toggle( "fadeIn", function() {});
});

$('#saveBtnField').click(function(){
	newfield = $('#formatfield input').val();
	newtype = $('select','#formatfield').val();
	fields =  htmlentities(removeSpace(newfield) + '_' + newtype);
	
	if(newfield != '' && existField( fields )) {
		box = '<div id="'+ fields + '" class="champs" data-show="true"><h1 class="nom ui-widget-header">'+ fields + '</h1><div class="ui-widget-content" data-show=""><ol class="ui-droppable ui-sortable"><li class="placeholder">'+placeholder+'</li></ol><ul><li id="formule_'+ fields + '" class="formule_text"></li></ul><p><input class="formule btn-mydinv" type="button" value="'+formula_create+'"></p></div></div>';
		$( '#targetfields' ).append( box );
		$('#formatfield input').val('');
	}	
	
	prepareDrag(); // Permet de faire un drag n drop
	openFormula(); // Permet d'ouvrir la boite de dialogue des formules
	hideFields(); // Filtre les champs
		
});




// ---- /AJOUT CHAMP CIBLE  --------------------------------------------------------------


// ---- FILTRES DES CHAMPS CIBLE  --------------------------------------------------------

function hideFields() {
	
	value = $('#hidefields').val();
	
	if(value != '') {
		var show = [];
		$( '#targetfields' ).children( 'div' ).each(function(){
			if( $(this).attr('id').toLowerCase().indexOf(value.toLowerCase()) >= 0 ) {
				show.push( $(this).attr('id') );
			}
			if( $(this).attr('data-show') != '' ) {
				if($(this).attr('data-title') != 'true') {
					if( verifFields($(this).attr('id'), show) ) {
						$(this).attr('data-show','true');
						$(this).show();			
					}
					else {
						$(this).attr('data-show','false');
						$(this).hide();
					}					
				}
			}
			
		});			
	}
	else {
		$( '#targetfields' ).children( 'div' ).each(function(){
			if( $(this).attr('data-show') != '' ) {
				$(this).attr('data-show','true');
				$(this).show();				
			}
		});
	}	
}

	$( '#hidefields' ).keyup(function() {	
		hideFields();
	});	



function verifFields(field_id,show) {
	r = 0;
	$.each(show, function(k,val) {	
		if( val == field_id) {
			r++;
		}
	}); 
	
	return ((r==0) ? false : true);
}
// ---- FILTRES DES CHAMPS CIBLE  --------------------------------------------------------

// ---- FILTRES  -------------------------------------------------------------------------
	// Ajoute un champ
	function addFilter(field,path) {
		
		// ajoute un champ uniquement s'il n'existe pas
		if(existeFilter(field) == 0 ) {
			if(field != 'my_value') {
				$('#fieldsfilter').append('<li id="filter_' + field + '"><span class="name">' + field + '</span> <a class="fancybox" data-fancybox-type="iframe" href="'+ path + 'source/' + field +'/"><span class="glyphicon glyphicon-question-sign"></span></a> <select class="filter">' + filter_liste + '</select><input type="text" value="" /> </li>');	
			}
		}
	}
	// test si le champ existe déjà
	function existeFilter(field) {
		view = 0;
		$( '#fieldsfilter' ).find( "span.name" ).each(function(){
			if( $.trim($(this).attr('value')) == field ){
				view++;
			}
		});
		
		return view;				
	}
	// Supprime un champ
	function removeFilter(field) {

		view = 0;
		$( '#cible' ).find( "li.ch" ).each(function(){
			
			if( $( this ).attr('value') == field ) {
				view++;
			}				
		});
		
		if(view < 2) {
			$('#filter_' + field).remove();
		}
	}			
// ---- FILTRES  -------------------------------------------------------------------------

// ---- PARAMS ET VALIDATION  ------------------------------------------------------------
			
	// Récupère la liste des filtres
	function recup_filter() {
		filter = [];	
		$( 'li','#fieldsfilter' ).each(function(){
			
			field_target ='';
			$( $(this) ).find( "span.name" ).each(function(){
				field_target = $.trim($(this).text());
			});
			
			field_filter = '';
			$( $(this) ).find( "select.filter" ).each(function(){
				field_filter = $.trim($(this).val());
			});	
			
			field_value = '';
			$( $(this) ).find( "input" ).each(function(){
				field_value = $.trim($(this).val());
			});											
			if(field_filter != '') {
				filter.push( {target: field_target, filter: field_filter, value: field_value } );
			}
		});
		
		return filter;
	}
			
	// Récupère tous les champs	
	function recup_champs() {
	
		var resultat="";
		
		$( '#cible' ).find( "li.ch" ).each(function(){
			
			var li = $( this );
			var fields = li.parent().parent().parent();
			var r = $( fields ).find( "h1" ).text();
		
			resultat += $.trim(r)+'[=]'+$.trim(li.attr('value'))+';';
			
		});
		
		return resultat;
	}
	
	// Récupère toutes les formules
	function recup_formule() {
	
		var resultat="";
		
		$( '#cible' ).find( ".formule_text li" ).each(function(){
			
			var formule = $( this );
			var test = formule.parent().parent().parent().parent();
			
			var r = $( test ).find( "h1" ).text();

			resultat += $.trim(r)+'[=]'+formule.text()+';';
			
		});
		
		return resultat;
	}			
	
	// Récupère la liste des relations
	function recup_relation() {
		var relations = [];	
		var parent_relations = [];	

		$( '.rel tr.line-relation','#relation' ).each(function(){			
			tr = $(this);		
			$( $(this) ).find( ".title" ).each(function(){							
				var name = $( this ).attr('data-value');
				var valueRule = tr.find('.lst_rule_relate').val();
				var valueSource = tr.find('.lst_source_relate').val();
				var valueparent = 0;
				if( valueRule != '' && valueSource != '' ) {
					relations.push( {target: name, rule: valueRule, source: valueSource, parent: valueparent} );
				}

			});
		});
		
		$( '.rel tr.line-parent_relation','#relation' ).each(function(){
			tr = $(this);	
			$( $(this) ).find( ".parent_search_field" ).each(function(){						
				var name = tr.find('.parent_search_field').val();
				var valueRule = tr.find('.parent_rule').val();
				var valueSource = tr.find('.parent_source_field').val();
				var valueparent = 1;
				
				if( valueRule != '' && valueSource != '' && name != '' ) {
					relations.push( {target: name, rule: valueRule, source: valueSource, parent: valueparent} );
				}

			});
		});
		
		return relations;
	}						

	// Récupère la liste des params
	function recup_params() {	
		var params = [];	
		$( '.params','#params' ).each(function(){

            var name = $(this).attr('name');
            var value = $(this).val();	
			
			params.push( {name: name, value: value} );
		});
		
		return params;
	}	

	function recup_fields_relate() {
		var relate_fields = [];	
		$( '#fields_duplicate_target li' ).each(function(){
			if( $(this).attr('data-active') == 'true' ) {
				relate_fields.push( $.trim( $(this).text() ) );
			}
		});
		
		return relate_fields;
	}
	
	function duplicate_fields_error() {
		error=0;
		$( 'li','#fields_duplicate_target' ).each(function(){
			if( $(this).attr('class') == 'no_active' ) {
				error++;
			}
		});
		
		return ((error==0) ? true : false);
	}

	// vérifie les champs obligatoires
	function require() {
		// We don't test the fields anymore because it block rule in update only for example
		return true;
	}

	// Liste le nombre d'erreurs de champs require pas remplis
	function require_params() {
		
		var r = 0;
		$( '#params').find( ".require" ).each(function(){
			if($( this ).val() == "") {
				r++;	
			}
		});

		if(r==0) {
			return true;
		}
		else
			{ return false;	}
	}	
	
	// Détecte les relations non remplis
	function require_relate() {
		// We don't test the fields anymore because fields relate required could be filled in the field mapping
		return true;				
	}

	// test si le champ à été selectionné pour pouvoir être utilisé comme référence afin d'éviter les doublons
	function fields_exist(fields_duplicate) {
		var exist = 0;
		$( '#cible' ).find( "li.ch" ).each(function(){
			
			var li = $( this );
			var fields = li.parent().parent().parent();
			var r = $.trim( $( fields ).find( "h1" ).text() );
								
			if(fields_duplicate == r) {
				exist++;
			}
		});
		
		return exist;			
	}

	// Affiche les champs obligatoire pour éviter les doublons
	$( '#fields_duplicate_target' ).on( "click", 'li', function(){	
		
		// si le champ est sélectionné
		if(fields_exist($(this).text())) {
			
			if( $(this).attr('data-active') == 'false' ) {
				$(this).attr('data-active','true');
				$(this).addClass('active');
			}
			else {
				$(this).attr('data-active','false');
				$(this).removeClass('active');					
			}					
		}
		else {
			if( $(this).attr('class') == 'no_active' ) {
				$(this).removeClass('no_active');
			}
			else {
				$(this).addClass('no_active');					
			}									
		}

		recup_fields_relate();
	});

	// Validation et vérification de l'ensemble du formulaire			
	$( "#validation", '#rule_mapping' ).on( "click", function(){
		
		before = $( "#validation" ).attr('value'); // rev 1.08

		if(require() && require_params() && require_relate() && duplicate_fields_error() ){	
			
			$.ajax({
			    type: "POST",
				url: "validation/",
				data:{
					champs : recup_champs(),
  					formules : recup_formule(),
  					params : recup_params(),
  					relations : recup_relation(),
  					duplicate : recup_fields_relate(),
  					filter : recup_filter(),
				},
				beforeSend: function() {
					$( "#validation" ).attr('value',save_wait); // rev 1.08
				},				
				success: function(data){
					
					if(data == 1) {
						alert(confirm_success);
						
						$(location).attr('href',return_success);
					}				
					else {
						
						data = data.split(';');
						if( data[0] == 2 ){
							alert(data[1]);	// Erreur personnalisé via beforeSave
						}
						else {
							alert(confirm_error);
						}
						
						$( "#validation" ).attr('value',before); // rev 1.08
					}
				}, // rev 1.08
			    statusCode: {
			        500: function() {
			            alert('Internal Server Error (500)');
			            $( "#validation" ).attr('value',before); // rev 1.08
			        }
			    }				 
			});					
		}
		else
			{
				//alert('Il existe des champs obligatoires !');
				 $("#dialog").dialog({
				    draggable: false,
				    modal: true,
				    resizable: false
				});
				
				$( "#validation" ).attr('value',before); // rev 1.08				
			}
	});
			
// ---- PARAMS ET VALIDATION  ------------------------------------------------------------


// ---- RELOAD  --------------------------------------------------------------------------

if ( typeof fields !== "undefined" && typeof params !== "undefined" && typeof relate !== "undefined") {

	// Fields
	if(fields) {
		
		$.each(fields, function( index, nameF ) {
			// fields
			$('#' + nameF.target + ' .ui-droppable').empty();
					
			$.each(nameF.source, function( fieldid, fieldname ) {
				$('#' + nameF.target + ' .ui-droppable').append('<li value="' + fieldid + '" class="ch">' + fieldname + '</li>');
				
				// filter
				addFilter(fieldid,path_info_field);
			});
							
			// formula
			if(nameF.formula != null) {
				$('#formule_' + nameF.target).append('<li>' + nameF.formula + '</li>');
			}	
		});			
	}
	
	// Filter	
	if(filter) {
		$.each(filter, function( index, nameF ) {
			$('select','#filter_' + nameF.target).val( nameF.type );
			$('input','#filter_' + nameF.target).val( nameF.value );
		});
	}
	
	// Params
	if(params) {	
		$.each(params, function( index, nameP ) {
			$( '#'+nameP.name ).val( nameP.value );		
			if( nameP.name + 'duplicate_fields' ) {
				duplicate_fields = nameP.value.split(';');
				$.each(duplicate_fields, function( index, d_fields ) {
					$( "li:contains('" + d_fields + "')", '#fields_duplicate_target').click();	
				});				
			}
		});	
	}
	// Relate
	if(relate) {	
		var cpt = 0;
		// We fill the differents field depending if the rule is a parent one or not
		$.each(relate, function( index, nameR ) {	
			if (nameR.parent == 0) {
				$('#lst_'+ nameR.target).val( nameR.id );
				$('#lst_source_'+ nameR.target).val( nameR.source );						
			} 	else {	
				$('#parent_rule_'+ cpt).val( nameR.id );
				$('#parent_source_field_'+ cpt).val( nameR.source );						
				$('#parent_search_field_'+ cpt).val( nameR.target );									
				cpt++;		
			}
		});
	}
	
}
// ---- RELOAD  --------------------------------------------------------------------------


// ---- FLUX  --------------------------------------------------------------------------

	var massFluxTab = [];
	showBtnFlux();
	
	function showBtnFlux() {
								
		if( massFluxTab.length == 0) {
			$('#cancelflux').hide();
			$('#reloadflux').hide();		
		}
		else {
			$('#cancelflux').show();
			$('#reloadflux').show();				
		}				
	}
	
	$('#massselectall').change(function() {	
		if ( $(this).is( ":checked" ) ){
			remove  = false;
		}
		else {
			remove = true;	
		}
		
		$('input','.listepagerflux td').each(function(){
			if( $(this).attr('disabled') != 'disabled' ) {
				if ( $(this).is( ":checked" ) ){					
					if(remove) {
						id = $(this).parent().parent().attr('data-id');
						massAddFlux( id,  true);
						$(this).prop( "checked", false );
					}
				}
				else {
					if(remove == false) {
						id = $(this).parent().parent().attr('data-id');
						massAddFlux( id,  false);
						$(this).prop( "checked", true );
					}
				}
			}	
		});		
		
		showBtnFlux();			
	});

	$('input','.listepagerflux td').change(function() {		
		id = $(this).parent().parent().attr('data-id');
		if ( $(this).is( ":checked" ) ){
			massAddFlux( id,  false);
		}
		else {
			massAddFlux( id,  true);
		}
		
		showBtnFlux();
	});
	
	$('#cancelflux').click(function(){
		if (confirm(confirm_cancel)) { // Clic sur OK
			$.ajax({
				type: "POST",
				url: mass_cancel,
				beforeSend: function() {
					btn_action_fct(); // Animation
				},
				data:{
					ids: massFluxTab
				},
				success: function(data){ // code_html contient le HTML renvoyé
					location.reload();
				}							
			});
		}		
	});
	
	$('#reloadflux').click(function(){
		if (confirm(confirm_reload)) { // Clic sur OK
			$.ajax({
				type: "POST",
				url: mass_run,
				beforeSend: function() {
					btn_action_fct(); // Animation
				},
				data:{
					ids: massFluxTab
				},
				success: function(data){ // code_html contient le HTML renvoyé
					location.reload();
				}
			});
		}
	}); 
	
	function massAddFlux(id, cond) {		
		if(id != '') {		
			if(cond==false) {
				massFluxTab.push( id );	
			}
			else {
				massFluxTab.splice($.inArray(id, massFluxTab),1);
			}			
		}	
		
		$('#cancelflux').find('span').html( massFluxTab.length );
		$('#reloadflux').find('span').html( massFluxTab.length );
	}
		
	$( "#flux_target" ).on( "dblclick", 'li', function(){	
		
		if($('#gblstatus').attr('data-gbl') == 'error' || $('#gblstatus').attr('data-gbl') == 'open') {
			verif = $(this).attr('class');	
			first = $('li:first','#flux_target').attr('class');
			classe = $(this).attr('class');	
			
			if( typeof verif !== "undefined" && first === "undefined" != classe !== "undefined" ) {
				
				value = $(this).find('.value').text();					
				$(this).find('.value').remove();
				$(this).append('<input id="' + classe + '" type="text" value="'+value+'" /><div data-value="' + classe + '" class="btn-group btn-group-xs"><span class="glyphicon glyphicon-ok cursor"></span><span class="load"></span></div>');							
			}			
		}
				
	});	

	$( "#flux_target" ).on( "click", 'div', function(){	
		saveInputFlux( $(this),inputs_flux );
	});
			
});

function saveInputFlux(div,link) {
	
	fields = div.attr('data-value');
	div.attr('data-value');
	value = $('#'+fields);	

	$.ajax({
		type: "POST",
		url: link,
		data:{
			flux: $('#flux_target').attr('data-id'),
			rule: $('#flux_target').attr('data-rule'),
			fields: fields,
			value: value.val()
		},							
		success: function(val){
			div.parent().append( '<span>' + val + '</span>' );
			div.remove();
			value.remove();	
		}			
	});
}