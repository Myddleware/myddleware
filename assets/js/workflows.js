$(document).ready(function () {
  // Function to hide or show the Rule field
  function toggleRuleField() {
    var actionValue = $("#form_action").val();

    if (
      actionValue === "transformDocument" ||
      actionValue === "sendNotification" ||
      actionValue === "updateStatus"
    ) {
      $("#form_ruleId").closest(".form-group").hide();
    } else {
      $("#form_ruleId").closest(".form-group").show();
    }
  }

  toggleRuleField();

  $("#form_action").on("change", function () {
    toggleRuleField();
  });

  function fetchFilteredData() {
    var workflowName = $("#workflow_name").val();
    var ruleName = $("#rule_name").val();

    $.ajax({
      url: workflowListUrl,
      type: "GET",
      data: {
        workflow_name: workflowName,
        rule_name: ruleName,
      },
      success: function (response) {
        console.log("Réponse reçue :", response);
        $("#workflowTableContainer").html(response);

        // $('#workflowTableContainer').html($(response).find('#workflowTableContainer').html());
      },
      error: function (xhr, status, error) {
        console.error("Erreur lors de la recherche :", status, error);
      },
    });
  }

  $("#workflow_name, #rule_name").on("keyup", function () {
    fetchFilteredData();
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

  // WORKFLOWACTION TARGETFIELD
  const ruleIdField = document.getElementById("form_ruleId");
  const targetFieldContainer = document.getElementById("targetFieldContainer");
  const targetFieldSelect = document.getElementById("form_targetField");
  const targetFieldValueContainer = document.getElementById("targetFieldValueContainer"); // Le nouveau champ de saisie de valeur

  // Cacher au démarrage
  targetFieldContainer.style.display = "none";
  targetFieldValueContainer.style.display = "none";
  const addFieldButton = document.getElementById('addFieldButton');
  const dynamicFieldsContainer = document.getElementById("dynamicFieldsContainer");

  // Masquer le bouton et le conteneur des champs dynamiques au démarrage
  addFieldButton.style.display = "none";
  dynamicFieldsContainer.style.display = "none";

  ruleIdField.addEventListener("change", function () {
    console.log('toto');
    
    const ruleId = ruleIdField.value;

    if (ruleId !== "") {
      // Afficher le bouton et le conteneur pour ajouter des champs
      addFieldButton.style.display = "block";
      dynamicFieldsContainer.style.display = "block";

      // Envoyer la requête AJAX pour récupérer les champs liés à la règle sélectionnée
      const updatedUrl = workflowTargetFieldUrl.replace("ruleFields", ruleId);
      $.ajax({
        url: updatedUrl,
        type: "GET",
        success: function (data) {
          console.log("Réponse reçue :", data);
          dynamicFieldsContainer.innerHTML = ''; // Vider les anciens champs

          // Ajouter un premier champ Target Field (select) avec les options disponibles
          addNewTargetField(data.fields); 
        },
        error: function (xhr, status, error) {
          console.error("Erreur lors de la récupération des champs:", status, error);
        },
      });
    } else {
      // Cacher les champs si aucune règle n'est sélectionnée
      addFieldButton.style.display = "none";
      dynamicFieldsContainer.style.display = "none";
      dynamicFieldsContainer.innerHTML = ''; // Vider les champs si aucune règle n'est sélectionnée
    }
  });

  // Fonction pour ajouter un champ targetField (select) et targetFieldValue (input)
  function addNewTargetField(fields) {
    const newFieldRow = document.createElement('div');
    newFieldRow.classList.add('row', 'mb-4');

    // Champ targetField (select avec options)
    const targetFieldDiv = document.createElement('div');
    targetFieldDiv.classList.add('col-md-6');
    const targetFieldLabel = document.createElement('label');
    targetFieldLabel.innerText = 'Target Field';
    const targetFieldSelect = document.createElement('select');
    targetFieldSelect.name = 'targetFields[]';
    targetFieldSelect.classList.add('form-control');

    // Ajouter les options au select
    fields.forEach(function(field) {
      const option = document.createElement('option');
      option.value = field;
      option.text = field;
      targetFieldSelect.appendChild(option);
    });

    targetFieldDiv.appendChild(targetFieldLabel);
    targetFieldDiv.appendChild(targetFieldSelect);

    // Champ targetFieldValue
    const targetFieldValueDiv = document.createElement('div');
    targetFieldValueDiv.classList.add('col-md-6');
    const targetFieldValueLabel = document.createElement('label');
    targetFieldValueLabel.innerText = 'New Value';
    const targetFieldValueInput = document.createElement('input');
    targetFieldValueInput.name = 'targetFieldValues[]';
    targetFieldValueInput.type = 'text';
    targetFieldValueInput.classList.add('form-control');
    targetFieldValueDiv.appendChild(targetFieldValueLabel);
    targetFieldValueDiv.appendChild(targetFieldValueInput);

    // Bouton de suppression
    const removeButtonDiv = document.createElement('div');
    removeButtonDiv.classList.add('col-md-12', 'd-flex', 'justify-content-end', 'mt-2');
    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.classList.add('btn', 'btn-danger');
    removeButton.innerText = 'Supprimer';
    removeButton.addEventListener('click', function () {
      newFieldRow.remove();
    });
    removeButtonDiv.appendChild(removeButton);

    newFieldRow.appendChild(targetFieldDiv);
    newFieldRow.appendChild(targetFieldValueDiv);
    newFieldRow.appendChild(removeButtonDiv);
    dynamicFieldsContainer.appendChild(newFieldRow);
  }

  addFieldButton.addEventListener('click', function () {

    $.ajax({
      url: workflowTargetFieldUrl.replace("ruleFields", ruleIdField.value),
      type: "GET",
      success: function (data) {
        addNewTargetField(data.fields);
      },
      error: function (xhr, status, error) {
        console.error("Erreur lors de la récupération des champs:", status, error);
      },
    });
  });
});