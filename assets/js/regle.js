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

// Load jQuery UI via CDN as fallback since webpack imports aren't working
if (typeof $.ui === 'undefined') {
  const jqueryUIScript = document.createElement('script');
  jqueryUIScript.src = 'https://code.jquery.com/ui/1.14.1/jquery-ui.min.js';
  jqueryUIScript.async = false;
  document.head.appendChild(jqueryUIScript);
  
  const jqueryUICSS = document.createElement('link');
  jqueryUICSS.rel = 'stylesheet';
  document.head.appendChild(jqueryUICSS);
}

if (window.location.pathname.includes("public")) {
  global.path_img = '../../build/images/';
} else {
  global.path_img = '/build/images/';
}

$(function () {
  // ----------------------------- LOADING EXÉCUTION RÈGLES / ANNULATION FLUX
  $(window).on("load", function () {
    // Bouton action "Exécuter règles actives"
    $("#exec_all", "#rule").on("click", function (e) {
      if (confirm(confirm_exec_all)) {
        btn_action_fct();
      } else {
        e.preventDefault();
      }
    });
    // Bouton action "Relancer les erreurs"
    $("#exec_error", "#rule").on("click", function (e) {
      if (confirm(confirm_exec_error)) {
        btn_action_fct();
      } else {
        e.preventDefault();
      }
    });
    // Boutons d'actions (affichage d'un loading) : "Annuler transfert" / "Exécuter la règle"
    $(".btn_action_loading").on("click", function () {
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

  $(".tooltip").qtip(); // Infobull

  notification();

  // ----------------------------- LISTE DES RÈGLES
  if (typeof question !== "undefined" && question) {
    $("#listrule .delete").on("click", function () {
      var answer = confirm(question);
      return !!answer;
    });
  }

  // ----------------------------- TABS (jQuery UI) -----------------------------

  function initializeTabs() {
    if (typeof onglets !== "undefined" && onglets && typeof $.fn.tabs === "function") {
      $("#tabs").tabs(onglets);
      return true;
    }
    setTimeout(initializeTabs, 200);
    return false;
  }

   // Try to initialize tabs with longer delay for CDN loading
  setTimeout(initializeTabs, 1000);

  // ----------------------------- CRÉATION CONNECTEUR : SELECT SOLUTION -------

  // Messages de connexion
  $("#source_msg").hide();
  $("#cible_msg").hide();
  $("#msg_status").hide();

  // Changement de solution source / cible
  $(document).on("change", "#soluce_cible, #soluce_source", function () {
    var val = $(this).val();
    var parent = $(this).parent().attr("id"); // "source" ou "cible"
    var val2 = val ? val.split("_") : [];

    $("#msg_status").hide();

    if (!val) {
      $("#" + parent + "_msg").hide();
      $(this).parent().find(".picture").empty();
      $(this).parent().find(".champs").empty();
      $(this).parent().find(".help").empty();
      return;
    }

    // Affichage du logo de la solution
    $(this).parent().find(".picture img").remove();
    $(this).parent().find(".help").empty();
    var solution = val2[0] ? val2[0] : val;
    if (window.location.pathname.includes("createout")) {
      var path_img_modal = "../../../build/images/";
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

    if ($.isNumeric(val2[1])) {
      $.ajax({
        type: "POST",
        url: "../inputs",
        data: {
          solution: val,
          parent: parent,
          name: $("#rulename").length ? $("#rulename").val() : '',
          mod: 3,
        },
        beforeSend: function () {
          $("#" + parent + "_status img").removeAttr("src");
          $("#" + parent + "_status img").attr("src", path_img + "loader.gif");
        },
        success: function (msg) {
          var r = msg.split(";");

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
      // Sinon : on récupère les champs de connexion à afficher (mod=1)
      champs(val, $(this).parent().find(".champs"), parent);
    }
  });

  // ----------------------------- FLUX ----------------------------------------

  var massFluxTab = [];
  showBtnFlux(massFluxTab);

  // Sélection de masse des flux
  $("#massselectall").on("change", function () {
    var remove = !$(this).is(":checked");

    $("input", ".listepagerflux td").each(function () {
      if ($(this).attr("disabled") !== "disabled") {
        if ($(this).is(":checked")) {
          if (remove) {
            var id = $(this).attr("name");
            massAddFlux(id, true, massFluxTab);
            $(this).prop("checked", false);
          }
        } else if (!remove) {
          var idAdd = $(this).attr("name");
          massAddFlux(idAdd, false, massFluxTab);
          $(this).prop("checked", true);
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

  // Annuler les flux sélectionnés
  $("#cancelflux").on("click", function () {
    if (confirm(confirm_cancel)) {
      $.ajax({
        type: "POST",
        url: mass_cancel,
        beforeSend: function () {
          btn_action_fct();
        },
        data: {
          ids: massFluxTab,
        },
        success: function () {
          location.reload();
        },
      });
    }
  });

  // Annuler + remettre en file les flux sélectionnés
  $("#cancelreloadflux").on("click", function () {
    if (confirm(confirm_cancel)) {
      $.ajax({
        type: "POST",
        url: mass_cancel,
        beforeSend: function () {
          btn_action_fct();
        },
        data: {
          ids: massFluxTab,
          reload: true,
        },
        success: function () {
          location.reload();
        },
      });
    }
  });

  // Déverrouiller tous les flux sélectionnés
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
        error: function () {
          alert('An error occurred while unlocking documents.');
        },
      });
    }
  });

  // Relancer les flux sélectionnés
  $("#reloadflux").on("click", function () {
    if (confirm(confirm_reload)) {
      $.ajax({
        type: "POST",
        url: mass_run,
        beforeSend: function () {
          btn_action_fct();
        },
        data: {
          ids: massFluxTab,
        },
        success: function () {
          location.reload();
        },
      });
    }
  });

  // Édition inline d'un champ de flux (double-clic)
  $("#flux_target").on("dblclick", "li", function () {
    if (
      $("#gblstatus").attr("data-gbl") == "error" ||
      $("#gblstatus").attr("data-gbl") == "open"
    ) {
      var verifClass = $(this).attr("class");
      var first = $("li:first", "#flux_target").attr("class");
      var classe = $(this).attr("class");

      if (
        typeof verifClass !== "undefined" &&
        ((first === "undefined") != classe) !== "undefined"
      ) {
        // Empêcher d'ouvrir plusieurs inputs en même temps
        if ($(this).find('input').length > 0) {
          alert('Please close the first one before adding a new one.');
        } else {
          var value = $(this).find(".value").text().trim();
          $(this).find(".value").remove();
          var newElement = $(this).append(
            '<input id="' +
              classe +
              '" type="text" value="' +
              value +
              '" />' +
              '<button type="submit" data-value="' +
              classe +
              '" class="btn-group btn-group-xs load">' +
              '<i class="fa fa-check-circle"></i>' +
              '</button>'
          );
          $(this).append(newElement);
        }
      }
    }
  });

  // Sauvegarde de l'édition inline
  $("#flux_target").on("click", ".load", function () {
    saveInputFlux($(this), inputs_flux);
  });

  // Upload Files (Fancybox)
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
      responseType: 'blob'
    },
    success: function (blob) {
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `documents_export_${new Date().toISOString().slice(0,19).replace(/[:]/g, '')}.csv`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      a.remove();
    },
    error: function () {
      alert('Failed to export CSV file. Please try again.');
    }
  });
});

// ---- UPLOAD WSDL / FICHIERS  --------------------------------------------------------------------------

function name_file_upload() {
  $.ajax({
    type: "POST",
    url: Routing.generate("upload", {
      solution: "all",
    }),
    success: function (data) {
      var r = data.split(";");
      if (r[0] == 1) {
        $("#param_wsdl").val(r[1]);
      }

      // Createout
      $("#source_test").removeAttr("disabled");
    },
  });
}

function confirm_upload() {
  $("#link_wsdl").on("click", function () {
    if ($("#param_wsdl").val() != "") {
      if (confirm("Souhaitez-vous abandonner l'ancien fichier de configuration ?")) {
        return true;
      } else {
        return false;
      }
    }
  });
}

confirm_upload();

// ----------------------------- LOADING OVERLAY GLOBAL -------------------------------

function btn_action_fct() {
  // Nettoyage d'un éventuel overlay existant
  $('.myd_div_loading').remove();
  $('body').css('overflow', '');

  $(window).scrollTop(0);
  $("body").css("overflow", "hidden");

  var divrule = $("#rule");
  if (!divrule.length) {
    divrule = $("#flux");
  }

  var loading = $("<div></div>");
  loading.empty();
  loading.attr("id", "myd_loading");

  var loadingCSS = {
    position: "absolute",
    display: "block",
    top: 0,
    left: 0,
    width: $(window).width() + "px",
    height: $(window).height() + "px",
    "background-color": "white",
    "text-align": "center",
    "z-index": 100,
  };

  loading.css(loadingCSS);
  loading.attr("class", "myd_div_loading");

  var p = $("<p>Please wait. This can take a few minutes.</p>");
  var pCSS = {
    position: "absolute",
    top: $(window).height() / 2 - 100,
    left: $(window).width() / 2 - 65,
    width: "130px",
    height: "60px",
    "font-weight": "bold",
  };

  p.css(pCSS);
  loading.append(p);

  var img = $("<div></div>");
  img.attr("class", "myd_div_loading_logo");
  var imgCSS = {
    position: "absolute",
    top: "5px",
    left: "5px",
    height: "150px",
    width: "150px",
  };

  img.css(imgCSS);
  loading.append(img);

  divrule.append(loading);

  // Nettoyage automatique dans différents cas
  $(window).on('popstate.myddleware', function () {
    cleanupLoading();
  });

  var currentPath = window.location.pathname;
  var checkInterval = setInterval(function () {
    if (window.location.pathname !== currentPath) {
      cleanupLoading();
      clearInterval(checkInterval);
    }
  }, 100);

  $(window).on('unload.myddleware', function () {
    cleanupLoading();
  });

  setTimeout(function () {
    if ($('#myd_loading').length > 0) {
      cleanupLoading();
    }
  }, 30000);

  function cleanupLoading() {
    $(window).off('popstate.myddleware');
    $(window).off('unload.myddleware');

    $('#myd_loading').fadeOut(300, function () {
      $(this).remove();
    });

    $('body').css('overflow', '');
  }
}

// Cleanup si le script est rechargé
$(document).ready(function () {
  $(window).off('popstate.myddleware');
  $(window).off('unload.myddleware');
  $('.myd_div_loading').remove();
  $('body').css('overflow', '');
});
// ----------------------------- NOTIFICATION -----------------------------------

function notification() {
  var notification = $.trim($("#zone_notification", "#notification").html());
  if (notification !== "") {
    $("#notification").fadeIn();
  }
}

// -------------------- ÉTAPE SUIVANTE (création connecteur / règle) -----------

function next_step(error) {
  $(".status")
    .find("img")
    .each(function () {
      if ($(this).attr("src") != path_img + "status_online.png") {
        error++;
      }
    });

  var connector = $("#connexion_connector");
  if (!connector.length) {
    if ($("#rulename").length && ($("#rulename").val() == "" || $("#rulename").val().length < 3)) {
      error++;
    }
  }

  if (error == 0) {
    $("#step_modules_confirme").removeAttr("disabled");
  } else {
    $("#step_modules_confirme").attr("disabled");
  }
}

// --------- VÉRIFICATION / TEST DE CONNEXION (création connecteur) -----------

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
    var urlInputs;
    var urlCallback;
    if (window.location.pathname.includes("createout")) {
      urlInputs = "../../inputs";
      urlCallback = "../../connector/callback/";
    } else {
      urlInputs = "../inputs";
      urlCallback = "../connector/callback/";
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
          url: urlCallback,
          success: function (data) {
            var param = data.split(";");

            // Avec popup
            if (param[0] == 1) {
              var link = param[1];

              $.ajax({
                type: "POST",
                data: {
                  solutionjs: true,
                },
                url: urlCallback,
                success: function (dataCallback) {
                  if (dataCallback != 1) {
                    var data_error_without_popup = dataCallback.split(";");
                    var data_error_with_popup = dataCallback.split("-");

                    var dataCode = data_error_with_popup[0];

                    if (dataCode != 401 && data_error_without_popup[0] != 2) {
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

                    var response = dataCode.split(";");

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
            } else {
              // Sans popup
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

  // Activation / désactivation du bouton de test selon les champs requis
  $(div_clock, "input").on("keyup", function () {
    var err = 0;
    var btn = $($(this)).find(".testing");

    $($(this))
      .find("input")
      .each(function () {
        if ($(this).val().length == 0) {
          var fieldParam = $(this).attr("data-param");

          // Champ requis sauf s'il est listé dans nonRequiredFields
          if (!nonRequiredFields.includes(fieldParam)) {
            err++;
          }
        }
      });

    if (err == 0) {
      $(btn).removeAttr("disabled");
    } else {
      $(btn).attr("disabled", "disabled");
    }
  });
}

// Récupération des champs de connexion pour un connecteur
function champs(solution, champs, parent) {
  var url;
  if (window.location.pathname.includes("createout")) {
    url = "../../inputs";
  } else {
    url = "../inputs";
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

// ----------------------------- COLORATION SYNTAXIQUE FORMULES ----------------
// (Gardé pour compatibilité si la page règles l'utilise encore)

function colorationSyntax() {
  $("#area_color").html($("#area_insert").val());

  if ($("#area_insert").val() == "") {
    $("#area_color").empty();
  }

  $("#area_color").each(function () {
    var text = $(this).html();

    text = text.replace(/\===/g, "[begin] class='operateur'[f]===[end]");
    text = text.replace(/\==/g, "[begin] class='operateur'[f]==[end]");
    text = text.replace(/\!==/g, "[begin] class='operateur'[f]!==[end]");
    text = text.replace(/\!=/g, "[begin] class='operateur'[f]!=[end]");
    text = text.replace(/\!/g, "[begin] class='operateur'[f]![end]");
    text = text.replace(/\./g, "[begin] class='operateur'[f].[end]");
    text = text.replace(/\:/g, "[begin] class='operateur'[f]:[end]");
    text = text.replace(/\?/g, "[begin] class='operateur'[f]?[end]");
    text = text.replace(/\(/g, "[begin] class='operateur'[f]([end]");
    text = text.replace(/\)/g, "[begin] class='operateur'[f])[end]");
    text = text.replace(/\//g, "[begin] class='operateur'[f]/[end]");
    text = text.replace(/\+/g, "[begin] class='operateur'[f]+[end]");
    text = text.replace(/\-/g, "[begin] class='operateur'[f]-[end]");
    text = text.replace(/\*/g, "[begin] class='operateur'[f]*[end]");
    text = text.replace(/\>=/g, "[begin] class='operateur'[f]>=[end]");
    text = text.replace(/\>/g, "[begin] class='operateur'[f]>[end]");
    text = text.replace(/\<=/g, "[begin] class='operateur'[f]<=[end]");
    text = text.replace(/\</g, "[begin] class='operateur'[f]<[end]");
    text = text.replace(/\{/g, "[begin] class='variable'[f]{");
    text = text.replace(/\}/g, "}[end]");
    text = text.replace(
      /\"([\s\S]*?)\"/g,
      "[begin] class='chaine'[f]\"$1\"[end]"
    );
    text = text.replace(/\[begin\]/g, "<span");
    text = text.replace(/\[f\]/g, ">");
    text = text.replace(/\[end\]/g, "</span>");

    $("#area_color").html(text);
  });

  $(".operateur", "#area_color").each(function () {
    if ($(this).parent().attr("class") == "chaine") {
      $(this).before($(this).html());
      $(this).remove();
    }
  });

  $(".variable", "#area_color").each(function () {
    if ($(this).parent().attr("class") == "chaine") {
      $(this).before($(this).html());
      $(this).remove();
    }
  });
}

// ----------------------------- BOUTONS MASS ACTION FLUX ----------------------

function showBtnFlux(massFluxTab) {
  if (massFluxTab.length == 0) {
    $("#cancelflux").hide();
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

// Ajout / retrait d'un flux de la sélection
function massAddFlux(id, cond, massFluxTab) {
  if (id != "") {
    if (cond === false) {
      massFluxTab.push(id);
    } else {
      massFluxTab.splice($.inArray(id, massFluxTab), 1);
    }
  }
  $("#cancelflux").find("span").html(massFluxTab.length);
  $("#reloadflux").find("span").html(massFluxTab.length);
  $("#cancelreloadflux").find("span").html(massFluxTab.length);
  $("#unlockAllFlux").find("span").html(massFluxTab.length);
}

// ----------------------------- SUPPRESSION FILTRES SAUVEGARDÉS --------------
$(document).ready(function () {
  $('.removeFilters').click(function () {
    var classList = $(this).attr('class').split(/\s+/);
    var filterClass = classList[classList.length - 1];

    var storedFilters = JSON.parse(localStorage.getItem('storedFilters'));
    localStorage.setItem('storedFilters', JSON.stringify(storedFilters));

    $.ajax({
      url: path_remove_filter,
      method: 'POST',
      data: { filterName: 'FluxFilter' + toCamelCase(filterClass) },
      success: function (response) {
        if (response.status === 'success') {
          var formFieldName = 'combined_filter[document][' + filterClass + ']';
          $('input[name="' + formFieldName + '"]').val('');
        }
      }
    });
  });
});
// ----------------------------- JOB LOCK (EDIT BUTTON) ------------------------

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
  var words = str.split('_');
  return words[0] + words.slice(1).map(function (word) {
    return word.charAt(0).toUpperCase() + word.slice(1);
  }).join('');
}

// ----------------------------- SAUVEGARDE INPUT FLUX -------------------------

function saveInputFlux(div, link) {
  var fields = div.attr('data-value').trim();
  var inputElement = div.closest('li').find('input');
  var inputValue = inputElement.val();

  $.ajax({
    type: "POST",
    url: link,
    data: {
      flux: $('#flux_target').attr('data-id'),
      rule: $('#flux_target').attr('data-rule'),
      fields: fields,
      value: inputValue
    },
    success: function (val) {
      div.parent().append('<span class="value">' + val + '</span>');
      div.remove();
      inputElement.remove();
    }
  });
}
// ----------------------------- BOOTSTRAP TOOLTIP -----------------------------

document.addEventListener('DOMContentLoaded', function () {
  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltipTriggerList.forEach(tooltipTriggerEl => {
    new bootstrap.Tooltip(tooltipTriggerEl);
  });
});