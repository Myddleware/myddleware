$(document).ready(function () {

  let isEditMode = $("#form_ruleId").val() !== '';
  let isEditModeValue = false;
  let workflowActionFields = null; // Initialize at the top level

  $('fieldset:has(#form_targetFieldValues)').hide();
  var selectedAction = $("#form_action").val();
  
  // All possible fields we want to track
  const ALL_FIELDS = [
    'Status',
    'Rule',
    'SearchField',
    'SearchValue',
    'Subject',
    'Message',
    'To',
    'Rerun',
    'Multiple run',
    'Active',
    'Target Field',
    'New Value'
  ];

  // Function to get field configuration for an action
  function getFieldConfig(actionName) {
    // Default configuration if JSON hasn't loaded yet or action not found
    const defaultConfig = {
      Action: actionName,
      Status: 'No',
      Rule: 'No',
      SearchField: 'No',
      SearchValue: 'No',
      Subject: 'No',
      Message: 'No',
      To: 'No',
      'Target Field': 'No',
      'New Value': 'No',
      Rerun: 'No',
      'Multiple run': 'Yes',
      Active: 'Yes'
    };

    if (!workflowActionFields) {
      console.log('Warning: Using default configuration as workflow action fields have not loaded yet');
      return defaultConfig;
    }

    return workflowActionFields.find(config => config.Action === actionName) || defaultConfig;
  }

  // Function to get field ID from field name
  function getFieldId(field) {
    switch(field) {
      case 'Status': return '#form_status';
      case 'Rule': return '#form_ruleId';
      case 'SearchField': return '#form_searchField';
      case 'SearchValue': return '#form_searchValue';
      case 'Subject': return '#form_subject';
      case 'Message': return '#form_message';
      case 'To': return '#form_to';
      case 'Rerun': return '#form_rerun';
      case 'Multiple run': return '#form_multipleRuns';
      case 'Active': return '#form_active';
      case 'Target Field': return '#targetFieldContainer';
      case 'New Value': return '#targetFieldValueContainer';
      default: return null;
    }
  }

  // Function to log field visibility
  function logFieldVisibility(field, shouldShow, fieldId) {
    console.log(`Field: ${field.padEnd(15)} | Status: ${shouldShow ? 'SHOWING' : 'HIDDEN '.padEnd(7)} | ID: ${fieldId}`);
  }

  // Function to toggle field visibility based on configuration
  function toggleFieldVisibility(actionConfig) {
    console.log('\n=== Field Visibility for Action: ' + actionConfig.Action + ' ===');
    console.log('Field'.padEnd(15) + ' | Status  | ID');
    console.log('─'.repeat(50));

    // Log visibility for all possible fields
    ALL_FIELDS.forEach(field => {
      const fieldId = getFieldId(field);
      if (fieldId) {
        const shouldShow = actionConfig[field] === 'Yes';
        const formGroup = $(fieldId).closest('.form-group');
        
        // Log the status regardless of whether we found the element
        logFieldVisibility(field, shouldShow, fieldId);
        
        // Toggle visibility if element exists
        if (formGroup.length) {
          formGroup.toggle(shouldShow);
        }
      }
    });

    // Special handling for Target Field and New Value in changeData action
    const isChangeData = actionConfig.Action === 'changeData';
    $('#addFieldButton').toggle(isChangeData);
    $('#dynamicFieldsContainer').toggle(isChangeData);

    console.log('─'.repeat(50));
    console.log('Dynamic Fields  | Status  | Container');
    console.log('─'.repeat(50));
    console.log(`Add Button      | ${isChangeData ? 'SHOWING' : 'HIDDEN '} | #addFieldButton`);
    console.log(`Fields Container| ${isChangeData ? 'SHOWING' : 'HIDDEN '} | #dynamicFieldsContainer`);
    
    if (!isChangeData) {
      $('#dynamicFieldsContainer').empty();
    }
  }

  // Load the workflow action fields configuration from the JSON file
  $.ajax({
    url: window.location.origin + '/build/workflow-action-fields.json', // Updated path
    dataType: 'json',
    success: function(data) {
      console.log('Successfully loaded workflow action fields configuration');
      workflowActionFields = data;
      
      // Handle initial load after JSON is loaded
      const selectedAction = $("#form_action").val();
      console.log('\n=== INITIAL PAGE LOAD ===');
      console.log('Selected Action:', selectedAction);
      const initialConfig = getFieldConfig(selectedAction);
      toggleFieldVisibility(initialConfig);

      // Handle target fields data in edit mode
      if (isEditMode && typeof targetFieldsData !== 'undefined' && targetFieldsData && targetFieldsData.length > 0 && selectedAction === "changeData") {
        dynamicFieldsContainer.innerHTML = '';
        dynamicFieldsContainer.style.display = "block";
        addFieldButton.style.display = "block";
        console.log('\n=== Loading Edit Mode Target Fields ===');
        targetFieldsData.forEach(function (fieldData, index) {
          console.log(`Target Field ${index + 1}: ${fieldData.field} = ${fieldData.value}`);
          addNewTargetFieldWithValue(fieldData.field, fieldData.value);
        });
      }
    },
    error: function(jqXHR, textStatus, errorThrown) {
      console.error('Failed to load workflow action fields configuration:', {
        status: textStatus,
        error: errorThrown,
        url: this.url
      });
      console.log('Using default field configuration');
      
      // Still initialize the page with default configuration
      const selectedAction = $("#form_action").val();
      console.log('\n=== INITIAL PAGE LOAD (with defaults) ===');
      console.log('Selected Action:', selectedAction);
      const initialConfig = getFieldConfig(selectedAction);
      toggleFieldVisibility(initialConfig);
    }
  });

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
  
  // Handle action change
  $("#form_action").on("change", function () {
    const actionValue = $(this).val();
    console.log('\n=== ACTION CHANGED ===');
    console.log('New Action:', actionValue);
    const config = getFieldConfig(actionValue);
    toggleFieldVisibility(config);
    
    if (!isEditMode || isEditModeValue) {
      $("#form_ruleId").val('');
    }
    isEditModeValue = true;
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
        // Show the add button and container for changeData action
        addFieldButton.style.display = "block";
        dynamicFieldsContainer.style.display = "block";
    } else {
        // Hide for other actions
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