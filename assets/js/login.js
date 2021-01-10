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

$(document).ready(function() {

	$('#myd_connexion .load').hide();
	$('#_error').hide();


	$("#control").one( "click", function() { 
		if( $('#password').val() != '' && $('#username').val() != '') {     	        		        
	        controleLogin();           
	    }
	} );

	$('form').keypress(function(e){
	    if( e.which == 13 ){
	    	if( $('#password').val() != '' && $('#username').val() != '') {
	    		$("#control").click();
	    		return false;
	    	} else {
	    		return false;
	    	}
	    }
	}); 
    
});
 

function controleLogin() {	
	
	$.ajax({
		type: "POST",
		url: path_control,
		data:{
			login : $('#username').val()
		},	
		beforeSend:	function() {
			//$('#_error').fadeOut();
        	//$('#control').hide();
        	//$('#_submit').show();							
		},											
		success: function(data){				
            if(data == 1) {	
            	$('#_error').fadeOut();
            	$('#_submit').click();
            }
            else {
            	$('#control').show();
            	$('#_error').show();
            	$('#_submit').hide();		
            } 	
		 }
	});	
}
