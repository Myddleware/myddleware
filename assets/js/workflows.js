$(document).ready(function () {

  let isEditMode = $("#form_ruleId").val() !== '';
  let isEditModeValue = false; 

  $('fieldset:has(#form_targetFieldValues)').hide();
  var selectedAction = $("#form_action").val();
  
  if (isEditMode && typeof targetFieldsData !== 'undefined' && targetFieldsData && targetFieldsData.length > 0 && selectedAction === "changeData") {
    targetFieldsData.forEach(function (fieldData) {
      addNewTargetFieldWithValue(fieldData.field, fieldData.value);
    });
  }

  function addNewTargetFieldWithValue(fieldName, fieldValue) {
  
    const newFieldRow = document.createElement('div');
    newFieldRow.classList.add('row', 'mb-4');
  
    const targetFieldDiv = document.createElement('div');
    targetFieldDiv.classList.add('col-md-6');
    const targetFieldLabel = document.createElement('label');
    targetFieldLabel.innerText = 'Target Field';
    const targetFieldSelect = document.createElement('select');
    targetFieldSelect.name = 'targetFields[]';
    targetFieldSelect.classList.add('form-control');
  
    // Ajouter l'option pré-sélectionnée
    const option = document.createElement('option');
    option.value = fieldName;
    option.text = fieldName;
    option.selected = true;
    targetFieldSelect.appendChild(option);
  
    targetFieldDiv.appendChild(targetFieldLabel);
    targetFieldDiv.appendChild(targetFieldSelect);
  
    const targetFieldValueDiv = document.createElement('div');
    targetFieldValueDiv.classList.add('col-md-6');
    const targetFieldValueLabel = document.createElement('label');
    targetFieldValueLabel.innerText = 'New Value';
    const targetFieldValueInput = document.createElement('input');
    targetFieldValueInput.name = 'targetFieldValues[]';
    targetFieldValueInput.type = 'text';
    targetFieldValueInput.classList.add('form-control');
  
    // Ici, on vérifie que la valeur est bien affectée
    targetFieldValueInput.value = fieldValue;
  
    targetFieldValueDiv.appendChild(targetFieldValueLabel);
    targetFieldValueDiv.appendChild(targetFieldValueInput);
  
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
  
    document.getElementById("dynamicFieldsContainer").appendChild(newFieldRow);

    document.getElementById("targetFieldContainer").style.display = 'block';
    document.getElementById("targetFieldValueContainer").style.display = 'block';
    document.getElementById("dynamicFieldsContainer").style.display = 'block';
    document.getElementById("addFieldButton").style.display = 'block';
    document.getElementById("targetFieldContainer").style.display = 'none';
    document.getElementById("targetFieldValueContainer").style.display = 'none';
  }
  
  // Function to hide or show the Rule field
  function toggleRuleField() {
    var actionValue = $("#form_action").val();
    if (!isEditMode || isEditModeValue) {
      $("#form_ruleId").val('');
  }

  isEditModeValue = true;

    if (
      actionValue === "transformDocument" ||
      actionValue === "rerun" ||
      actionValue === "sendNotification" ||
      actionValue === "updateStatus"
    ) {
      $("#form_ruleId").closest(".form-group").hide();
    } else {
      $("#form_ruleId").closest(".form-group").show();
    }

  }


  $("#form_action").on("change", function () {
    toggleRuleField();

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
  const targetFieldValueContainer = document.getElementById("targetFieldValueContainer");
  const addFieldButton = document.getElementById('addFieldButton');
  const dynamicFieldsContainer = document.getElementById("dynamicFieldsContainer");
  const formAction = document.getElementById("form_action");

  // Cacher les champs et le bouton au démarrage
  targetFieldContainer.style.display = "none";
  targetFieldValueContainer.style.display = "none";
  addFieldButton.style.display = "none";
  dynamicFieldsContainer.style.display = "none";

  // Fonction pour afficher/masquer les champs et le bouton en fonction de la valeur de form_action
  function toggleTargetFields() {
    const actionValue = formAction.value;

    if (actionValue === "changeData") {
      addFieldButton.style.display = "none";
      dynamicFieldsContainer.style.display = "none";
      targetFieldContainer.style.display = "none";
      targetFieldValueContainer.style.display = "none";
    } else {
      targetFieldContainer.style.display = "none";
      targetFieldValueContainer.style.display = "none";
      addFieldButton.style.display = "none";
      dynamicFieldsContainer.style.display = "none";
      dynamicFieldsContainer.innerHTML = '';
    }
  }

  toggleTargetFields();

  // Ecouter le changement de valeur sur le champ "Action"
  formAction.addEventListener("change", function () {
    toggleTargetFields();
  });

  // Ecouter le changement de la règle
  ruleIdField.addEventListener("change", function () {
    const ruleId = ruleIdField.value;

    // Vérifier à nouveau si l'action est bien "changeData" avant d'afficher les champs
    if (formAction.value === "changeData") {
      if (ruleId !== "") {
        addFieldButton.style.display = "block";
        dynamicFieldsContainer.style.display = "block";

        // Envoyer la requête AJAX pour récupérer les champs liés à la règle sélectionnée
        const updatedUrl = workflowTargetFieldUrl.replace("ruleFields", ruleId);
        $.ajax({
          url: updatedUrl,
          type: "GET",
          success: function (data) {
            dynamicFieldsContainer.innerHTML = '';
            addNewTargetField(data.fields); 
          },
          error: function (xhr, status, error) {
            console.error("Erreur lors de la récupération des champs:", status, error);
          },
        });
      } else {
        addFieldButton.style.display = "none";
        dynamicFieldsContainer.style.display = "none";
        dynamicFieldsContainer.innerHTML = '';
      }
    } else {
      addFieldButton.style.display = "none";
      dynamicFieldsContainer.style.display = "none";
      dynamicFieldsContainer.innerHTML = '';
    }
  });

  // Fonction pour ajouter un champ targetField (select) et targetFieldValue (input)
  function addNewTargetField(fields) {
    const newFieldRow = document.createElement('div');
    newFieldRow.classList.add('row', 'mb-4');
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

  // Ecouter le clic sur le bouton "Add Field"
  addFieldButton.addEventListener('click', function () {
    const ruleId = ruleIdField.value;

    $.ajax({
      url: workflowTargetFieldUrl.replace("ruleFields", ruleId),
      type: "GET",
      success: function (data) {
        addNewTargetField(data.fields);
      },
      error: function (xhr, status, error) {
        console.error("Erreur lors de la récupération des champs :", status, error);
      },
    });
  });
});