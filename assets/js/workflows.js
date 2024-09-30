$(document).ready(function () {

    // Function to hide or show the Rule field
    function toggleRuleField() {
      var actionValue = $('#form_action').val();
  
      if (actionValue === 'transformDocument' || actionValue === 'sendNotification' || actionValue === 'updateStatus') {
        $('#form_ruleId').closest('.form-group').hide();
      } else {
        $('#form_ruleId').closest('.form-group').show();
      }
    }
  
    toggleRuleField();
  
    $('#form_action').on('change', function () {
      toggleRuleField(); 
    });

  function fetchFilteredData() {
    var workflowName = $('#workflow_name').val();
    var ruleName = $('#rule_name').val();


    $.ajax({
      url: workflowListUrl, 
      type: 'GET',
      data: {
        workflow_name: workflowName,
        rule_name: ruleName
      },
      success: function (response) {
        console.log("Réponse reçue :", response);
        $('#workflowTableContainer').html(response);

        // $('#workflowTableContainer').html($(response).find('#workflowTableContainer').html());
      },
      error: function (xhr, status, error) {
        console.error("Erreur lors de la recherche :", status, error);
      }
    });
  }

  $('#workflow_name, #rule_name').on('keyup', function () {
    fetchFilteredData();
  });

  $(".workflow-check-input").on("change", function () {
    var $input = $(this);
    var entityId = $input.data("id");
    var entityType = $input.data("type");
    var newState = $input.is(":checked") ? 1 : 0;

    var pathArray = window.location.pathname.split("/");
    var basePath = window.location.origin + "/" + pathArray[1] + "/" + pathArray[2];
    var currentUrl = `${basePath}/${entityType}/${entityType}/toggle/${entityId}`;

    $.ajax({
      url: currentUrl,
      type: "POST",
      data: { newState: newState },
      beforeSend: function () {
        console.log(`Before sending the request for ${entityType} ID: ${entityId}`);
      },
      success: function (response) {
        console.log(`Success response received for ${entityType} ID: ${entityId}`, response);
      },
      error: function (xhr, status, error) {
        console.error(`Error received for ${entityType} ID: ${entityId}`, xhr, status, error);
        alert("Erreur lors de la bascule");
      },
      complete: function (xhr, status) {
        console.log(`Request completed for ${entityType} ID: ${entityId}`, status);
      },
    });
  });


  $(".workflow-check-input").on("change", function () {
    var $input = $(this);
    var entityId = $input.data("id");
    var entityType = $input.data("type");
    var newState = $input.is(":checked") ? 1 : 0;

    var pathArray = window.location.pathname.split("/");
    var basePath =
      window.location.origin + "/" + pathArray[1] + "/" + pathArray[2];
    var currentUrl = `${basePath}/${entityType}/${entityType}/toggle/${entityId}`;

    $.ajax({
      url: currentUrl,
      type: "POST",
      data: { newState: newState },
      beforeSend: function () {
        console.log(
          `Before sending the request for ${entityType} ID: ${entityId}`
        );
      },
      success: function (response) {
        console.log(
          `Success response received for ${entityType} ID: ${entityId}`,
          response
        );
      },
      error: function (xhr, status, error) {
        console.error(
          `Error received for ${entityType} ID: ${entityId}`,
          xhr,
          status,
          error
        );
        alert("Erreur lors de la bascule");
      },
      complete: function (xhr, status) {
        console.log(
          `Request completed for ${entityType} ID: ${entityId}`,
          status
        );
      },
    });
  });
});

document.addEventListener("DOMContentLoaded", function () {
  function toggleIcon(button, content) {
    if (content.classList.contains("show")) {
      button.innerHTML = '<i class="fa fa-minus"></i>';
    } else {
      button.innerHTML = '<i class="fa fa-plus"></i>';
    }
  }

  // Panneau principal
  var mainToggleButton = document.querySelector(".toggle-button");
  var mainCollapseContent = document.getElementById("workflow-content");

  if (mainCollapseContent) {
    mainCollapseContent.addEventListener("shown.bs.collapse", function () {
      toggleIcon(mainToggleButton, mainCollapseContent);
    });

    mainCollapseContent.addEventListener("hidden.bs.collapse", function () {
      toggleIcon(mainToggleButton, mainCollapseContent);
    });
  }

  // Sous-panneau des actions
  var subToggleButton = document.querySelectorAll(".toggle-button")[1];
  var subCollapseContent = document.getElementById("actions-content");

  if (subCollapseContent) {
    subCollapseContent.addEventListener("shown.bs.collapse", function () {
      toggleIcon(subToggleButton, subCollapseContent);
    });

    subCollapseContent.addEventListener("hidden.bs.collapse", function () {
      toggleIcon(subToggleButton, subCollapseContent);
    });
  }

  // Panneau des logs
  var logsToggleButton = document.querySelectorAll(".toggle-button")[2];
  var logsCollapseContent = document.getElementById("logs-content");

  if (logsCollapseContent) {
    logsCollapseContent.addEventListener("shown.bs.collapse", function () {
      toggleIcon(logsToggleButton, logsCollapseContent);
    });

    logsCollapseContent.addEventListener("hidden.bs.collapse", function () {
      toggleIcon(logsToggleButton, logsCollapseContent);
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    var tooltipTriggerList = [].slice.call(
      document.querySelectorAll('[data-toggle="tooltip"]')
    );
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  });

  
});
