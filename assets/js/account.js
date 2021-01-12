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
    
    var path_locale = Routing.generate('account_locale');
	
	positionningPopup('#myd_change_locale','#user_account');
	
    /******
     *	BOUTONS CHANGEMENT DE LANGUE 
     */
	
	$('#btn-locale-en','#user_account').click(function() {
		if(!$("#btn-locale-en").is(".disabled")) {
			$.ajax({
				type: "POST",
				url: path_locale,
				data:{
					locale : "en"
				},
				beforeSend: function(){
					$('#myd_change_locale','#user_account').fadeIn();
				},
				success: function(){
					document.location.reload(true)
				}
			});
		}	    	
    });
    
	$('#btn-locale-fr','#user_account').click(function() {
		if(!$("#btn-locale-fr").is(".disabled")) {
			$.ajax({
				type: "POST",
				url: path_locale,
				data:{
					locale : "fr"
				},
				beforeSend: function(){
					$('#myd_change_locale','#user_account').fadeIn();
				},
				success: function(){
					document.location.reload(true)
				}
			});
		}	
    });
});

// Positionne les différentes Popup au centre de la page en prenant en compte le scroll
function positionningPopup(id, idcontainer) {
	// Positionnement du bloc transparent (qui permet d'éviter les clics le temps de la popup)
	$(id,idcontainer).css({
		"height": $(document).height(),
		"width": $(document).width(),
	});
	
	// Positionnement initial (au centre de l'écran) + scroll
	$(id + "> .user_account_popup_content",idcontainer).css({
		"top": $(window).height()/2 - 200 + $(window).scrollTop(),
		"left": $(window).width()/2 - 150,
	});
	
    // On déclenche un événement scroll pour mettre à jour le positionnement au chargement de la page
    $(window).trigger('scroll');
 
    $(window).scroll(function(event){
		$(id + "> .user_account_popup_content",idcontainer).css({
			"top": $(window).height()/2 - 200 + $(window).scrollTop()
		});
    });
	
	// Repositionnement si resize de la fenêtre
	$(window).resize(function(){
		$(id,idcontainer).css({
			"height": $(document).height(),
			"width": $(document).width(),
		});
		
		$(id + "> .user_account_popup_content",idcontainer).css({
			"top": $(window).height()/2 - 200 + $(window).scrollTop(),
			"left": $(window).width()/2 - 150,
		});
	});	
}
