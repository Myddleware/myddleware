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

$(function() {
	
	$('.rule-create-submit').hide();



	// Initialisation des progressBar (à leurs positions initiales)
    progressBar(0, $('#animation-Bar1'), 0);
    progressBar(0, $('#animation-Bar2'), 0);
    progressBar(0, $('#animation-Bar3'), 0);
    progressBar(0, $('#animation-Bar4'), 0);
    
    // Positionne le logo Myddleware
    posMyddleware();

	// Resize de window = Repositionnement du Logo
    $(window).resize(function() {
      posMyddleware();
    });
    
	// Clic sur une solution source -> toSource (affichage de la solution sur le cylindre source)
    $("img[class^='animation-solution-source']").on('click',function(e){
    	toSource(e,$(this));
    });
    
    // Clic sur une solution cible -> toCible (affichage de la solution sur le cylindre cible)
    $("img[class^='animation-solution-cible']").on('click',function(e){
    	toCible(e,$(this));
    });	

	// Clic sur les boutons d'affichage = toggle des 2 carrousels
    $(".animation-content-left-toggle-btn").one('click', content_left_toggle);
    $(".animation-content-right-toggle-btn").one('click', content_right_toggle);  
    
    $('#btn_validation').on('click', function(){
    	animValidation();
    });    
    
 	// Fermeture de la fancybox
	$(".fancybox_animation").fancybox({
	    'beforeClose': function() { 
	    	addSolution();
	    },
	    'closeClick': true
	 }); 
	 
	 // Cache les éléments au chargement de la page
	 hideLoad();
	 
	 // Affiche la liste des modules si le connecteur est sélectionné
	 addModule('source');
	 addModule('cible');
	 
	 // Contrôle le nom de la règle
	 $( '#rulename' ).on('keyup', function() {
	 	controleRuleName($(this).val());
	 });
	 	  
	 // Traitement bouton choix
	 $('button','#choice').on('click', function(){
	 	
	 	choice = $(this).attr('id');
	 	
	 	if(choice == 'btn_module') {
	 		$('#validation').hide();
	 		$(this).attr('class','btn btn-outline-success btn-lg');
	 		$('#btn_template').attr('class','btn btn-outline-secondary btn-lg');
	 		
	 		$('#module').fadeIn(1000);	
			$('#template').fadeOut(1000);	 
			$('.rule-create-submit').show();
	 		$('#btn_validation').html(trans_btn_mapping);
	 	}
	 	else if(choice == 'btn_template') {
	 		
	 		$(this).attr('class','btn btn-outline-success btn-lg');
	 		$('#btn_module').attr('class','btn btn-outline-secondary btn-lg');	 		
			$('.rule-create-submit').show();
	 		$('#template').fadeIn(1000);	
	 		$('#module').fadeOut(1000);	
	 		$('#submodules').fadeOut(1000);	
	 		$('#btn_validation').html(trans_btn_confirm);
	 	}
	 });		 

	//gray();
		
	// sub modules ex SAP --------------------
	$('#animation-Module-source').change(function() {
		getSubModules('source');
	});

	$('#animation-Module-cible').change(function() {
		getSubModules('cible');
	});	
	// sub modules ex SAP --------------------

});


// Récupère les sousmodules de la solution
function getSubModules(type) {	
	
	op_type = ((type == 'source') ? 'cible' : 'source');
		
	$.ajax({
		type: "POST",	
		data:{
			type : type,
			connector : $('#animation-Connecteur-' + type ).val(),
			module : $('#animation-Module-' + type ).val(),
		},		
		url: path_submodules,						
		success: function(data){		
			if(data == 0) {
				$('#animation-Module-' + type ).attr('data-block',0);
				if($('#animation-Module-' + type ).attr('data-block') == 0 && $('#animation-Module-' + op_type ).attr('data-block') == 0 ) {
					$('#submodules').hide();
					$('#validation').fadeIn(2000);	
				}			
			}
			else {
				$('#animation-Module-' + type ).attr('data-block',1);
				$('#validation').hide();
				$('#submodules').fadeIn(1000);
				$('#submodules').html(data);
			}	
			
			$('.sub_module_selected').on('click',function() {
				
				var field_selected = $(this);
				$(this).addClass('selected');			
						
				id = $(this).find('span').attr('data-id');
				
				if (typeof id === "undefined") {
					id = $(this).find('span').next().attr('data-id');
				}

				$.ajax({
					type: "POST",	
					data:{
						type : type,
						ids : id,
						connector : $('#animation-Connecteur-' + type ).val(),
						module : $('#animation-Module-' + type ).val(),
						select : 1
					},		
					url: path_submodules,						
					success: function(data){						
						if( data == 1 ) {
							$('#validation').fadeIn(2000);	
						}
						else if( data == 2 ) {
							field_selected.removeClass('selected');
						}
					}
				});
				
			});			
		}
	});		
}


// Affiche ou désactive l'arc de cercle
function arc(type, style) {		
if(style) {
	if(type == 'source') {
		s_source = $('#animation-source-container').parent();
		s_source.fadeOut();
		s_source.attr('class','animation-puce arcleft-c');	
		s_source.fadeIn(3500);		
	}
	else {
		s_cible = $('#animation-cible-container').parent();
		s_cible.fadeOut();
		s_cible.attr('class','animation-puce arcright-c');	
		s_cible.fadeIn(3500);		
	}	
}
else {
	if(type == 'source') {
		s_source = $('#animation-source-container').parent();
		s_source.fadeOut();
		s_source.attr('class','animation-puce arcleft-g');	
		s_source.fadeIn(3500);		
	}
	else {
		s_cible = $('#animation-cible-container').parent();
		s_cible.fadeOut();
		s_cible.attr('class','animation-puce arcright-g');	
		s_cible.fadeIn(3500);		
	}	
}	
	

}


// Actualise l'écoute des solutions
function listenSolution() {
	// Clic sur une solution source -> toSource (affichage de la solution sur le cylindre source)
    $("img[class^='animation-solution-source']").on('click',function(e){
    	toSource(e,$(this));
    });
    
    // Clic sur une solution cible -> toCible (affichage de la solution sur le cylindre cible)
    $("img[class^='animation-solution-cible']").on('click',function(e){
    	toCible(e,$(this));
    });	
}

// Permet le grayscale sur les images
function gray() {
	
	// Fade in images so there isn't a color "pop" document load and then on window load
	$(".gray").fadeIn(500);
	
	// clone image
	$('.gray').each(function(){
		
		$(this).removeClass('gray');
		classe = $(this).attr('class');
		
		var el = $(this);
		el.css({"position":"absolute"}).wrap("<div class='img_wrapper' style='display: inline-block'>").clone().addClass(classe+' grayout').css({"position":"absolute","z-index":"998","opacity":"0"}).insertBefore(el).queue(function(){
			var el = $(this);
			el.parent().css({"width":this.width,"height":this.height});
			el.dequeue();
		});
		

      	this.src = grayscale(this.src);
		
	});	
	
	listenSolution();
    
	// Fade image 
	$('.grayout').on('mouseout', function(){
		//$(this).parent().find('img:first').stop().animate({opacity:1}, 1000);
		$(this).stop().animate({opacity:0}, 1000);
	})
	$('.grayout').on('mouseover', function(){
		$(this).parent().find('img:first').stop().animate({opacity:1}, 1000);
	});    
    	
}
	
// Listes non visibles au début
function hideLoad() {
	
	 $('#choice').hide(); 
	 $('#module').hide(); 
	 $('#template').hide();
	 $('#submodules').hide();
	 $('#validation').hide();	 
	 
	 $('#animation-Connecteur-source').hide();	
	 $('#animation-Connecteur-cible').hide();
	 //--
	 $('#animation-Module-source').hide();	
	 $('#animation-Module-cible').hide();	
	 //-- 
	 $('#connector-source-error').hide();
	 $('#connector-cible-error').hide();
	 //--
	 $('#connector-source-success').hide();
	 $('#connector-cible-success').hide();	 
	 //--	 
	 $('.loader-source').hide();
	 $('.loader-cible').hide();
	 //--		
}

// Liste des templates
function addTemplate() {
	$.ajax({
		type: "POST",
		url: path_template,						
		success: function(data){
			$('#template').html(data);	
			selectTemplate();	
		}
	});						
}

function selectTemplate() {
    $('#template tbody tr').on('click',function(){  	
    	$('#template tr').removeAttr('class');  	
	 	$(this).addClass('info');
	 	$('#validation').fadeIn(1000);
    });	
}

// Affiche les choix quand source et cible sont OK
function addChoice() {
	
	source = $('img','#animation-source').attr('data-send');
	cible = $('img','#animation-cible').attr('data-send');

	// si la source et cible sont OK
	if(source == 'success' && typeof source !== "undefined" 
	&& cible == 'success'  && typeof cible !== "undefined"  ) {
		
		addTemplate();

		$('#btn_module').attr('class','btn btn-outline-secondary btn-lg');
		$('#btn_template').attr('class','btn btn-outline-secondary btn-lg');
		
		$('#choice').fadeIn(2000);		
	}
	else {
		hideElements();
	}
	
}

// Affiche la liste des modules si le connecteur est sélectionné
function addModule(type) {
	$('#animation-Connecteur-' + type).on('change', function() {
		$('#connector-' + type +'-error').hide();
		$('#connector-' + type +'-success').hide();	 	
		$('#animation-Module-' + type).hide();	
		$('.loader-' + type).hide();
		value = $(this).val();
		if(value != '') {
	 		// Récupère la liste des modules
			$.ajax({
				type: "POST",
				url: path_module,	
				data:{
					type : type,
					id : value,
				},	
				beforeSend:	function() {
					$('.loader-' + type).show();
					$('#animation-Connecteur-'+ type).attr('disabled','disabled');	
				},
				success: function(data){	
					$('#animation-Connecteur-'+ type).removeAttr('disabled');
					r = data.split(';');
					imgCount = $('img','#animation-'+type).attr('data-count');
					
					if ( r.length > 1 ) { // error
						$('#connector'+type+'-success').hide();
						id = '#connector-'+type+'-error';
						$(id).html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation" viewBox="0 0 16 16"><path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.553.553 0 0 1-1.1 0L7.1 4.995z"/></svg> '+r[0]).fadeIn(1000);
						
						if( typeof imgCount !== "undefined" ) {
							
							if(imgCount>0) {	
								$('img','#animation-'+type).removeAttr('data-count'); 							
								((type == 'source') ? source_toggle() : cible_toggle() );							
							}
						}
						
						$('img','#animation-'+type).removeAttr('data-send');	
						addChoice();	
						setTimeout(function(){
				        	arc(type, false);	
				        }, 4000); 				
					}
					else { // success
						arc(type, true);
						$('#animation-Module-' + type).empty();
						$('#connector'+type+'-error').hide();
						id = '#connector-'+type+'-success';
						$(id).html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left-right" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 11.5a.5.5 0 0 0 .5.5h11.793l-3.147 3.146a.5.5 0 0 0 .708.708l4-4a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 11H1.5a.5.5 0 0 0-.5.5zm14-7a.5.5 0 0 1-.5.5H2.707l3.147 3.146a.5.5 0 1 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 4H14.5a.5.5 0 0 1 .5.5z"/></svg> Success').fadeIn(1000);						
						$('#animation-Module-' + type).append(data);
						$('#animation-Module-' + type).fadeIn(1000);	

						if( typeof imgCount !== "undefined" ) {
							imgCount++;
							$('img','#animation-'+type).attr('data-count',imgCount)
						}
						else {
							imgCount = 1;
							$('img','#animation-'+type).attr('data-count',imgCount)
						}
						if(imgCount==1) {	
												
							((type == 'source') ? source_toggle() : cible_toggle() );							
						}
						
						$('img','#animation-'+type).attr('data-send','success');	
						addChoice();
					}
					
					$('.loader-' + type).hide();
				}
			});	 		
		} else {
			$('img','#animation-'+type).removeAttr('data-count'); 	
			$('img','#animation-'+type).removeAttr('data-send');		
			((type == 'source') ? source_toggle() : cible_toggle() );	
			$('#animation-Module-' + type).hide();	
		}	 	
	});	
}

// Ajoute les solutions manquantes
function addSolution() {
	$.ajax({
		type: "POST",
		url: path_new_solution,						
		success: function(data){		
			if(data != 0) {
				values = data.split(';');
				// 0 : source 1 : solution : 2 multi 3 : id
				if ( typeof values[0] !== "undefined" && typeof values[1] !== "undefined" && typeof values[2] !== "undefined") {
					
					if(values[0] == 'source') {	
						plus = $('.clr','#animation-cleft').find('.fancybox_animation').parent();
					}
					else {
						plus = $('.clr','#animation-cright').find('.fancybox_animation').parent();
					}
					
					content = '<li><img class="animation-solution-'+ values[0] +' grayout" alt="'+ values[1] +'" src="'+ path_img + 'solution/' + values[1] +'.png" data-solution="'+values[1]+'" data-id="'+values[3]+'"/></li>';
					$(content).insertAfter(plus).fadeIn(1000);
					
					if(values[2] == 2) {
						if(values[0] == 'source') {							
							plus = $('.clr','#animation-cright').find('.fancybox_animation').parent();
							content = '<li><img class="animation-solution-cible grayout" alt="'+ values[1] +'" src="'+ path_img + 'solution/' + values[1] +'.png" data-solution="'+values[1]+'" data-id="'+values[3]+'"/></li>';
							$(content).insertAfter(plus).fadeIn(1000);						
						}
						else {
							plus = $('.clr','#animation-cleft').find('.fancybox_animation').parent();
							content = '<li><img class="animation-solution-source grayout" alt="'+ values[1] +'" src="'+ path_img + 'solution/' + values[1] +'.png" data-solution="'+values[1]+'" data-id="'+values[3]+'"/></li>';
							$(content).insertAfter(plus).fadeIn(1000);
						}
					}					
				}
				
				listenSolution();			
			} // end success
		}			
	});
}

// Ajoute la liste des connecteurs en fonction de la solution choisi
function addConnector(type,solution) {
	
	pos = ((type == 'source') ? 'left' : 'right' );
	
	$('li','#animation-c'+pos).find('img').each(function(){		
		if($.trim($(this).attr('data-solution')) == $.trim(solution)) {
			id = $(this).attr('data-id');
		}
	});		
	
	$.ajax({
		type: "POST",
		url: path_connector_by_solution,	
		data:{
			id : id,
		},				
		success: function(data){	
			$('#animation-Connecteur-' + type).empty();
			$('#animation-Connecteur-' + type).append(data);
			$('#animation-Connecteur-' + type).fadeIn(1000);	
		}
	});
	
	$('.loader-' + type).hide();
	$('#animation-Connecteur-'+ type).removeAttr('disabled');
	$('#btn_module').attr('class','btn btn-light btn-lg');
	$('#btn_template').attr('class','btn btn-light btn-lg');
	hideElements();
}

function hideElements() {
	$('#choice').fadeOut(2000);	
	$('#module').fadeOut(1000);	
	$('#template').fadeOut(1000);	
	$('#submodules').fadeOut(1000);		
	$('#validation').fadeOut(1000);		
}


// Validation générale
function animValidation() {		
	
	rulename = $('#nameverif').find('div').attr('class');
	
	if(rulename == 'd-flex input-group has-success has-feedback') {
		if( $('#btn_module').attr('class') == 'btn btn-outline-success btn-lg') {
			if( $('#animation-Module-source').val() != '' && $('#animation-Module-cible').val() != '' ) {
				animConfirm('module');
			}		
		}
		else if( $('#btn_template').attr('class') == 'btn btn-outline-success btn-lg' ) {
				animConfirm('template');		
		}	
	} else {
		$('div','#nameverif').attr('class','d-flex input-group has-error has-feedback');
		// Only display 1 icon
		if($('#rulename').siblings()){
			$('#rulename').siblings().each( function(index){
				$(this).hide();
			});
		}
		$('#rulename').after('<i class="fas fa-times form-control-feedback"></i>');
		$('#rulename').focus();
	}

}

function animConfirm(choice_lst) {
	template = '';	
	if(choice_lst == 'template') {
		$('#template').find('tr').each(function(){
			$(this).find('span').each(function(){
				if( $(this).data('id') !== null ){
					template = $(this).data('id');
				}
			});
			// TODO: this doesn't work any more because the class info isn't there so I added the fix 
			// above while we find out why the class info isn't there
			// if($(this).attr('class') === 'info') {
			// 	template = $(this).find('span').attr('data-id');
	
			// }
		});		
	}	
	$.ajax({
		type: "POST",
		data:{
			choice_select : choice_lst,
			name : $('#rulename').val(),
			module_source : $('#animation-Module-source').val(),
			module_target : $('#animation-Module-cible').val(),
			template : template,
		},
		url: path_validation,	
		beforeSend:	function() {	
			$('#btn_validation').empty();
			$('#btn_validation').html(wait);
			$('#btn_validation').removeAttr('id');
		},				
		success: function(data){				
			if(data == 'module') {
				$(location).attr('href',path_mapping);	
			}
			else if(data == 'template') {
				$(location).attr('href',path_listrule);
			}else if (data == 'error'){
				$('#validation').append('<p>There is an error</p>');
			}
		},
		error: function(data, exeption){
			if(data === undefined){
				alert ('Exeption:', exeption);
			}
			
		}
	});	
}

// Contrôle le nom de la règle
function controleRuleName(nameValue) {
	
	if(nameValue.length > 2 ) {
		$.ajax({
			type: "POST",
			url: path_rulename,
			data:{
				name : nameValue
			},							
			success: function(data){
				
				// true
				if( data == 0 ) {
					$('div','#nameverif').attr('class','d-flex input-group has-success has-feedback');
					// Only display 1 icon
					if($('#rulename').siblings()){
						$('#rulename').siblings().each( function(index){
							$(this).hide();
						});
					}
					$('#rulename').after('<i class="fas fa-check form-control-feedback"></i>');
					
				}
				else {	// false				
					$('div','#nameverif').attr('class','d-flex input-group has-error has-feedback');
					// Only display 1 icon
					if($('#rulename').siblings()){
						$('#rulename').siblings().each( function(index){
							$(this).hide();
						});
					}
					$('#rulename').after('<i class="fas fa-times form-control-feedback"></i>');
				}		
			}			
		});
	}
	else {
		$('div','#nameverif').attr('class','d-flex input-group has-normal has-feedback');
	}	
}

// Transfert l'image de la solution choisie au cylindre de la solution source
function toSource(e,solution) {
	
	var src = solution.attr("src");
    var alt = solution.attr("alt");

	// si la même solution existe deja alors on quitte
	if( $('img','#animation-source').attr('alt') == alt ) {
		return false;
	}
	
	$('img','#animation-source').removeAttr('data-count'); 
		
    if(source){
        if(logo)
            animateLogo();
        $("#animation-myddleware-back-2 > .animation-myddleware-logo").animate({ width: 0 }, 2000);
        setTimeout(function(){
            progressBar(0, $('#animation-Bar1'), 2000);
            progressBar(0, $('#animation-Bar2'), 2000);
            setTimeout(function(){source = false}, 2005);
        }, 2000);	
    }    


    if(!(src == $("#animation-source > img").attr("src"))){
        e.stopPropagation();
        $("#animation-source-container").fadeOut(1000);
        setTimeout(function(){$("#animation-source > img").attr("src", src)}, 1000);
        setTimeout(function(){$("#animation-source > img").attr("alt", alt)}, 1000);
        $("#animation-source-container").fadeIn(1000);
        
        addConnector('source',alt);
        // gestion des erreurs
		$('#connector-source-error').hide();
		$('#connector-source-success').hide();
		// gestion des erreurs      
        $('#animation-Module-source').hide();
        $('#animation-Module-source').empty();
    }

}

// Transfert l'image de la solution choisie au cylindre de la solution cible
function toCible(e,solution) {
	
	var src = solution.attr("src");
    var alt = solution.attr("alt");
	
	// si la même solution existe deja alors on quitte
	if( $('img','#animation-cible').attr('alt') == alt ) {
		return false;
	}	
	
	$('img','#animation-cible').removeAttr('data-count');
	
    if(cible){
        if(logo)
            animateLogo();
        $("#animation-myddleware-back-3 > .animation-myddleware-logo").animate({ width: 0 }, 2000);
        setTimeout(function(){
            progressBar(0, $('#animation-Bar3'), 2000);
            progressBar(0, $('#animation-Bar4'), 2000);
            setTimeout(function(){cible = false}, 2005);
        }, 2000);       
    }  

    if(!(src == $("#animation-cible > img").attr("src"))){  	
        e.stopPropagation();
        $("#animation-cible-container").fadeOut(1000);
        setTimeout(function(){$("#animation-cible > img").attr("src", src)}, 1000);
        setTimeout(function(){$("#animation-cible > img").attr("alt", alt)}, 1000);
        $("#animation-cible-container").fadeIn(1000);

        addConnector('cible',alt);
        // gestion des erreurs
		$('#connector-cible-error').hide();
		$('#connector-cible-success').hide();	
		// gestion des erreurs          
        $('#animation-Module-cible').hide();
        $('#animation-Module-cible').empty();
    }
}    

// Anime la connexion de la source à Myddleware
var source = false;
function source_toggle() {

    if(source){
        // if(cible) {
        //     animateLogo();
        // }
        $("#animation-myddleware-back-2 > .animation-myddleware-logo").animate({ width: 0 }, 2000);
        setTimeout(function(){
            progressBar(0, $('#animation-Bar1'), 2000);
            progressBar(0, $('#animation-Bar2'), 2000);
            setTimeout(function(){source = false}, 2005);
        }, 2000);
        
        setTimeout(function(){
        	arc('source', false);	
        }, 4000);        
    } else {
        progressBar(100, $('#animation-Bar1'), 2000);
        progressBar(100, $('#animation-Bar2'), 2000);
        setTimeout(function(){
            source = true;
            $("#animation-myddleware-back-2 > .animation-myddleware-logo").animate({ width: 105 }, 2000)
        }, 2005);
    }
}

// Anime la connexion de la cible à Myddleware
var cible = false;
function cible_toggle() {

    if(cible){
        $("#animation-myddleware-back-3 > .animation-myddleware-logo").animate({ width: 0 }, 2000);
        setTimeout(function(){
            progressBar(0, $('#animation-Bar3'), 2000);
            progressBar(0, $('#animation-Bar4'), 2000);
            setTimeout(function(){cible = false}, 2005);      
        }, 2000);
        
        setTimeout(function(){
        	arc('cible', false);	
        }, 4000);
    } else {
        progressBar(100, $('#animation-Bar3'), 2000);
        progressBar(100, $('#animation-Bar4'), 2000);
        setTimeout(function(){
            cible = true;
            $("#animation-myddleware-back-3 > .animation-myddleware-logo").animate({ width: 105 }, 2000)
        }, 2005);
    }

}

// Anime le Logo Myddleware (non utilisée pour l'instant)
var logo = false;
function animateLogo() {
    if(logo){
        clearInterval(logoAnimation);
        $("#animation-myddleware-back-1").fadeIn(500);
        logo = false;
    } else {
        logoAnimation = setInterval(function() {
            $("#animation-myddleware-back-1").fadeIn(500);
            $("#animation-myddleware-back-1").fadeOut(500);
        }, 1000);
        logo = true;
    }
}

// Function de positionnement du logo Myddleware
function posMyddleware() {
	
	var pos = $("#animation-myddleware-div").position();
	if(typeof pos !== "undefined" ) {    
	    //show the menu directly over the placeholder
	    $("#animation-myddleware-div > div").css({
	        position: "absolute",
	        top: (pos.top - 50 ) + "px",
	        left: (pos.left + 30) + "px"
	    }).show();		
	}
	

}

// Affiche ou désaffiche le carrousel gauche (Responsive)
var content_left = false;
function content_left_toggle(e) {
    if(content_left){
        $(".animation-content-left").animate({ left: -80 }, 1000);
    } else {
        $(".animation-content-left").animate({ left: 0 }, 1000);
    }
}

// Affiche ou désaffiche le carrousel droite (Responsive)
var content_right = false;
function content_right_toggle(e) {
    if(content_right){
        $(".animation-content-right").animate({ right: -80 }, 1000);
    } else {
        $(".animation-content-right").animate({ right: 0 }, 1000);
    }
}

// Anime la progressBar dont l'ID est passé en paramètre
function progressBar(percent, $element, time) {
	var progressBarWidth = percent * $element.width() / 100;
	$element.find('div').animate({ width: progressBarWidth }, time);
}


// Grayscale w canvas method
function grayscale(src){
	var canvas = document.createElement('canvas');
	var ctx = canvas.getContext('2d');
	var imgObj = new Image();
	imgObj.src = src;
	canvas.width = imgObj.width;
	canvas.height = imgObj.height; 
	ctx.drawImage(imgObj, 0, 0); 
	var imgPixels = ctx.getImageData(0, 0, canvas.width, canvas.height);
	
	for(var y = 0; y < imgPixels.height; y++){
		for(var x = 0; x < imgPixels.width; x++){
			var i = (y * 4) * imgPixels.width + x * 4;
			var avg = (imgPixels.data[i] + imgPixels.data[i + 1] + imgPixels.data[i + 2]) / 3;
			imgPixels.data[i] = avg; 
			imgPixels.data[i + 1] = avg; 
			imgPixels.data[i + 2] = avg;
		}
	}
	ctx.putImageData(imgPixels, 0, 0, 0, 0, imgPixels.width, imgPixels.height);
	return canvas.toDataURL();
}
