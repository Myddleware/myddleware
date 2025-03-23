global.path_img = '../../build/images/';
console.log("rule-detail.js loaded");
$(function () {
  // ----------------------------- AFFICHAGE DU LOADING LANCEMENT REGLE / ANNULER FLUX
  $(window).on("load", function () {

  // CONFIRMATION TO DELETE THE RULE
  if (typeof question !== "undefined" && question) {
    $("#listrule .delete").on("click", function () {
      var answer = confirm(question);
      console.log('answer is', answer);
      if (answer) {
        return true;
      } else {
        return false;
      }
    });
  }

}); // end of window.on("load", function () { line 5

});  // end of $(function () { line 3
