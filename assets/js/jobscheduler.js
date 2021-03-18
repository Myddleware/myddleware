var url = '../jobscheduler/getFieldsSelect';
require('../css/jobscheduler.css');

$(function(){
    if(window.location.href.indexOf("edit") > -1 ){
        url = '../../jobscheduler/getFieldsSelect';
    }
    if(window.location.href.indexOf("jobscheduler") > -1){
        fieldTreatment();
    }
});

$('select#myddleware_reglebundle_jobscheduler_command').on('change', fieldTreatment);

function fieldTreatment($event) {
    var paramName1 = $("#myddleware_reglebundle_jobscheduler_paramName1");
    var paramValue1 = $("#myddleware_reglebundle_jobscheduler_paramValue1");
    var paramValue2 = $("#myddleware_reglebundle_jobscheduler_paramValue2");
    var paramName2 = $("#myddleware_reglebundle_jobscheduler_paramName2");

    var type = $('select#myddleware_reglebundle_jobscheduler_command').val();
    if ($('select#myddleware_reglebundle_jobscheduler_command').val() !== '') {
        type = $('select#myddleware_reglebundle_jobscheduler_command').val();
    }
    var field_name = "";
    $.ajax({
        type: "GET",
        url: url,
        data: {
            type: type
        },
        success: function (option) {
            if ($event) {
                refreshFields();
            }
            if (!option) {
                return;
            }
            delete option.name;
            // initialization input
            $.each(option, function (key, option) {
                var fieldName = '';
                var fieldValue = '';
                if (key === 'param1') {
                    fieldName = paramName1;
                    fieldValue = paramValue1;
                    field_name = "paramValue1";
                    $("#bloc_paramName1").show();
                    $("#bloc_paramvalue1").show();
                } else {
                    fieldName = paramName2;
                    fieldValue = paramValue2;
                    field_name = "paramValue2";
                    $("#bloc_paramName2").show();
                    $("#bloc_paramvalue2").show();
                }
                fieldName.val(Object.keys(option)[0]);
                var fieldType = option[Object.keys(option)[0]]['fieldType'];
                if (fieldType === 'list') { // if input is select option
                    fieldValue.replaceWith(function ($this) {

                        var select = $('<select id="myddleware_reglebundle_jobscheduler_' + (field_name) + '" class="form-control" name="myddleware_reglebundle_jobscheduler[' + (field_name) + ']"></select>');
                        fieldValue.append('<option value  selected="selected">> Select ' + $(this).find("option:selected").text() + ' ...</option>');
                        for (var item in option[Object.keys(option)[0]].option) {
                            if (item == fieldValue.val()) {
                                select.append('<option value="' + item + '"selected="selected">' + option[Object.keys(option)[0]].option[item] + '</option>');
                            } else {
                                select.append('<option value="' + item + '" >' + option[Object.keys(option)[0]].option[item] + '</option>');
                            }
                        }
                        return select;
                    });
                } else if (fieldType === 'int') {
                    fieldValue.replaceWith(function () {
                        var input = $('<input id="myddleware_reglebundle_jobscheduler_' + (field_name) + '" type="number" name="myddleware_reglebundle_jobscheduler[' + (field_name) + ']" class="form-control" value="' + fieldValue.val() + '" />');
                        return input;
                    });
                }
            });
        },
        error: function (err) {
           console.log('error wrong path');
        }
    });

    /**
     * function for refresh fields
     */
    function refreshFields() {
        paramName1.val('');
        paramValue1.val('');
        paramName2.val('');
        paramValue2.val('');
        $("#bloc_paramName1").hide();
        $("#bloc_paramvalue1").hide();
        $("#bloc_paramName2").hide();
        $("#bloc_paramvalue2").hide();
    }
};

