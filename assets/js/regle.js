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

global.path_img = '../../build/images/';

$(function () {
  // ----------------------------- AFFICHAGE DU LOADING LANCEMENT REGLE / ANNULER FLUX
  $(window).on("load", function () {
    // Bouton action "Exécuter règles actives"
    $("#exec_all", "#rule").on("click", function (e) {
      if (confirm(confirm_exec_all)) {
        // Clic sur OK
        btn_action_fct();
      } else {
        e.preventDefault();
      }
    });
    // Bouton action "Relancer les erreurs"
    $("#exec_error", "#rule").on("click", function (e) {
      if (confirm(confirm_exec_error)) {
        // Clic sur OK
        btn_action_fct();
      } else {
        e.preventDefault();
      }
    });
    // ----------------------------- Boutons d'actions (affichage d'un loading)
    // Appelé pour: "Annuler transfert" et "Exécuter la règle"
    $(".btn_action_loading").on("click", function (e) {
      btn_action_fct();
    });

    $(window).resize(function () {
      $("#myd_loading").css({
        width: $(window).width() + "px",
        height: $(window).height() + "px",
      });
      $("#myd_loading > p").css({
        top: $(window).height() / 2 - 100,
        left: $(window).width() / 2 - 65,
      });
    });
  });

  $("#rule_previous").on("click", function () {
    // console.log("previous start");
    previous_next(0);
    // console.log("previous end");  
  });

  $("#rule_next").on("click", function () {
    // console.log("next start");
    previous_next(1);
    // console.log("next end");
  });

  $("#tabs", "#rule_mapping").tabs({
    activate: function (event, ui) {
      // console.log("activate rule mapping start");
      previous_next(2);
      // console.log("activate rule mapping end");
    },
  });
  // rev 1.08 ----------------------------

  $(".tooltip").qtip(); // Infobulle

  notification();

  // ----------------------------- List rule
  if (typeof question !== "undefined" && question) {
    $("#listrule .delete").on("click", function () {
      var answer = confirm(question);
      if (answer) {
        return true;
      } else {
        return false;
      }
    });
  }

  // ----------------------------- Step
  if (typeof onglets !== "undefined" && onglets) {
    $("#tabs").tabs(onglets);
  }

  // ----------------------------- Step 1

  // Test si le name de la règle existe ou non
  $("#rulename", "#connexion").on("keyup", function () {
    var error = 1;
    if ($(this).val().length > 2) {
      $.ajax({
        type: "POST",
        url: Routing.generate("regle_inputs_name_unique"),
        data: {
          name: $(this).val(),
        },
        success: function (msg) {
          if (msg == 0) {
            $("#rulename").css("border", " 3px solid #0DF409");
            error = 0;
          } else {
            $("#rulename").css("border", "3px solid #E81919");
            error++;
          }

          next_step(error);
        },
      });
    } else {
      $("#rulename").css("background", "#202020");
      $("#rulename").css("border", "3px solid transparent");
      next_step(0);
    }
  });

  $("#source_msg").hide(); // message retour
  $("#cible_msg").hide(); // message retour

  // Tentative de connexion
  $(document).on("change", "#soluce_cible, #soluce_source", function () {
    var val = $(this).val();
    var parent = $(this).parent().attr("id");
    var val2 = val.split("_");

    $("#msg_status").hide();

    if (val == "") {
      $("#" + parent + "_msg").hide();
      $(this).parent().find(".picture").empty();
      $(this).parent().find(".champs").empty();
      $(this).parent().find(".help").empty();
    } else {
      $(this).parent().find(".picture img").remove();
      $(this).parent().find(".help").empty();
      var solution = val2[0] ? val2[0] : val;
      // if we're creating the connector from the '+' button modal in rule creation view
      if (window.location.pathname.includes("createout")) {
        path_img_modal = "../../../build/images/";
        $(this)
          .parent()
          .find(".picture")
          .append(
            '<img src="' +
              path_img_modal +
              "solution/" +
              solution +
              '.png" alt="' +
              solution +
              '" />'
          );
      } else {
        $(this)
          .parent()
          .find(".picture")
          .append(
            '<img src="' +
              path_img +
              "solution/" +
              solution +
              '.png" alt="' +
              solution +
              '" />'
          );
      }

      $(this)
        .parent()
        .find(".help")
        .append(
          '<i class="fas fa-info-circle"></i> <a href="' +
            path_link_fr +
            solution +
            '" target="_blank"> ' +
            help_connector +
            "</a>"
        );

      if ($.isNumeric(val2[1])) {
        $.ajax({
          type: "POST",
          url: "../inputs",
          data: {
            solution: val,
            parent: parent,
            name: $("#rulename").val(),
            mod: 3,
          },
          beforeSend: function () {
            $("#" + parent + "_status img").removeAttr("src");
            $("#" + parent + "_status img").attr(
              "src",
              path_img + "loader.gif"
            );
          },
          success: function (msg) {
            r = msg.split(";");

            if (r[1] == 0) {
              $("#" + parent + "_status img").removeAttr("src");
              $("#" + parent + "_status img").attr(
                "src",
                path_img + "status_offline.png"
              );
              $("#" + parent + "_msg span").html(r[0]);
              $("#" + parent + "_msg").show();
            } else {
              $("#" + parent + "_status img").removeAttr("src");
              $("#" + parent + "_status img").attr(
                "src",
                path_img + "status_online.png"
              );
              $("#" + parent + "_msg").hide();
              $("#" + parent + "_msg span").html("");
            }

            next_step(0);
          },
        });
      } else {
        // Recupere tous les champs de connexion
        champs(val, $(this).parent().find(".champs"), parent);
      }
    }
  });
  // Si nom de règle est vide alors retour false du formulaire
  $("#connexion #step_modules_confirme").on("click", function () {
    if (!$("#rulename").val()) {
      return false;
    }
  });

  $("#msg_status").hide(); // message retour

  // ----------------------------- Step 3

  prepareDrag();

  // ---- PREPARATION DE LA ZONE DRAGGABLE -----------------------------------

  // ---- FORMULE ------------------------------------------------------------
  if (
    typeof style_template !== "undefined" &&
    typeof formula_error !== "undefined"
  ) {
    $("#area_insert").on("keyup", function () {
      colorationSyntax();
      theme(style_template);
    });

    // Filtre des fonctions pour les formules
    $("#filter").on("change", function () {
      var cat = $("select[name='filter_functions'] > option:selected").attr(
        "data-type"
      );
      if (cat >= 1) {
        $(".func", "#functions").each(function () {
          if ($(this).attr("data-type") != cat) {
            $(this).fadeOut(200);
          } else {
            $(this).fadeIn(200);
          }
        });
      } else {
        $(".func", "#functions").fadeIn(200);
      }
    });

    $("#test").on("click", function () {
      recup_formule();
    });

    openFormula();

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

    // Btn clear dialogue formule
    $("#area_eff").on("click", function () {
      $("#area_insert").val("");
      $("#area_color").empty();
    });

    // Btn fermer la doite de dialogue
    $("#area_quit").on("click", function () {
      $("#formule").dialog("close");
    });

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

// console.log('Checking pair type:', open+close);

        for (var i = 0; i < formula.length; i++) {
          if (formula[i] === open) {
            currentPair++;
            stack.push({
              symbol: open,
              position: i,
              pairNum: currentPair,
            });
// console.log('Found opening symbol at', i, 'Pair Number:', currentPair);
          } else if (formula[i] === close) {
            var last = stack.pop();

            if (!last) {
// console.log('Found closing symbol without matching opening symbol at', i);
              errorAt = i;
              currentPair++;
              break;
            } else {
// console.log('Found matching closing symbol for pair', last.pairNum, 'at', i);
              if (!pairs.includes(last.pairNum)) {
                pairs.push(last.pairNum);
              }
            }
          }
        }

        // If we still have unclosed brackets at the end of parsing, record an error
        if (stack.length > 0) {
// console.log('Found unbalanced pair at the end of the formula');
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

// console.log('Pair Type:', open + close);
// console.log('Status:', status);
// console.log('Error Position:', errorAt);
// console.log('Unbalanced Pair:', unbalancedPair);
// console.log('Balanced Pairs:', pairs);

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

    // Btn confirmation dialogue formule
    $("#area_confirm").on("click", function () {
      // Avant de confirmer la formule il faut la valider
      var myFormula = $("#area_insert").val(); // l'id du champ
      var zone = $(this).parent().parent().find("#formule_table").text();

      var values = [];

      // champs_insert is the id of the select element, add all the values of the options of the select element to the values array
      $("#champs_insert option").each(function () {
        var valueOfTheOption = $(this).val();
        var nameOfTheOption = $(this).text();
        // concatenate the value of the option with the name of the option
        values.push($(this).val() + " (" + $(this).text() + ")");
      });

// console.log('these are the values', values);

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
        // prevents from exiting the current menu
        // return false;
      }

      if (bracketError == true && emptyBracketError == false) {
        // finds the position of the first bracket of the pair that is wrong
        alert("Your bracket number " + wrongbracket + " has a wrong field");
        missingFieldList = [];
        values = [];
        // prevents from exiting the current menu
        // return false;
      }

      if (missingFieldError == true) {
        alert(
          "Your formula is missing a field or more. Please add the following field(s): " +
            missingFieldList
        );
        missingFieldList = [];
        values = [];
        // return false;
      }

      // if there are one or more unbalanced [, (, {,in myFormula, then bracketError = true
      // algorithm to take count of every bracket in the formula and then compare the number of each type of bracket
      // if the number of each type of bracket is not equal, then bracketError = true
      // if the number of each type of bracket is equal, then bracketError = false
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

      // if (leftBracket != rightBracket) {
      // 	alert('Your formula has an unbalanced number of brackets. You have ' + leftBracket + ' left brackets and ' + rightBracket + ' right brackets. Please check your formula.');
      // 	return false;

      // } if (leftParenthesis != rightParenthesis) {
      // 	alert('Your formula has an unbalanced number of parenthesis. You have ' + leftParenthesis + ' left parenthesis and ' + rightParenthesis + ' right parenthesis. Please check your formula.');
      // 	return false;
      // }

      // if (leftCurlyBracket != rightCurlyBracket) {
      // 	alert('Your formula has an unbalanced number of curly brackets.  You have ' + leftCurlyBracket + ' left curly brackets and ' + rightCurlyBracket + ' right curly brackets. Please check your formula.');
      // 	return false;
      // }

      // If there is the substring null in the formula and it is not encased in two "" or two '', then return an error
      if (myFormula.includes("null")) {
        if (myFormula.includes('"null"') || myFormula.includes("'null'")) {
          // do nothing
        } else {
          alert(
            "Your formula contains the substring null. Please encase it in two \"\" or two ''"
          );
          missingFieldList = [];
          values = [];
          // return false;
        }
      }

      const functionsToCheck = ["round", "ceil", "abs", "trim", "ltrim", "rtrim", "lower", "upper", "substr", "striptags", "changeValue", "htmlEntityDecode", "replace", "utf8encode", "utf8decode", "htmlentities", "htmlspecialchars", "strlen", "urlencode", "chr", "json_decode", "json_encode", "getValueFromArray", "lookup", "date", "microtime", "changeTimeZone", "changeFormatDate", "mdw_no_send_field"];

      functionsToCheck.forEach(functionName => {
        const pattern = `${functionName}()|${functionName}( )`;
        if (myFormula.includes(functionName) && (myFormula.includes(`${functionName}()`) || myFormula.includes(`${functionName}( )`))) {
          alert(`Your formula contains the function ${functionName}. Please add the required parameters.`);
          missingFieldList = []; // Reset or handle as needed
          values = []; // Reset or handle as needed
          // return false; // Depending on your needs, you might want to return from the enclosing function
        }
      });

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
        // return false;
      }

      // empty the values array
      missingFieldList = [];
      values = [];
// console.log('these are the values', values);

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
          }
        },
      });
    });

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
    $("button", "#lookup_rules").on("click", function () {
      var position = $("#area_insert").getCursorPosition();
      var content = $("#area_insert").val();
      var newContent =
        content.substr(0, position) +
        '"' +
        $.trim($("select", "#lookup_rules").val()) +
        '"' +
        content.substr(position);
      $("#area_insert").val(newContent);
      colorationSyntax();
      theme(style_template);
    });
  }
  // ---- FORMULE ------------------------------------------------------------

  // ---- SIMULATION DE DONNEES ------------------------------------------------------------

  // Avant la validation du formulaire on peut simuler les données pour contrôler le résultat
  $("#validation_simulation").on("click", function () {
    if (require()) {
      $.ajax({
        type: "POST",
        url: path_simulation,
        data: {
          champs: recup_champs(),
          formules: recup_formule(),
          params: recup_params(),
          relations: recup_relation(),
        },
        beforeSend: function () {
          $("#simulation_tab").html(
            '<i class="fa fa-info-circle" aria-hidden="true"></i>' + data_wait
          );
        },
        success: function (data) {
          if (data == 0) {
            $("#simulation_tab").html("error");
          } else {
            $("#simulation_tab").html(data);
          }
        },
      });
    }
  });

  // ESTELLE TODO ; check whether this is still up to date (we might change to only one button)
  // For rule simulation, users can send a specific record ID to test the data with, instead of the default read_last
  $("#validation_manual_simulation").on("click", function (e) {
    // Prevent running simulation if the input is empty / invalid
    if (
      select_record_id.value === false ||
      select_record_id.value === null ||
      select_record_id.value === undefined ||
      select_record_id.value === ""
    ) {
      $("#manual-simulation-container").after(
        '<div><span style="color : red;">Please insert a valid record ID if you want to run a manual simulation.</span></div>'
      );
      e.preventDefault();
    } else {
      if (require()) {
        $.ajax({
          type: "POST",
          url: path_simulation,
          data: {
            champs: recup_champs(),
            formules: recup_formule(),
            params: recup_params(),
            relations: recup_relation(),
            query: select_record_id.value,
          },
          beforeSend: function () {
            $("#simulation_tab").html(
              '<i class="fa fa-info-circle" aria-hidden="true"></i>' + data_wait
            );
          },
          success: function (data) {
            if (data == 0) {
              $("#simulation_tab").html("error");
            } else {
              $("#simulation_tab").html(data);
            }
          },
        });
      }
    }
  });

  // ---- SIMULATION DE DONNEES ------------------------------------------------------------

  $("#addField").on("click", function () {
    $("#formatfield").toggle("fadeIn", function () {});
  });

  $("#saveBtnField").on("click", function () {
    newfield = $("#formatfield input").val();
    newtype = $("select", "#formatfield").val();
    fields = htmlentities(removeSpace(newfield) + "_" + newtype);

    if (newfield != "" && existField(fields)) {
      box =
        '<div id="' +
        fields +
        '" class="champs" data-show="true"><h1 class="nom ui-widget-header">' +
        fields +
        '</h1><div class="ui-widget-content" data-show=""><ol class="ui-droppable ui-sortable"><li class="placeholder">' +
        placeholder +
        '</li></ol><ul><li id="formule_' +
        fields +
        '" class="formule_text"></li></ul><p><input class="formule btn btn-outline-primary" type="button" value="' +
        formula_create +
        '"></p></div></div>';
      $("#targetfields").append(box);
      $("#formatfield input").val("");
    }

    prepareDrag(); // Permet de faire un drag n drop
    openFormula(); // Permet d'ouvrir la boite de dialogue des formules
    hideFields(); // Filtre les champs
  });

  $("#hidefields").on("keyup", function () {
    hideFields();
  });

  // Affiche les champs obligatoire pour éviter les doublons
  $("#fields_duplicate_target").on("click", "li", function () {
    // si le champ est sélectionné
    if (fields_exist($(this).text())) {
      if ($(this).attr("data-active") === "false") {
        $(this).attr("data-active", "true");
        $(this).addClass("active");
      } else {
        $(this).attr("data-active", "false");
        $(this).removeClass("active");
      }
    } else {
      if ($(this).attr("class") === "no_active") {
        $(this).removeClass("no_active");
      } else {
        $(this).addClass("no_active");
      }
    }

    recup_fields_relate();
  });

  // Validation et vérification de l'ensemble du formulaire
  $("#validation").on("click", function () {
    before = $("#validation").attr("value");

    if (
      require() &&
      require_params() &&
      require_relate() &&
      duplicate_fields_error()
    ) {
      $.ajax({
        type: "POST",
        url: path_validation,
        data: {
          champs: recup_champs(),
          formules: recup_formule(),
          params: recup_params(),
          relations: recup_relation(),
          duplicate: recup_fields_relate(),
          filter: recup_filter(),
        },
        beforeSend: function () {
          $("#validation").attr("value", save_wait);
        },
        success: function (data) {
          if (data.status == 1) {
            alert(confirm_success);

            var path_template = $("#validation").data("url");
            var path_view_detail = path_template.replace(
              "placeholder_id",
              data.id
            );
            $(location).attr("href", path_view_detail);
          } else {
            data = data.split(";");
            if (data[0] == 2) {
              alert(data[1]);
            } else {
              alert(confirm_error);
            }
            $("#validation").attr("value", before);
          }
        },
        statusCode: {
          500: function (e) {
            // console.log(e.responseText);
            alert(
              "An error occured. Please check your server logs for more detailed information."
            );
            $("#validation").attr("value", before);
          },
        },
      });
    } else {
      $("#dialog").dialog({
        draggable: false,
        modal: true,
        resizable: false,
      });

      $("#validation").attr("value", before);
    }
  });

  // ---- PARAMS ET VALIDATION  ------------------------------------------------------------

  // ---- RELOAD  --------------------------------------------------------------------------

  if (
    typeof fields !== "undefined" &&
    typeof params !== "undefined" &&
    typeof relate !== "undefined"
  ) {
    // Fields
    if (fields) {
      $.each(fields, function (index, nameF) {
        // fields
        $("#" + nameF.target + " .ui-droppable").empty();

        $.each(nameF.source, function (fieldid, fieldname) {
          $("#" + nameF.target + " .ui-droppable").append(
            '<li value="' +
              fieldid +
              '" class="ch">' +
              fieldid +
              " (" +
              fieldname +
              ")" +
              "</li>"
          );
        });

        // formula
        if (nameF.formula != null) {
          $("#formule_" + nameF.target).append(
            "<li>" + nameF.formula + "</li>"
          );
        }
      });
    }

    // Filter
    if (filter) {
      $.each(filter, function (index, nameF) {
        $("select", "#filter_" + nameF.target).val(nameF.type);
        $("input", "#filter_" + nameF.target).val(nameF.value);
      });
    }

    // Params
    if (params) {
      $.each(params, function (index, nameP) {
        $("#" + nameP.name).val(nameP.value);
        if (nameP.name === "duplicate_fields") {
          duplicate_fields = nameP.value.split(";");
          $.each(duplicate_fields, function (index, d_fields) {
            $(
              "li:contains('" + d_fields + "')",
              "#fields_duplicate_target"
            ).click();
          });
        }
      });
    }
    // Relate
    if (relate) {
      var cpt = 0;
      // We fill the differents field depending if the rule is a parent one or not
      $.each(relate, function (index, nameR) {
        if (nameR.parent == 0) {
          $("#lst_" + nameR.target).val(nameR.id);
          $("#lst_source_" + nameR.target).val(nameR.source);
          $("#lst_error_missing_" + nameR.target).val(nameR.errorMissing);
          $("#lst_error_empty_" + nameR.target).val(nameR.errorEmpty);
        } else {
          $("#parent_rule_" + cpt).val(nameR.id);
          $("#parent_source_field_" + cpt).val(nameR.source);
          $("#parent_search_field_" + cpt).val(nameR.target);
          cpt++;
        }
      });
    }
  }
  // ---- RELOAD  --------------------------------------------------------------------------

  // ---- FLUX  --------------------------------------------------------------------------

  var massFluxTab = [];
  showBtnFlux(massFluxTab);

  // Mass action on flux list
  $("#massselectall").on("change", function () {
    if ($(this).is(":checked")) {
      remove = false;
    } else {
      remove = true;
    }

    $("input", ".listepagerflux td").each(function () {
      if ($(this).attr("disabled") != "disabled") {
        if ($(this).is(":checked")) {
          if (remove) {
            id = $(this).attr("name");
            massAddFlux(id, true, massFluxTab);
            $(this).prop("checked", false);
          }
        } else {
          if (remove == false) {
            id = $(this).attr("name");
            massAddFlux(id, false, massFluxTab);
            $(this).prop("checked", true);
          }
        }
      }
    });

    showBtnFlux(massFluxTab);
  });

  $("input", ".listepagerflux td").on("change", function () {
    if ($(this).is(":checked")) {
      massAddFlux($(this).attr("name"), false, massFluxTab);
    } else {
      massAddFlux($(this).attr("name"), true, massFluxTab);
    }

    showBtnFlux(massFluxTab);
  });

  $("#cancelflux").on("click", function () {
    if (confirm(confirm_cancel)) {
      // Clic sur OK
      $.ajax({
        type: "POST",
        url: mass_cancel,
        beforeSend: function () {
          btn_action_fct(); // Animation
        },
        data: {
          ids: massFluxTab,
        },
        success: function (data) {
          // code_html contient le HTML renvoyé
          location.reload();
        },
      });
    }
  });

  $("#cancelreloadflux").on("click", function () {
    if (confirm(confirm_cancel)) {
      $.ajax({
        type: "POST",
        url: mass_cancel,
        beforeSend: function () {
          btn_action_fct(); // Animation
        },
        data: {
          ids: massFluxTab,
          reload: true,
        },
        success: function (data) {
          // code_html contient le HTML renvoyé
          location.reload();
        },
      });
    }
  });

  $("#unlockAllFlux").on("click", function () {
    if (confirm(confirm_unlock)) {
        $.ajax({
            type: "POST",
            url: mass_unlock,
            beforeSend: function () {
                btn_action_fct();
            },
            data: {
                ids: massFluxTab,
            },
            success: function (response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.error);
                }
            },
            error: function (jqXHR) {
                // console.log('Error response:', jqXHR.responseText);
                alert('An error occurred while unlocking documents.');
            },
        });
    }
});

  $("#reloadflux").on("click", function () {
    // console.log(mass_run);
    if (confirm(confirm_reload)) {
      // Clic sur OK
      $.ajax({
        type: "POST",
        url: mass_run,
        beforeSend: function () {
          btn_action_fct(); // Animation
        },
        data: {
          ids: massFluxTab,
        },
        success: function (data) {
          // code_html contient le HTML renvoyé
          location.reload();
        },
      });
    }
  });

  $("#flux_target").on("dblclick", "li", function () {
  if (
    $("#gblstatus").attr("data-gbl") == "error" ||
    $("#gblstatus").attr("data-gbl") == "open"
  ) {
    verif = $(this).attr("class");
    first = $("li:first", "#flux_target").attr("class");
    classe = $(this).attr("class");

    if (
      typeof verif !== "undefined" &&
      ((first === "undefined") != classe) !== "undefined"
    ) {
      // Check if an input element already exists
      if ($(this).find('input').length > 0) {
        // If it does, show an alert to the user
        alert('Please close the first one before adding a new one.');
      } else {
        value = $(this).find(".value").text();
        $(this).find(".value").remove();
        newElement = $(this).append(
          '<input id="' +
            classe +
            '" type="text" value="' +
            value +
            '" /><button type="submit" data-value="' +
            classe +
            '" class="btn-group btn-group-xs load"><i class="fa fa-check-circle"></i ></button> '
        );
        $(this).append(newElement);
      }
    }
  }
});

  // If the div in flux_target is clicked, then wec call the saveInputFlux function
  $("#flux_target").on("click", ".load", function () {
    saveInputFlux($(this), inputs_flux);
  });

  // Rev 1.1.0 Upload Files ------------------------------
  // Fermeture de la fancybox
  $(".fancybox_upload").fancybox({
    maxWidth: 800,
    maxHeight: 600,
    fitToView: false,
    width: "70%",
    height: "70%",
    autoSize: false,
    closeClick: false,
    openEffect: "elastic",
    closeEffect: "elastic",
    beforeClose: function () {
      name_file_upload();
    },
    "beforeLoad ": function () {
      if (!confirm_upload()) {
        $.fancybox.cancel();
      }
    },
    closeClick: true,
  });
});

// ---- EXPORT DOCUMENTS TO CSV  --------------------------------------------------------------------------

$("#exportfluxcsv").on("click", function () {
  $.ajax({
    type: "POST",
    url: flux_export_docs_csv,
    data: {
      csvdocumentids: csvdocumentids,
    },
    xhrFields: {
      responseType: 'blob' // Set the response type to blob
    },
    success: function (blob) {
      // Create a temporary URL for the blob
      const url = window.URL.createObjectURL(blob);
      
      // Create a temporary link element
      const a = document.createElement('a');
      a.href = url;
      a.download = `documents_export_${new Date().toISOString().slice(0,19).replace(/[:]/g, '')}.csv`;
      
      // Append link to body, click it, and remove it
      document.body.appendChild(a);
      a.click();
      
      // Clean up
      window.URL.revokeObjectURL(url);
      a.remove();
    },
    error: function(xhr, status, error) {
      console.error('Export failed:', error);
      alert('Failed to export CSV file. Please try again.');
    }
  });
});

// ---- EXPORT DOCUMENTS TO CSV  --------------------------------------------------------------------------

function name_file_upload() {
  $.ajax({
    type: "POST",
    url: Routing.generate("upload", {
      solution: "all",
    }),
    success: function (data) {
      r = data.split(";");
      if (r[0] == 1) {
        $("#param_wsdl").val(r[1]);
      }

      // Createout
      $("#source_test").removeAttr("disabled");
    },
  });
}
// Rev 1.1.0 Upload Files ------------------------------

function confirm_upload() {
  $("#link_wsdl").on("click", function (e) {
    if ($("#param_wsdl").val() != "") {
      if (
        confirm("Souhaitez-vous abandonner l'ancien fichier de configuration ?")
      ) {
        return true;
      } else {
        return false;
      }
    }
  });
}

confirm_upload();

function htmlentities(value) {
  if (value) {
    return jQuery("<div />").text(value).html();
  } else {
    return "";
  }
}

// rev 1.08 --------
var TabSpec = {
  à: "a",
  á: "a",
  â: "a",
  ã: "a",
  ä: "a",
  å: "a",
  ò: "o",
  ó: "o",
  ô: "o",
  õ: "o",
  ö: "o",
  ø: "o",
  è: "e",
  é: "e",
  ê: "e",
  ë: "e",
  ç: "c",
  ì: "i",
  í: "i",
  î: "i",
  ï: "i",
  ù: "u",
  ú: "u",
  û: "u",
  ü: "u",
  ÿ: "y",
  ñ: "n",
  "-": " ",
  _: " ",
};

function replaceSpec(Texte) {
  var reg = /[àáäâèéêëçìíîïòóôõöøùúûüÿñ-]/gi;
  return Texte.replace(reg, function () {
    return TabSpec[arguments[0].toLowerCase()];
  }).toLowerCase();
}

function removeSpace(string) {
  string = replaceSpec(string);
  string = string.replace(/\s/g, "");
  string = string.replace(/[^a-zA-Z0-9_\s]/gi, "");
  return string;
}
// rev 1.08 --------

/* infobulle mapping des champs */
function fields_target_hover() {
  $(".ch").on(
    "hover",
    function () {
      $(this).append(
        $(
          '<i class="fa fa-info-circle" aria-hidden="true"></i> ' +
            infobulle_fields +
            "</div>"
        )
      );
    },
    function () {
      $(this).find("div:last").remove();
    }
  );

  $(".ch").on("click", function () {
    $(this).find("div:last").remove();
  });
}

// rev 1.08 ----------------------------
function previous_next(tab) {
  // console.log("previous_next start inside the function charlie");
  // console.log("tab", tab);
  // tab 0 : default
  // tab 1 : plus
  // tab 2 : manual tab
  name_current = $(".active").attr("aria-controls");
  // console.log("Current active tab name:", name_current);
  
  number = 0;
  number = name_current.split("-");
  number = parseInt(number[1]);
  // console.log("Parsed tab number:", number);

  $("#rule_previous").show();
  $("#rule_next").show();

  if (number == 3) {
    // console.log("Hiding previous button because number is 3");
    $("#rule_previous").hide();
    $("#rule_next").show();
  }

  if (number == 7) {
    // console.log("Hiding next button because number is 7");
    $("#rule_next").hide();
    $("#rule_previous").show();
  }

  if (tab == 1) {
    number_next = number + 1;
    // console.log("Next tab number:", number_next);
    let nextTab = $(".tab-" + number_next);
    
    // Check if tab-5 exists, if not, skip to tab-6
    if (number_next === 5 && nextTab.length === 0) {
      // console.log("Tab-5 does not exist, skipping to tab-6");
      number_next = 6;
      nextTab = $(".tab-" + number_next);
    }

    if (nextTab.length > 0) {
      // console.log("Next tab exists, triggering click");
      nextTab.trigger("click");
    } else {
      // console.log("Next tab does not exist");
    }
  } else if (tab == 0) {
    number_previous = number - 1;
    // console.log("Previous tab number:", number_previous);
    
    // Check if tab-5 exists, if not, skip back to tab-4
    if (number_previous === 5 && $(".tab-" + number_previous).length === 0) {
      // console.log("Tab-5 does not exist, skipping back to tab-4");
      number_previous = 4;
    }

    const previousTab = $(".tab-" + number_previous);
    if (previousTab.length > 0) {
      // console.log("Previous tab exists, triggering click");
      previousTab.trigger("click");
    } else {
      // console.log("Previous tab does not exist");
    }
  }
}

function btn_action_fct() {
  // IMPORTANT
  $(window).scrollTop(0);
  $("body").css("overflow", "hidden");
  var ww = $(window).width() / 2 - 33 + "px";
  var wh = $(window).height() / 2 - 33 + "px";
  var divrule = $("#rule");
  if (!divrule.length) {
    var divrule = $("#flux");
  }
  var loading = $("<div></div>");
  loading.empty(); // on le vide
  loading.attr("id", "myd_loading");
  loading.css({
    position: "absolute",
    display: "block",
    top: 0,
    left: 0,
    width: $(window).width() + "px",
    height: $(window).height() + "px",
    "background-color": "white",
    "text-align": "center",
    "z-index": 100,
  });
  loading.attr("class", "myd_div_loading");

  var p = $("<p>Please wait. This can take a few minutes.</p>");
  p.css({
    position: "absolute",
    top: $(window).height() / 2 - 100,
    left: $(window).width() / 2 - 65,
    width: "130px",
    height: "60px",
    "font-weight": "bold",
  });
  loading.append(p);

  var img = $("<div></div>");
  img.attr("class", "myd_div_loading_logo");
  img.css({
    position: "absolute",
    top: "5px",
    left: "5px",
    height: "150px",
    width: "150px",
  });
  loading.append(img);
  divrule.append(loading);
}

function notification() {
  var notification = $.trim($("#zone_notification", "#notification").html());

  if (notification != "") {
    $("#notification").fadeIn();
  }
}

// Préparation de l'étape suivante
function next_step(error) {
  $(".status")
    .find("img")
    .each(function () {
      if ($(this).attr("src") != path_img + "status_online.png") {
        error++;
      }
    });

  var connector = $("#connexion_connector");
  if (connector.length) {
    // create connector
  } else {
    // other
    if ($("#rulename").val() == "" || $("#rulename").val().length < 3) {
      error++;
    }
  }

  if (error == 0) {
    $("#step_modules_confirme").removeAttr("disabled");
  } else {
    $("#step_modules_confirme").attr("disabled");
  }
}

// vérification pour la création d'un connecteur
function verif(div_clock) {
  $(".testing", div_clock).on("click", function () {
    var parent = $("#source").attr("id");
    var datas = "";
    var status = $(div_clock).parent().find(".status img");
    var solution = $(div_clock).parent().find(".liste_solution").val();
    $("input").each(function () {
      if ($(this).attr("data-param") != undefined) {
        datas +=
          $(this).attr("data-param") +
          "::" +
          $(this).val().replace(/;/g, "") +
          ";";
      }
    });

    var path_img_modal = "../../../build/images/";
    if (window.location.pathname.includes("createout")) {
      var urlInputs = "../../inputs";
      var urlCallback = "../../connector/callback/";
    } else {
      var urlInputs = "../inputs";
      var urlCallback = "../connector/callback/";
    }

    $.ajax({
      type: "POST",
      url: urlInputs,
      data: {
        champs: datas,
        parent: parent,
        solution: solution,
        mod: 2,
      },
      beforeSend: function () {
        $(status).removeAttr("src");
        if (window.location.pathname.includes("createout")) {
          $(status).attr("src", path_img_modal + "loader.gif");
        } else {
          $(status).attr("src", path_img + "loader.gif");
        }
      },
      success: function (json) {
        if (!json.success) {
          $(status).removeAttr("src");
          if (window.location.pathname.includes("createout")) {
            $(status).attr("src", path_img_modal + "status_offline.png");
          } else {
            $(status).attr("src", path_img + "status_offline.png");
          }
          $("#msg_status span.error").html(json.message);
          $("#msg_status").show();
          return false;
        }

        $.ajax({
          type: "POST",
          data: {
            solutionjs: true,
            detectjs: true,
          },
          // url: Routing.generate('connector_callback'),
          url: urlCallback,
          success: function (data) {
            param = data.split(";");

            // si popup
            if (param[0] == 1) {
              link = param[1];

              $.ajax({
                type: "POST",
                data: {
                  solutionjs: true,
                },
                // url: Routing.generate('connector_callback'),
                url: urlCallback,
                success: function (data) {
                  // if 1ere fois
                  if (data != 1) {
                    data_error_without_popup = data.split(";");
                    data_error_with_popup = data.split("-");

                    data = data_error_with_popup[0];

                    if (data != 401 && data_error_without_popup[0] != 2) {
                      var win = window.open(
                        link,
                        "Connexion",
                        "scrollbars=1,resizable=1,height=560,width=770"
                      );
                      var timer = setInterval(function () {
                        if (win.closed) {
                          clearInterval(timer);
                          if (confirm("Reconnect")) {
                            $("#source_test").click();
                          }
                        }
                      }, 1000);
                    }

                    response = data.split(";");

                    $(status).removeAttr("src");
                    $(status).attr("src", path_img + "status_offline.png");

                    if (
                      typeof data_error_without_popup[0] !== "undefined" &&
                      data_error_without_popup[0] == 2
                    ) {
                      response[0] = data_error_without_popup[1];
                    }

                    $("#msg_status span.error").html(response[0]);
                    $("#msg_status").show();
                  } else {
                    $(status).removeAttr("src");
                    $(status).attr("src", path_img + "status_online.png");
                    $("#msg_status").hide();
                    $("#msg_status span.error").html("");
                    $("#step_modules_confirme").removeAttr("disabled");
                  }
                },
              });
            } // sans popup
            else {
              if (!json.success) {
                $(status).removeAttr("src");
                if (window.location.pathname.includes("createout")) {
                  $(status).attr("src", path_img_modal + "status_offline.png");
                } else {
                  $(status).attr("src", path_img + "status_offline.png");
                }
                $("#msg_status span.error").html(json.message);
                $("#msg_status").show();
              } else {
                $(status).removeAttr("src");
                if (window.location.pathname.includes("createout")) {
                  $(status).attr("src", path_img_modal + "status_online.png");
                } else {
                  $(status).attr("src", path_img + "status_online.png");
                }
                $("#msg_status").hide();
                $("#msg_status span.error").html("");
                $("#step_modules_confirme").removeAttr("disabled");
              }
            }
          },
        });

        next_step(0);
      },
    });
  });

  $(div_clock, "input").on("keyup", function () {
    var err = 0;
    var btn = $($(this)).find(".testing");

    $($(this))
      .find("input")
      .each(function () {
        if ($(this).val().length == 0) {
          err++;
        }
      });

    if (err == 0) {
      $(btn).removeAttr("disabled");
    } else {
      $(btn).attr("disabled", "disabled");
    }
  });
}

function champs(solution, champs, parent) {
  // if we're creating a connector in the modal from the '+' button in rule creation view
  if (window.location.pathname.includes("createout")) {
    var url = "../../inputs";
    // we're creating a connector from the connector create page
  } else {
    var url = "../inputs";
  }
  $.ajax({
    type: "POST",
    url: url,
    data: {
      solution: solution,
      parent: parent,
      mod: 1,
    },
    success: function (data) {
      $(champs).html(data);
      verif(champs);
    },
  });
}

// ---- PREPARATION DE LA ZONE DRAGGABLE ------------------------------------

function prepareDrag() {
  // détecte la position du curseur dans une zone
  (function ($, undefined) {
    $.fn.getCursorPosition = function () {
      var el = $(this).get(0);
      var pos = 0;
      if ("selectionStart" in el) {
        pos = el.selectionStart;
      } else if ("selection" in document) {
        el.focus();
        var Sel = document.selection.createRange();
        var SelLength = document.selection.createRange().text.length;
        Sel.moveStart("character", -el.value.length);
        pos = Sel.text.length - SelLength;
      }
      return pos;
    };
  })(jQuery);

  $("#catalog").accordion({
    collapsible: true,
    heightStyle: "content",
  }); // liste des modules : source

  $("#catalog li").draggable({
    appendTo: "body",
    helper: "clone",
  });

  $(".champs ol")
    .droppable({
      activeClass: "ui-state-default",
      hoverClass: "ui-state-hover",
      accept: ":not(.ui-sortable-helper)",
      drop: function (event, ui) {
        var dragId = ui.draggable.find("a").attr("id");
        var str = ui.draggable.text();

        $(this).find(".placeholder").remove();

        if ($("li.ch:contains(" + str + ")", this).length < 1) {
          $(this).append(
            '<li value="' + dragId + '" class="ch">' + str + "</li>"
          );

          /*
					var formule_in = $( this ).parent().find( ".formule" );
					$( formule_in ).css('opacity','1');	*/

          //addFilter(dragId, path_info_field);

          $("li", "#fields_duplicate_target").each(function () {
            if ($(this).attr("data-active") == "false") {
              if ($(this).attr("class") == "no_active") {
                $(this).removeAttr("class");
                $(this).click();
              }
            }
          });
        }

        fields_target_hover();
      },
    })
    .sortable({
      items: "li.ch:not(.placeholder)",
      sort: function () {
        $(this).removeClass("ui-state-default");
      },
    });

  $(".champs ol").on("dblclick", "li", function () {
    if (typeof placeholder !== "undefined") {
      if ($(this).parent().children().length < 2) {
        $(this)
          .parent()
          .append('<li class="placeholder">' + placeholder + "</li>");

        /*
				var formule_in = $( this ).parent().parent().find( '.formule' );
				$( formule_in ).css('opacity','0.5'); */

        var formule_text = $(this).parent().parent().find("ul");
        formule_text.children().empty();
        $("#area_color").empty();
      }

      target = $.trim($(this).parent().parent().parent().find("h1").text());
      fields = $("#fields_duplicate_target").find(
        "li:contains(" + target + ")"
      );
      fields.removeAttr("class");
      fields.attr("data-active", "false");

      removeFilter($.trim($(this).attr("value")));
      $(this).remove();
    }
  });
}

//-- syntax color
// coloration syntaxique des formules
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

function openFormula() {
  // Dialogue formule ouverture
  $(".formule").on("click", function () {
    var li = $(this).parent().parent(); // block cible

    var champ_nom = $(this).parent().parent().parent().find("h1").text(); //nom du champ cible

    var basepathforGetFieldInfo = basepathforGetFieldInfoWithFakeId.replace('fake_id', '');

    var basepathwithouttheextrasource = basepathforGetFieldInfo.replace('/source/', '');

    // remove the space from champ_nom
    var champ_nom_without_space = champ_nom.replace(/\s+/g, '');


    var finalFieldInfoPath = basepathwithouttheextrasource + champ_nom_without_space;


    $("li.ch", li).each(function () {
      var fieldValue = $(this).attr("value");
      var basepathforGetFieldInfo = basepathforGetFieldInfoWithFakeId.replace('fake_id', '');

      var basepathwithouttheextrasource = basepathforGetFieldInfo.replace('/source/', '');

      // remove the space from champ_nom
      var champ_nom_without_space = champ_nom.replace(/\s+/g, '');


      // Modified this line to include both type and field parameters
      var finalFieldInfoPath = basepathwithouttheextrasource + 'source/' + fieldValue + '/';


      $.ajax({
        url: finalFieldInfoPath,
        method: 'GET',
        success: function(response) {
          var displayName = response.field.label || response.name;
          $("#champs_insert").append(
            '<option value="' + fieldValue + '">' +
              fieldValue + ' (' + displayName + ')</option>'
          );
        },
        error: function() {
          // Fallback to just the field value if the request fails
          $("#champs_insert").append(
            '<option value="' + fieldValue + '">' + fieldValue + '</option>'
          );
        }
      });
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

// ---- AJOUT CHAMP CIBLE  ---------------------------------------------------------------

function existField(name) {
  result = 0;
  $("#targetfields")
    .children("div")
    .each(function () {
      if ($(this).attr("id") == $.trim(name)) {
        result++;
      }
    });

  return result == 0 ? true : false;
}

// ---- /AJOUT CHAMP CIBLE  --------------------------------------------------------------

// ---- FILTRES DES CHAMPS CIBLE  --------------------------------------------------------

function hideFields() {
  value = $("#hidefields").val();

  if (value != "") {
    var show = [];
    $(".targetfield").each(function () {
      if ($(this).attr("id").toLowerCase().indexOf(value.toLowerCase()) >= 0) {
        show.push($(this).attr("id"));
      }
      if ($(this).attr("data-show") != "") {
        if ($(this).attr("data-title") != "true") {
          if (verifFields($(this).attr("id"), show)) {
            $(this).attr("data-show", "true");
            $(this).show();
          } else {
            $(this).attr("data-show", "false");
            $(this).hide();
          }
        }
      }
    });
  } else {
    $(".targetfield").each(function () {
      if ($(this).attr("data-show") != "") {
        $(this).attr("data-show", "true");
        $(this).show();
      }
    });
  }
}

function verifFields(field_id, show) {
  r = 0;
  $.each(show, function (k, val) {
    if (val == field_id) {
      r++;
    }
  });

  return r == 0 ? false : true;
}
// ---- FILTRES DES CHAMPS CIBLE  --------------------------------------------------------

// ---- FILTRES  -------------------------------------------------------------------------
// Ajoute un champ
function addFilter(field, path) {
  // ajoute un champ uniquement s'il n'existe pas
  if (existeFilter(field) == 0) {
    if (field != "my_value") {
      $("#fieldsfilter").append(
        '<li id="filter_' +
          field +
          '" class="row align-items-center mb-2"><span class="name me-2 mt-2">' +
          field +
          '</span> <a class="fancybox me-2" data-fancybox-type="iframe" href="' +
          path +
          "/source/" +
          field +
          '/"> <i class="fas fa-question-circle"></i></a> <select class="filter mt-2 me-2 form-select">' +
          filter_liste +
          '</select><input type="text" value=""  class="form-control filter-input my-3" /> </li>'
      );
    }
  }
}
// test si le champ existe déjà
function existeFilter(field) {
  view = 0;
  $("#fieldsfilter")
    .find("span.name")
    .each(function () {
      if ($.trim($(this).attr("value")) == field) {
        view++;
      }
    });

  return view;
}
// Delete a field from the Filters tab list
function removeFilter(field) {
  view = 0;
  $("#cible")
    .find("li.ch")
    .each(function () {
      if ($(this).attr("value") == field) {
        view++;
      }
    });
  if (view < 2) {
    $("#filter_" + field).remove();
  }
}
// ---- FILTRES  -------------------------------------------------------------------------

// ---- PARAMS ET VALIDATION  ------------------------------------------------------------
function recup_filter() {
  let filter = [];
  $("#fieldsfilter li").each(function () {
    let field_target = $.trim($(this).find(".name").text());

    let field_filter = "";
    let selectElement = $(this).find("input[name*='anotherFieldInput']");
    if (selectElement.length > 0) {
      field_filter = $.trim(selectElement.val());
    }

    let field_value = "";
    let inputElement = $(this).find("input[name*='textareaFieldInput']");
    if (inputElement.length > 0) {
      field_value = $.trim(inputElement.val());
    }

    if (field_target || field_filter || field_value) {
      filter.push({
        target: field_target,
        filter: field_filter,
        value: field_value,
      });
    }
  });
  return filter;
}

// Récupère tous les champs
function recup_champs() {
  var resultat = "";

  $("#cible")
    .find("li.ch")
    .each(function () {
      var li = $(this);
      var fields = li.parent().parent().parent();
      var r = $(fields).find("h1").text();

      resultat += $.trim(r) + "[=]" + $.trim(li.attr("value")) + ";";
    });

  return resultat;
}

// Récupère toutes les formules
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

// Récupère la liste des relations
function recup_relation() {
  var relations = [];
  $(".rel tr.line-relation", "#relation").each(function () {
    tr = $(this);
    $($(this))
      .find(".title")
      .each(function () {
        var name = $(this).attr("data-value");
        var valueRule = tr.find(".lst_rule_relate").val();
        var valueSource = tr.find(".lst_source_relate").val();
        var valueEmpty = tr.find(".parent_error_empty").val();
        var valueMissing = tr.find(".parent_error_missing").val();
        var valueparent = 0;
        if (
          valueRule !== "" &&
          valueSource !== "" &&
          valueEmpty !== "" &&
          valueMissing !== ""
        ) {
          relations.push({
            target: name,
            errorEmpty: valueEmpty,
            errorMissing: valueMissing,
            rule: valueRule,
            source: valueSource,
            parent: valueparent,
          });
        }
      });
  });

  $(".rel tr.line-parent_relation", "#relation").each(function () {
    tr = $(this);
    $($(this))
      .find(".parent_search_field")
      .each(function () {
        var name = tr.find(".parent_search_field").val();
        var valueRule = tr.find(".parent_rule").val();
        var valueSource = tr.find(".parent_source_field").val();
        var valueEmpty = tr.find(".parent_error_empty").val();
        var valueMissing = tr.find(".parent_error_missing").val();
        var valueparent = 1;
        if (
          valueRule !== "" &&
          valueSource !== "" &&
          name !== "" &&
          valueEmpty !== "" &&
          valueMissing !== ""
        ) {
          relations.push({
            target: name,
            rule: valueRule,
            source: valueSource,
            errorEmpty: valueEmpty,
            errorMissing: valueMissing,
            parent: valueparent,
          });
        }
      });
  });
  return relations;
}

// Récupère la liste des params
function recup_params() {
  var params = [];
  $(".params", "#params").each(function () {
    var name = $(this).attr("name");
    var value = $(this).val();

    params.push({
      name: name,
      value: value,
    });
  });

  return params;
}

function recup_fields_relate() {
  var relate_fields = [];
  $("#fields_duplicate_target li").each(function () {
    if ($(this).attr("data-active") === "true") {
      relate_fields.push($.trim($(this).text()));
    }
  });

  return relate_fields;
}

function duplicate_fields_error() {
  error = 0;
  $("li", "#fields_duplicate_target").each(function () {
    if ($(this).attr("class") === "no_active") {
      error++;
    }
  });

  return error == 0 ? true : false;
}

// vérifie les champs obligatoires
function require() {
  // We don't test the fields anymore because it block rule in update only for example
  return true;
}

// Liste le nombre d'erreurs de champs require pas remplis
function require_params() {
  var r = 0;
  $("#params")
    .find(".require")
    .each(function () {
      if ($(this).val() === "") {
        r++;
      }
    });

  if (r == 0) {
    return true;
  } else {
    return false;
  }
}

// Détecte les relations non remplis
function require_relate() {
  // We don't test the fields anymore because fields relate required could be filled in the field mapping
  return true;
}

// test si le champ à été selectionné pour pouvoir être utilisé comme référence afin d'éviter les doublons
function fields_exist(fields_duplicate) {
  var exist = 0;
  // On parcours tous les champs de la liste
  $("#cible")
    .find("li.ch")
    .each(function () {
      var li = $(this);
      // On récupère le nom du champ parent du champ
      var fields = li.parent().parent().parent();
      var r = $.trim($(fields).find("h1").text());

      // Si le nom du champ parent est le même que celui passé en paramètre, on incrémente le compteur
      if (fields_duplicate == r) {
        exist++;
      }
    });

  return exist;
}

// Affiche ou cache les boutons de la liste des flux en fonction du nombre de flux sélectionnés
function showBtnFlux(massFluxTab) {
  if (massFluxTab.length == 0) {
    $("#cancelflux").hide();
    // if cancelflux-grey is defined, then hide it
    if ($("#cancelflux-grey").length > 0) {
      $("#cancelflux-grey").hide();
    }
    $("#reloadflux").hide();
    $("#cancelreloadflux").hide();
    if ($("#reloadflux-grey").length > 0) {
      $("#reloadflux-grey").hide();
    }
    $("#unlockAllFlux").hide();
    if ($("#unlockAllFlux-grey").length > 0) {
      $("#unlockAllFlux-grey").hide();
    }
  } else {
    $("#cancelflux").show();
    if ($("#cancelflux-grey").length > 0) {
      $("#cancelflux-grey").show();
    }
    $("#reloadflux").show();
    if ($("#reloadflux-grey").length > 0) {
      $("#reloadflux-grey").show();
    }
    $("#cancelreloadflux").show();
    $("#unlockAllFlux").show();
    if ($("#unlockAllFlux-grey").length > 0) {
      $("#unlockAllFlux-grey").show();
    }
  }
}

// Add or remove the id of the flux to the array
// If the id is already in the array, it is removed
// If the id is not in the array, it is added
// The number of elements in the array is displayed in the button
function massAddFlux(id, cond, massFluxTab) {
  if (id != "") {
    if (cond == false) {
      massFluxTab.push(id);
    } else {
      massFluxTab.splice($.inArray(id, massFluxTab), 1);
    }
  }
  // Display the number of elements in the array
  $("#cancelflux").find("span").html(massFluxTab.length);
  $("#reloadflux").find("span").html(massFluxTab.length);
  $("#cancelreloadflux").find("span").html(massFluxTab.length);
  $("#unlockAllFlux").find("span").html(massFluxTab.length);
}

$(document).ready(function() {
	$('.removeFilters').click(function() {
		// Get the class list of the clicked element
		var classList = $(this).attr('class').split(/\s+/);
// console.log(classList);

		// Find the filter class (it's the last class in the list)
		var filterClass = classList[classList.length - 1];
// console.log(filterClass);

		// Get the stored filters from local storage
		var storedFilters = JSON.parse(localStorage.getItem('storedFilters'));
// console.log(storedFilters);


		// Save the updated filters back to local storage
		localStorage.setItem('storedFilters', JSON.stringify(storedFilters));
// console.log(localStorage.getItem('storedFilters'));

		    // Make an AJAX request to the server to remove the filter from the session
			$.ajax({
				url: path_remove_filter,
				method: 'POST',
				data: { filterName: 'FluxFilter' + toCamelCase(filterClass) },
				success: function(response) {
					if (response.status === 'success') {
// console.log('Filter successfully removed Argonien');
						
						// Clear the form field
						var formFieldName = 'combined_filter[document][' + filterClass + ']';
						$('input[name="' + formFieldName + '"]').val('');
// console.log('Filter input cleared');
					} else {
// console.log('Error removing filter: ' + response.message);
					}
				}
			});
	});
});

$(document).ready(function () {
  $(".edit-button").hover(
    function () {
      $(this).find(".fa-lock").hide();
      $(this).find(".fa-unlock").show();
    },
    function () {
      $(this).find(".fa-lock").show();
      $(this).find(".fa-unlock").hide();
    }
  );

  $(".edit-button").click(function () {
    var jobId = $(this).data("job-id");
    var ajaxUrl = clearReadJobLockUrl.replace("PLACEHOLDER_ID", jobId);

    $.ajax({
      url: ajaxUrl,
      type: "POST",
      success: function (response) {
        if (response.read_job_lock === "") {
          $(".job_lock_" + jobId).show();
        } else {
          $(".job_lock_" + jobId).hide();
        }
      },
      error: function () {
        alert(read_job_lock_error);
      },
    });
  });
});

function toCamelCase(str) {
    // Split the string into words
    var words = str.split('_');

    // Convert each word to title case (except for the first one), and join them back together
    return words[0] + words.slice(1).map(function(word) {
        return word.charAt(0).toUpperCase() + word.slice(1);
    }).join('');
}

// Save the modified field data by using an ajax request
function saveInputFlux(div, link) {

	fields = div.attr('data-value');
	div.attr('data-value');
	value = $('#' + fields);

	// Ajax request to save the data in the database
	$.ajax({
		type: "POST",
		url: link,
		data: {
			flux: $('#flux_target').attr('data-id'),
			rule: $('#flux_target').attr('data-rule'),
			fields: fields,
			value: value.val()
		},
		success: function (val) {
			div.parent().append('<span>' + val + '</span>');
			div.remove();
			value.remove();
		}
	});
}

document.addEventListener('DOMContentLoaded', function () {
  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltipTriggerList.forEach(tooltipTriggerEl => {
      new bootstrap.Tooltip(tooltipTriggerEl);
  });
});

// Function wizard handling
$(document).ready(function() {
    const functionSelect = $('#function-select');
    const lookupOptions = $('#lookup-options');
    const lookupRule = $('#lookup-rule');
    const lookupField = $('#lookup-field');
    const flagFunctionWizardEnd = $('#flag-function-wizard-end');
    // Add tooltip container after the select
    $('<div id="function-tooltip" class="tooltip-box" style="display:none; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; margin-top: 5px;"></div>')
        .insertAfter(flagFunctionWizardEnd);

    // Show tooltip when option changes
    functionSelect.on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const tooltip = selectedOption.data('tooltip');
        const tooltipBox = $('#function-tooltip');
        
        if (tooltip) {
            tooltipBox.text(tooltip).show();
        } else {
            tooltipBox.hide();
        }

        const selectedFunction = $(this).val();
        
        if (selectedFunction === 'lookup') {
            lookupOptions.show();
            
            // Populate rules dropdown
            $.ajax({
                url: lookupgetrule,
                method: 'GET',
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
            insertFunction(selectedFunction);
        }
    });

    // When a rule is selected
    lookupRule.on('change', function() {
        const selectedRule = $(this).val();

        
        if (selectedRule) {
            // Get fields for selected rule
            $.ajax({
                url: lookupgetfieldroute,
                method: 'GET',
                success: function(fields) {
                    lookupField.empty();
                    lookupField.append('<option value="">' + translations.selectField + '</option>');
                    // Filter fields to only show those from the selected rule
                    const filteredFields = fields.filter(field => field.rule_id === selectedRule);
                    filteredFields.forEach(field => {
                        lookupField.append(`<option value="${field.id}">${field.name}</option>`);
                    });
                    lookupField.prop('disabled', false);
                }
            });
        } else {
            lookupField.prop('disabled', true);
        }
    });

    // When a field is selected
    lookupField.on('change', function() {
        if ($(this).val()) {
            // Get the selected field's name (without the rule part in parentheses)
            const selectedOption = $(this).find('option:selected');
            const fieldName = selectedOption.text().split(' (')[0];
            const lookupFormula = `lookup({${fieldName}}, "${lookupRule.val()}"`;
            insertFunction(lookupFormula);
        }
    });

    function insertFunction(funcText) {
        const areaInsert = $('#area_insert');
        const position = areaInsert.getCursorPosition();
        const content = areaInsert.val();
        
        // Add parentheses only if not already part of the funcText
        const suffix = funcText.endsWith('"') ? ' )' : '( ';
        
        const newContent = 
            content.substr(0, position) +
            funcText +
            suffix +
            content.substr(position);
            
        areaInsert.val(newContent);
        colorationSyntax();
        theme(style_template);
    }
});
