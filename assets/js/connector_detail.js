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

const listOfFieldKeyWords = (typeof sensitiveFields !== 'undefined') ? sensitiveFields : [];

$(function () {
    $('#connexion_detail').on('click', function () {

        var datas = '';
        var parent = 'source';
        var status = $('#source_status');

        $('.title').each(function() {

            if ($(this).text() != 'solution' && $(this).text() != 'nom') {

                var dataElement = $(this).parent().find('[data-param]'); 

                if (dataElement.length > 0) {
                    var dataParam = dataElement.attr('data-param');
                    var textValue = dataElement.is('input') ? dataElement.val() : dataElement.text().trim();

                   if (listOfFieldKeyWords.some(keyword => $(this).text().toLowerCase() === keyword) && textValue === '******************') {
                        const matchedKeyword = listOfFieldKeyWords.find(keyword => $(this).text().toLowerCase() === keyword);
                        textValue = window[matchedKeyword];
                    }
                    if (dataParam !== undefined) {
                        datas += dataParam + "::" + textValue.replace(/;/g, "") + ";";
                        
                    }
                }
            }
        });

        $.ajax({
            type: "POST",
            url: '../../inputs',
            data: {
                champs: datas,
                parent: parent,
                solution: $('.vignette').attr('alt'),
                mod: 2
            },
            beforeSend: function () {
                status.empty().append('<i class="fa fa-spinner fa-spin status-loader"></i>');
            },
            success: function (json) {
                if (!json.success) {
                    status.empty().append('<i class="fa fa-lightbulb status-offline"></i>');
                    $('#msg_status span.error').html(json.message);
                    $('#msg_status').show();
                    return false;
                }

                $.ajax({
                    type: "POST",
                    data: {
                        solutionjs: true,
                        detectjs: true
                    },
                    url: '../callback/',
                    success: function (data) {
                        param = data.split(';');

                        if (param[0] == 1) {
                            link = param[1];

                            $.ajax({
                                type: "POST",
                                data: {
                                    solutionjs: true
                                },
                                url: '../callback/',
                                success: function (data) {
                             
                                    if (data != 1) {
                                        var win = window.open(link, 'Connexion', 'scrollbars=1,resizable=1,height=560,width=770');
                                        if (data != 401) {
                                            var timer = setInterval(function () {
                                                if (win.closed) {
                                                    clearInterval(timer);
                                                    if (confirm("Reconnect")) {
                                                        $('#connexion_detail').trigger();
                                                    }
                                                }
                                            }, 1000);
                                        } else {
                                            $('#connexion_detail').trigger();
                                        }

                                        status.empty().append('<i class="fa fa-lightbulb status-offline"></i>');
                                        
                                        var r = data.split(';');
                                        $('#msg_status span.error').html(r[0]);
                                        $('#msg_status').show();
                                    } else {
                                        status.empty().append('<i class="fa fa-lightbulb status-online"></i>');
                                        $('#msg_status').hide();
                                        $('#msg_status span.error').html('');
                                        $('#step_modules_confirme').removeAttr('disabled');
                                    }
                                }
                            });
                        } else {
                            if (!json.success) {
                                status.empty().append('<i class="fa fa-lightbulb status-offline"></i>');
                                $('#msg_status span.error').html(json.message);
                                $('#msg_status').show();
                            } else {
                                status.empty().append('<i class="fa fa-lightbulb status-online"></i>');
                                $('#msg_status').hide();
                                $('#msg_status span.error').html('');
                                $('#step_modules_confirme').removeAttr('disabled');
                            }
                        }
                    }
                });
            },
        });
    });
});