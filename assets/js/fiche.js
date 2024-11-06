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
const $ = require('jquery');

$(function() {
	// ----------------------------- Fiche rule

	//$( ".mapping p" ).hide();

	$( ".mapping > .title" ).on('click', function() {
		$('p',$( this ).parent()).toggle( "fadein" );
	});
	
	$( ".mapping > .title" ).on('click', function(){
		if($(this).parent().attr('class') == 'mapping') {
			$(this).parent().attr('class','mapping gray');							
			$(this).each(function(){
				if($(this).attr('data-title') == 'gray') {
					$(this).attr('class','title');
				}
			});
		}
		else {
			$(this).parent().attr('class','mapping');				
			$(this).each(function(){
				if($(this).attr('data-title') == 'gray') {
					$(this).attr('class','title gray');
				}
			});					
		}
	});
	
// Paramètres -------------------------------------------------------------	
	
	$( "#activerule").on('click', function() {	
		$.ajax({
			type: "POST",
			url: path_fiche_update,						
				success: function(data){			
			}			
		});	
	});
	
	$( '#saveRuleParams' ).on('click', function() {	

		loading_img = $('#myd_loading_img', '.myd_loading');	
		$.ajax({
			type: "POST",
			url: path_fiche_update_params,	
			data:{
				params : recup_params(),
			},
			beforeSend:	function() {
				$('#saveRuleParams').find('span').attr('class','glyphicon glyphicon-edit');
				loading_img.attr('src', path_img+'loader.gif');
				loading_img.show();
			},							
			success: function(data){
				loading_img.hide();
				loading_img.replaceWith("<span class='glyphicon glyphicon-ok'></span>");
				$('.myd_loading > span').show();
				if(data == 1) {
					$('#saveRuleParams').find('span').attr('class','glyphicon glyphicon-ok-circle');
				}
				else {
					$('#saveRuleParams').find('span').attr('class','glyphicon glyphicon-remove-circle');
				}
				setTimeout(function() {
					$('.myd_loading > span').hide();
					$('.myd_loading > span').replaceWith(loading_img);
					$('#saveRuleParams').find('span').attr('class','glyphicon glyphicon-edit');
				}, 2000);	
			}			
		});	
	});
	
	$( '#simuleRuleFluxAction' ).on('click', function() {
		simule_img = $('#simuleRuleFluxLoading_img', '.simuleRuleFluxLoading');	
		$.ajax({
			type: "GET",
			url: path_fiche_update_simulate,
			beforeSend:	function() {
				simule_img.attr('src', path_img+'loader.gif');
				simule_img.show();
				$('#simuleRuleFluxError').empty();
				$('#simuleRuleFluxError').hide();
				$('#simuleRuleFluxResult').empty();
				$('#simuleRuleFluxResult').append('-');
			},							
			success: function(data){
				obj = data;
				simule_img.hide();
				simule_img.replaceWith('<span><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check" viewBox="0 0 16 16"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/></svg></span>');
				$('.simuleRuleFluxLoading > span').show();
				if(typeof obj.error === "undefined") {
					$('#simuleRuleFlux').find('span').attr('class','glyphicon glyphicon-ok-circle');
					$('#simuleRuleFluxResult').empty();
					$('#simuleRuleFluxResult').val(data);
				} else {
					$('#simuleRuleFluxError').show();
					$('#simuleRuleFluxError').append(obj.error);
					$('#simuleRuleFlux').find('span').attr('class','glyphicon glyphicon-remove-circle');
				}
				setTimeout(function() {
					$('.simuleRuleFluxLoading > span').hide();
					$('.simuleRuleFluxLoading > span').replaceWith(simule_img);
					$('#simuleRuleFluxAction').find('span').attr('class','glyphicon glyphicon-play-circle');
					$('#simuleRuleFluxResult').val(data);
				}, 2000);
			}			
		});
	});		
});

// --For 'description' in the detail view of the rule
$('.edit-button-description').on('click', function () {
	var field = $(this).closest('td');
	var editForm = field.find('.edit-form');
	var valueField = field.find('.value');
	var newValueField = editForm.find('textarea');

	valueField.hide();
	editForm.show();
	newValueField.val(valueField.text().trim());
});

$('.edit-form').off('submit').on('submit', function (event) {
	event.preventDefault();

	var editForm = $(this);
	var valueField = editForm.closest('td').find('.value');
	var newValueField = editForm.find('textarea');
	var ruleId = editForm.find('input[name="ruleId"]').val();
	var newValue = newValueField.val().trim();
	var updateUrl = editForm.attr('action');

	if (newValue === "") {
		return;
	}

	$.ajax({
		type: 'POST',
		url: updateUrl,
		data: {
			ruleId: ruleId,
			description: newValue
		},
		success: function (response) {
			valueField.text(newValue);
			valueField.show();
			editForm.hide();
			location.reload();
		},
		error: function (error) {
			console.log(error);
			alert("Une erreur s'est produite lors de la mise à jour.");
		}
	});
});

// --For 'rule name' in the detail view of the rule
$('.edit-button-name').on('click', function () {
    var field = $(this).closest('td');
    var editForm = field.find('.edit-form-name-rule');
    var valueField = field.find('.detail-rule-name');
    var newValueField = editForm.find('input[name="ruleName"]');

    editForm.show();
    newValueField.val(valueField.text().trim());
});

$('.close-button-name').on('click', function () {
    var editForm = $(this).closest('.edit-form-name-rule');
    var valueField = editForm.closest('td').find('.rule-name');

    editForm.hide();
    valueField.show();
});

$('.edit-form-name-rule').on('submit', function (event) {
    event.preventDefault();

    var editForm = $(this);
    var valueField = editForm.closest('td').find('.detail-rule-name');
    var newValueField = editForm.find('input[name="ruleName"]');
    var ruleId = editForm.find('input[name="ruleId"]').val();
    var newValue = newValueField.val().trim();
    var updateUrl = editForm.attr('action');

    if (newValue === "") {
        alert("Rule name is empty");
        return;
    }

    $.ajax({
        type: 'POST',
        url: updateUrl,
        data: {
            ruleId: ruleId,
            ruleName: newValue
        },
        success: function (response) {
            valueField.text(newValue);
            editForm.hide();
        },
        error: function (error) {
            alert("Une erreur s'est produite lors de la mise à jour.");
        }
    });
});

// Récupère la liste des params
function recup_params() {	
	var params = [];	
	$( '.params','#ruleparams' ).each(function(){

        var name = $(this).attr('name');
		value = $(this).val();	
		id = $(this).attr('data-id');
        if(name == 'datereference_txt') {
        	name = 'datereference';
        }

		// delete the comma added by dtsel datetimepicker to fit with params format
		if (name === 'datereference' && value.includes(',')){
			value = value.replace(',', '');
		}

		params.push( {name: name, value: value, id: id } );
	});
	
	return params;
}

document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll('.edit-button-description');
    const closeButtons = document.querySelectorAll('.close-button-description');
    const editForms = document.querySelectorAll('.edit-form');

    editButtons.forEach((button, index) => {
        button.addEventListener('click', () => {
            editForms[index].style.display = 'block';
        });
    });

    closeButtons.forEach((button, index) => {
        button.addEventListener('click', () => {
            editForms[index].style.display = 'none';
        });
    });
});