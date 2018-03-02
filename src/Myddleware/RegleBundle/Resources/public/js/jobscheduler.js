$(document).ready(function () {


    fieldTreatment();
});
$('select#myddleware_reglebundle_jobscheduler_command').on('change', fieldTreatment);


function fieldTreatment() {

    var paramName1 = $("#myddleware_reglebundle_jobscheduler_paramName1");
    var paramValue1 = $("#myddleware_reglebundle_jobscheduler_paramValue1");
    var paramValue2 = $("#myddleware_reglebundle_jobscheduler_paramValue2");
    var paramName2 = $("#myddleware_reglebundle_jobscheduler_paramName2");

    var type = $('select#myddleware_reglebundle_jobscheduler_command').val();
    if ($('select#myddleware_reglebundle_jobscheduler_command').val() !== '') {
        type = $('select#myddleware_reglebundle_jobscheduler_command').val();
    } else {
    }


    var field_name = "";
    $.ajax({
        type: "GET",
        url: "http://localhost/myddleware/web/app_dev.php/rule/jobscheduler/getFieldsSelect",
        data: {
            type: type
        },
        success: function (option) {
            console.log(option);
            if (!option.hasOwnProperty('name')) {
                refreshFields();
            }
            delete option.name;
            // initialization input
            $.each(option, function (key, option) {
                console.log("key : ", key);
                console.log("option : ", option);
                var fieldName = '';
                var fieldValue = '';
                if (key === 'param1') {
                    fieldName = paramName1;
                    fieldValue = paramValue1;
                    field_name = "paramValue1";
                    $("#bloc_paramName1").show();
                    $("#bloc_paramvalue1").show();
                    console.log('field_name', field_name)
                    console.log('fieldValue', fieldValue.val())
                } else {
                    fieldName = paramName2;
                    fieldValue = paramValue2;
                    field_name = "paramValue2";
                    $("#bloc_paramName2").show();
                    $("#bloc_paramvalue2").show();
                    console.log('field_name', field_name)
                    console.log('fieldValue', fieldValue.val())
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
                        var input = $('<input id="myddleware_reglebundle_jobscheduler_' + (field_name) + '" type="number" name="myddleware_reglebundle_jobscheduler[' + (field_name) + ']" class="form-control" />');
                        return input;
                    });
                }
            });
        },
        error: function (err) {
            alert("An error ocurred while loading data ...");
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

