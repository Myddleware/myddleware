global.path_img = '../../build/images/';

$(function () {
  // ----------------------------- AFFICHAGE DU LOADING LANCEMENT REGLE / ANNULER FLUX
  $(window).on("load", function () {

$("#exec_all", "#rule").on("click", function (e) {
    if (confirm(confirm_exec_all)) {
      // Clic sur OK
      btn_action_fct();
    } else {
      e.preventDefault();
    }
  }); // end of $("#exec_all", "#rule").on("click", function (e) { line 7

  // Bouton action "Relancer les erreurs"
  $("#exec_error", "#rule").on("click", function (e) {
    if (confirm(confirm_exec_error)) {
      // Clic sur OK
      btn_action_fct();
    } else {
      e.preventDefault();
    }
  }); // end of $("#exec_error", "#rule").on("click", function (e) { line 17

}); // end of window.on("load", function () { line 5

});  // end of $(function () { line 3
