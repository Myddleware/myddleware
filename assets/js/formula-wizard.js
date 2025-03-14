console.log('formula-wizard.js loaded')

// Function wizard handling
$(document).ready(function() {
    const functionSelect = $('#function-select');
    const lookupOptions = $('#lookup-options');
    const lookupRule = $('#lookup-rule');
    const lookupField = $('#lookup-field');
    const flagFunctionWizardEnd = $('#flag-function-wizard-end');
    const functionParameter = $('#function-parameter');
    const insertFunctionBtn = $('#insert-function-parameter');
    let tooltipVisible = false; // Changed to false by default
    let currentTooltip = '';
    let selectedFunction = '';
    
    // Handle tooltip toggle button
    $('#toggle-tooltip').on('click', function() {
        tooltipVisible = !tooltipVisible;
        const tooltipBox = $('#function-tooltip');
        
        if (tooltipVisible) {
            $(this).find('i').removeClass('fa-question').addClass('fa-question-circle');
            if (functionSelect.val() && currentTooltip) {
                tooltipBox.text(currentTooltip).show();
            }
        } else {
            $(this).find('i').removeClass('fa-question-circle').addClass('fa-question');
            tooltipBox.hide();
        }
    });

    // Show tooltip when option changes
    functionSelect.on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const tooltip = selectedOption.data('tooltip');
        const tooltipBox = $('#function-tooltip');
        
        selectedFunction = $(this).val();
        currentTooltip = tooltip;

        // if the selected function is a mwd function, we need to hide the function-parameter input, else we need to show it
        if (selectedFunction.startsWith('mdw_')) {
            $('#function-parameter').hide();
        } else {
            $('#function-parameter').show();
        }
        
        // Only show tooltip if tooltipVisible is true and a function is selected
        if (tooltip && tooltipVisible && $(this).val()) {
            tooltipBox.text(tooltip).show();
        } else {
            tooltipBox.hide();
        }

        if (selectedFunction === 'round') {
          $('#round-precision-input').show();
      } else {
          $('#round-precision-input').hide();
      }

        if (selectedFunction === 'lookup') {
            lookupOptions.show();
            $('#function-parameter-input').hide(); // Hide parameter input for lookup
            
            // Populate rules dropdown
            $.ajax({
                url: lookupgetrule,
                method: 'GET',
                data: {
                    arg1: connectorsourceidlookup,
                    arg2: connectortargetidlookup
                },
                success: function(rules) {
                    lookupRule.empty();
                    lookupRule.append('<option value="">' + translations.selectRule + '</option>');
                    rules.forEach(rule => {
                        lookupRule.append(`<option value="${rule.id}">${rule.name}</option>`);
                    });
                    lookupRule.prop('disabled', false);
                }
            });
        } else {
            lookupOptions.hide();
            $('#function-parameter-input').show(); // Show parameter input for other functions
        }
    });

    // Handle parameter insertion
    insertFunctionBtn.on('click', function() {
        if (!selectedFunction) return; // Do nothing if no function is selected
        
        // Get the function category from the selected option
        const functionCategory = $('#function-select option:selected').data('type');

        if (selectedFunction === 'round') {
          const parameterValue = functionParameter.val().trim();
          const precisionInput = $('#round-precision');
          const precision = parseInt(precisionInput.val());
          
          // Validate precision
          if (isNaN(precision) || precision < 1 || precision > 100) {
              precisionInput.addClass('is-invalid');
              return;
          }
          
          precisionInput.removeClass('is-invalid');
          
          const areaInsert = $('#area_insert');
          const position = areaInsert.getCursorPosition();
          const content = areaInsert.val();
          
          // Construct round function call with precision
          const functionCall = `round(${parameterValue}, ${precision})`;
          
          const newContent = 
              content.substr(0, position) +
              functionCall +
              content.substr(position);
              
          areaInsert.val(newContent);
          
          // Clear inputs
          functionParameter.val('');
          precisionInput.val('');
        
        // Special handling for MDW functions
        } else if (selectedFunction.startsWith('mdw_')) {
            const areaInsert = $('#area_insert');
            const position = areaInsert.getCursorPosition();
            const content = areaInsert.val();


            
            // For MDW functions, just insert the function name as a string
            const functionCall = `"${selectedFunction}"`;
            
            const newContent = 
                content.substr(0, position) +
                functionCall +
                content.substr(position);
                
            areaInsert.val(newContent);
        } else {
            // Normal function handling
            const parameterValue = functionParameter.val().trim();
            const areaInsert = $('#area_insert');
            const position = areaInsert.getCursorPosition();
            const content = areaInsert.val();
            
            // Create the function call based on category
            let functionCall = '';
            if (parameterValue) {
                switch(functionCategory) {
                    case 1: // mathematical
                        functionCall = `${selectedFunction}(${parameterValue})`; // No quotes for numbers
                        break;
                    case 2: // text
                    case 3: // date
                        functionCall = `${selectedFunction}("${parameterValue}")`; // Add quotes for text and dates
                        break;
                    case 4: // constant
                        functionCall = `${selectedFunction}()`; // Constants don't need parameters
                        break;
                    default:
                        functionCall = `${selectedFunction}("${parameterValue}")`; // Default to quoted parameter
                }
            } else {
                functionCall = `${selectedFunction}()`;
            }
            
            const newContent = 
                content.substr(0, position) +
                functionCall +
                content.substr(position);
                
            areaInsert.val(newContent);
        }
        
        // Clear the parameter input
        functionParameter.val('');
        
        // Update syntax highlighting
        colorationSyntax();
        theme(style_template);
    });

    // When a rule is selected
    lookupRule.on('change', function() {
        const selectedRule = $(this).val();
        
        if (selectedRule) {
            lookupField.empty();
            lookupField.append('<option value="">' + translations.selectField + '</option>');
            
            // Get fields from the existing select element
            $('#champs_insert option').each(function() {
                const fieldName = $(this).val();
                lookupField.append(`<option value="${fieldName}">${fieldName}</option>`);
            });
            
            lookupField.prop('disabled', false);
        } else {
            lookupField.prop('disabled', true);
        }
    });

    // Remove the lookupField.on('change') handler since we don't want automatic insertion
    lookupField.off('change');

    // Add handler for submit lookup button
    $('#submit-lookup').on('click', function() {
        const selectedField = lookupField.find('option:selected');
        if (selectedField.val()) {
            const fieldName = selectedField.text().split(' (')[0];
            let errorEmpty = $('#lookup-error-empty').is(':checked');
            let errorNotFound = $('#lookup-error-not-found').is(':checked');

            // Convert boolean to 1/0
            errorEmpty = errorEmpty ? 1 : 0;
            errorNotFound = errorNotFound ? 1 : 0;
            
            // Construct the complete lookup formula
            const lookupFormula = `lookup({${fieldName}}, "${lookupRule.val()}", ${errorEmpty}, ${errorNotFound})`;
            
            const areaInsert = $('#area_insert');
            const position = areaInsert.getCursorPosition();
            const content = areaInsert.val();
            
            const newContent = 
                content.substr(0, position) +
                lookupFormula +
                content.substr(position);
                
            areaInsert.val(newContent);
            colorationSyntax();
            theme(style_template);
        }
    });

    $('#round-precision').on('input', function() {
      const value = this.value;
      
      // Remove any non-digit characters
      const sanitizedValue = value.replace(/[^0-9]/g, '');
      
      if (sanitizedValue !== value) {
          this.value = sanitizedValue;
      }
      
      const precision = parseInt(sanitizedValue);
      
      if (isNaN(precision) || precision < 1 || precision > 100) {
          $(this).addClass('is-invalid');
      } else {
          $(this).removeClass('is-invalid');
      }
  });

// wait for the press of the close button which is #area_quit
$('#area_quit').on('click', function() {
    resetFunctionWizard();
});

// function to reset the function wizard
function resetFunctionWizard() {
    $('#function-select').val('').trigger('change');
    $('#lookup-rule').val('').trigger('change');
    $('#lookup-field').val('').prop('disabled', true);
}

    // Additional event handlers from regle.js
    
    // Ajouter un champ dans la zone de formule
    $("#champs_insert").on("dblclick", "option", function () {
        var position = $("#area_insert").getCursorPosition();
        var content = $("#area_insert").val();
        var newContent =
            content.substr(0, position) +
            "{" +
            $.trim($(this).attr("value")) +
            "}" +
            content.substr(position);
        $("#area_insert").val(newContent);
        colorationSyntax();
        theme(style_template);
    });

    // Add function to formula
    $("#functions").on("dblclick", "li", function () {
        var position = $("#area_insert").getCursorPosition();
        var content = $("#area_insert").val();
        var newContent =
            content.substr(0, position) +
            $.trim($(this).text()) +
            "( " +
            content.substr(position);
        // If we click on the function mdw_no_send_field, we don't want to add the parenthesis because it is a constant
        if (newContent.includes("mdw_no_send_field( ")) {
            newContent = newContent.replace("mdw_no_send_field( ", '"mdw_no_send_field"');
        }
        $("#area_insert").val(newContent);
        // Set the cursor position to the end of the new content
        $("#area_insert").setCursorPosition(newContent.length);
        colorationSyntax();
        theme(style_template);
    });

    // Update syntax highlighting on keyup
    $("#area_insert").on("keyup", function () {
        colorationSyntax();
        theme(style_template);
    });

    // Clear formula dialog
    $("#area_eff").on("click", function () {
        $("#area_insert").val("");
        $("#area_color").empty();
    });

    // Close formula dialog
    $("#area_quit").on("click", function () {
        $("#formule").dialog("close");
    });

    // Add source value to formula
    $("button", "#source_info").on("click", function () {
        var position = $("#area_insert").getCursorPosition();
        var content = $("#area_insert").val();
        var newContent =
            content.substr(0, position) +
            '"' +
            $.trim($("#source_value_select").val()) +
            '"' +
            content.substr(position);
        $("#area_insert").val(newContent);
        colorationSyntax();
        theme(style_template);
    });

    // Add target value to formula
    $("button", "#target_info").on("click", function () {
        var position = $("#area_insert").getCursorPosition();
        var content = $("#area_insert").val();
        var newContent =
            content.substr(0, position) +
            '"' +
            $.trim($("#target_value_select").val()) +
            '"' +
            content.substr(position);
        $("#area_insert").val(newContent);
        colorationSyntax();
        theme(style_template);
    });

    // Validate and save formula
    $("#area_confirm").on("click", function () {
        // Avant de confirmer la formule il faut la valider
        var myFormula = $("#area_insert").val(); // l'id du champ
        var zone = $(this).parent().parent().find("#formule_table").text();

        var values = [];

        // champs_insert is the id of the select element, add all the values of the options of the select element to the values array
        $("#champs_insert option").each(function () {
            values.push($(this).val());
        });

        var bracketError = false;
        var emptyBracketError = false;
        var missingFieldError = false;
        var missingFieldList = [];

        if (myFormula.includes("{") && myFormula.includes("}")) {
            // if there is a pair of curly brackets in myFormula, then check if it contains any of the values in the values array
            for (var i = 0; i < values.length; i++) {
                if (myFormula.includes(values[i])) {
                    continue;
                } else {
                    missingFieldError = true;
                    missingFieldList.push(values[i]);
                }
            }
        }

        // check for set of empty brackets
        if (myFormula.includes("{}")) {
            emptyBracketError = true;
        }

        if (emptyBracketError == true) {
            // finds the position of the first empty bracket
            var position = myFormula.indexOf("{}");
            alert("Your bracket is empty at position " + position + ".");
            missingFieldList = [];
            values = [];
        }

        if (bracketError == true && emptyBracketError == false) {
            // finds the position of the first bracket of the pair that is wrong
            alert("Your bracket number " + wrongbracket + " has a wrong field");
            missingFieldList = [];
            values = [];
        }

        if (missingFieldError == true) {
            alert(
                "Your formula is missing a field or more. Please add the following field(s): " +
                missingFieldList
            );
            missingFieldList = [];
            values = [];
        }

        // Count brackets for balance check
        var leftBracket = 0;
        var rightBracket = 0;
        var leftParenthesis = 0;
        var rightParenthesis = 0;
        var leftCurlyBracket = 0;
        var rightCurlyBracket = 0;

        for (var i = 0; i < myFormula.length; i++) {
            if (myFormula[i] == "[") {
                leftBracket++;
            } else if (myFormula[i] == "]") {
                rightBracket++;
            } else if (myFormula[i] == "(") {
                leftParenthesis++;
            } else if (myFormula[i] == ")") {
                rightParenthesis++;
            } else if (myFormula[i] == "{") {
                leftCurlyBracket++;
            } else if (myFormula[i] == "}") {
                rightCurlyBracket++;
            }
        }

        // Check for null not in quotes
        if (myFormula.includes("null")) {
            if (myFormula.includes('"null"') || myFormula.includes("'null'")) {
                // do nothing
            } else {
                alert(
                    "Your formula contains the substring null. Please encase it in two \"\" or two ''"
                );
                missingFieldList = [];
                values = [];
            }
        }

        // Check for empty function calls
        const functionsToCheck = ["round", "ceil", "abs", "trim", "ltrim", "rtrim", "lower", "upper", "substr", "striptags", "changeValue", "htmlEntityDecode", "replace", "utf8encode", "utf8decode", "htmlentities", "htmlspecialchars", "strlen", "urlencode", "chr", "json_decode", "json_encode", "getValueFromArray", "lookup", "date", "microtime", "changeTimeZone", "changeFormatDate", "mdw_no_send_field"];

        functionsToCheck.forEach(functionName => {
            const pattern = `${functionName}()|${functionName}( )`;
            if (myFormula.includes(functionName) && (myFormula.includes(`${functionName}()`) || myFormula.includes(`${functionName}( )`))) {
                alert(`Your formula contains the function ${functionName}. Please add the required parameters.`);
                missingFieldList = [];
                values = [];
            }
        });

        // Check bracket balance
        var result = checkBrackets(myFormula);
        if (!result.status) {
            alert(
                "Your formula has unbalanced brackets at position " +
                result.error_at +
                ". Bracket pair number " +
                result.unbalanced_pair +
                " is unbalanced. Bracket pair number " +
                result.balanced_pairs.join(", ") +
                " are balanced. Please check your formula."
            );
            missingFieldList = [];
            values = [];
        }

        // empty the values array
        missingFieldList = [];
        values = [];

        // Submit formula for validation
        $.ajax({
            type: "POST",
            url: path_formula,
            data: {
                formula: myFormula,
            },
            success: function (error) {
                if (error == 0) {
                    zone = $.trim(zone);
                    $("#formule_" + zone + " li").remove();
                    $("#formule_" + zone).append("<li>" + myFormula + "</li>");
                    $("#formule").dialog("close"); // Aucune erreur
                } else {
                    alert(formula_error);
                    // we alert but close anyway and validate the formula anyway
                    zone = $.trim(zone);
                    $("#formule_" + zone + " li").remove();
                    $("#formule_" + zone).append("<li>" + myFormula + "</li>");
                    $("#formule").dialog("close");
                }
            },
        });
    });
});

// Utility function for cursor positioning
$.fn.setCursorPosition = function(pos) {
    this.each(function(index, elem) {
        if (elem.setSelectionRange) {
            elem.setSelectionRange(pos, pos);
        } else if (elem.createTextRange) {
            var range = elem.createTextRange();
            range.collapse(true);
            range.moveEnd('character', pos);
            range.moveStart('character', pos);
            range.select();
        }
    });
    return this;
};

// Function to open the formula dialog
function openFormula() {
    // Dialogue formule ouverture
    $(".formule").on("click", function () {
        var li = $(this).parent().parent(); // block cible
        var champ_nom = $(this).parent().parent().parent().find("h1").text(); //nom du champ cible

        $("li.ch", li).each(function () {
            $("#champs_insert").append(
                '<option value="' +
                $(this).attr("value") +
                '">' +
                $(this).text() +
                "</option>"
            );
        });

        // récupération de la formule existante si rééouverture
        var formuleExistante = $("#formule_" + $.trim(champ_nom)).text();
        $("#area_insert").val(formuleExistante);
        $("#formule_table").empty();
        $("#formule_table").append(champ_nom); // nom du champ en titre

        $("#formule").dialog({
            width: "auto",
            height: "auto",
            draggable: false,
            modal: true,
            resizable: false,
            close: function (event, ui) {
                $("#champs_insert option").remove();
                $("#area_insert").val("");
                $("#formule_table").empty();
            },
        });
    });
}

// Function to check bracket balance
function checkBrackets(formula) {
    var map = {
        "(": ")",
        "[": "]",
        "{": "}",
    };

    var pairTypes = Object.keys(map);

    for (var t = 0; t < pairTypes.length; t++) {
        var stack = [];
        var pairs = [];
        var currentPair = 0;
        var errorAt = -1;

        var open = pairTypes[t];
        var close = map[open];

        for (var i = 0; i < formula.length; i++) {
            if (formula[i] === open) {
                currentPair++;
                stack.push({
                    symbol: open,
                    position: i,
                    pairNum: currentPair,
                });
            } else if (formula[i] === close) {
                var last = stack.pop();

                if (!last) {
                    errorAt = i;
                    currentPair++;
                    break;
                } else {
                    if (!pairs.includes(last.pairNum)) {
                        pairs.push(last.pairNum);
                    }
                }
            }
        }

        // If we still have unclosed brackets at the end of parsing, record an error
        if (stack.length > 0) {
            var lastUnbalanced = stack.pop();
            errorAt = lastUnbalanced.position;
            currentPair = lastUnbalanced.pairNum;
        }

        var status = stack.length === 0 && errorAt === -1;
        var unbalancedPair = status ? null : currentPair;

        var index = pairs.indexOf(unbalancedPair);
        if (index > -1) {
            pairs.splice(index, 1);
        }

        if (!status) {
            return {
                status: status,
                error_at: errorAt,
                unbalanced_pair: unbalancedPair,
                balanced_pairs: pairs.sort((a, b) => a - b),
            };
        }
    }

    return {
        status: true,
        error_at: -1,
        unbalanced_pair: null,
        balanced_pairs: [],
    };
}

// Function to collect all formulas
function recup_formule() {
    var resultat = "";

    $("#cible")
        .find(".formule_text li")
        .each(function () {
            var formule = $(this);
            var test = formule.parent().parent().parent().parent();
            var r = $(test).find("h1").text();
            resultat += $.trim(r) + "[=]" + formule.text() + ";";
        });

    return resultat;
}

// Function for syntax highlighting styling
function theme(style_template) {
    $("#area_color .operateur").css("letter-spacing", "5px");
    $("#area_color .chaine").css("letter-spacing", "2px");
    $("#area_color .variable").css("letter-spacing", "2px");

    if (style_template == "dark") {
        $("#area_color").css("background-color", "#272822");
        $("#area_color").css("color", "#f8f8f8");
        $("#area_color .operateur").css("color", "#f92665");
        $("#area_color .chaine").css("color", "#c8bf6f");
        $("#area_color .variable").css("color", "#8966c9");
    } else if (style_template == "light") {
        $("#area_color").css("background-color", "#fdf6e3");
        $("#area_color").css("color", "#61b5ac");
        $("#area_color .operateur").css("color", "#d33613");
        $("#area_color .chaine").css("color", "#268bd2");
        $("#area_color .variable").css("color", "#8966c9");
    } else if (style_template == "myddleware") {
        $("#area_color").css("background-color", "#EDEDED");
        $("#area_color").css("color", "#444446");
        $("#area_color .operateur").css("color", "#EC8709");
        $("#area_color .chaine").css("color", "#268bd2");
        $("#area_color .variable").css("color", "#198BCA");
    }
}

function colorationSyntax() {
    $("#area_color").html($("#area_insert").val());
  
    if ($("#area_insert").val() == "") {
      $("#area_color").empty();
    }
  
    $("#area_color").each(function () {
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
      text = text.replace(/\>=/g, "[begin] class='operateur'[f]>=[end]");
      text = text.replace(/\>/g, "[begin] class='operateur'[f]>[end]");
      text = text.replace(/\<=/g, "[begin] class='operateur'[f]<=[end]");
      text = text.replace(/\</g, "[begin] class='operateur'[f]<[end]");
      //---
      text = text.replace(/\{/g, "[begin] class='variable'[f]{");
      text = text.replace(/\}/g, "}[end]");
      //---
      text = text.replace(
        /\"([\s\S]*?)\"/g,
        "[begin] class='chaine'[f]\"$1\"[end]"
      );
      //---
      text = text.replace(/\[begin\]/g, "<span");
      text = text.replace(/\[f\]/g, ">");
      text = text.replace(/\[end\]/g, "</span>");
      $("#area_color").html(text);
    });
  
    // supprime les doublons
    $(".operateur", "#area_color").each(function () {
      if ($(this).parent().attr("class") == "chaine") {
        $(this).before($(this).html());
        $(this).remove();
      }
    });
  
    // supprime les doublons
    $(".variable", "#area_color").each(function () {
      if ($(this).parent().attr("class") == "chaine") {
        $(this).before($(this).html());
        $(this).remove();
      }
    });
  }
  //-- syntax color