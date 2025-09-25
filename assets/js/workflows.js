$(document).ready(function () {

  let isEditMode = $("#form_ruleId").val() !== '';
  let isEditModeValue = false;
  let workflowActionFields = null;

  $('fieldset:has(#form_targetFieldValues)').hide();
  var selectedAction = $("#form_action").val();
  
  // Handle target fields data in edit mode - moved from tryLoadConfig
  if (isEditMode && typeof targetFieldsData !== 'undefined' && targetFieldsData && targetFieldsData.length > 0 && selectedAction === "changeData") {
    const dynamicFieldsContainer = document.getElementById("dynamicFieldsContainer");
    const addFieldButton = document.getElementById("addFieldButton");
    
    dynamicFieldsContainer.innerHTML = '';
    dynamicFieldsContainer.style.display = "block";
    addFieldButton.style.display = "block";
    
    targetFieldsData.forEach(function (fieldData) {
      addNewTargetFieldWithValue(fieldData.field, fieldData.value);
    });
  }
  
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
    // Default configurations for each action type
    const defaultConfigs = {
      updateStatus: {
        Action: 'updateStatus',
        Status: 'Yes',
        Rule: 'No',
        SearchField: 'No',
        SearchValue: 'No',
        Subject: 'No',
        Message: 'Yes',
        To: 'No',
        'Target Field': 'No',
        'New Value': 'No',
        Rerun: 'No',
        'Multiple run': 'Yes',
        Active: 'Yes'
      },
      generateDocument: {
        Action: 'generateDocument',
        Status: 'No',
        Rule: 'Yes',
        SearchField: 'Yes',
        SearchValue: 'Yes',
        Subject: 'No',
        Message: 'No',
        To: 'No',
        'Target Field': 'No',
        'New Value': 'No',
        Rerun: 'Yes',
        'Multiple run': 'Yes',
        Active: 'Yes'
      },
      sendNotification: {
        Action: 'sendNotification',
        Status: 'No',
        Rule: 'No',
        SearchField: 'No',
        SearchValue: 'No',
        Subject: 'Yes',
        Message: 'Yes',
        To: 'Yes',
        'Target Field': 'No',
        'New Value': 'No',
        Rerun: 'No',
        'Multiple run': 'Yes',
        Active: 'Yes'
      },
      transformDocument: {
        Action: 'transformDocument',
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
      },
      rerun: {
        Action: 'rerun',
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
      },
      changeData: {
        Action: 'changeData',
        Status: 'No',
        Rule: 'No',
        SearchField: 'No',
        SearchValue: 'No',
        Subject: 'No',
        Message: 'No',
        To: 'No',
        'Target Field': 'Yes',
        'New Value': 'Yes',
        Rerun: 'No',
        'Multiple run': 'Yes',
        Active: 'Yes'
      }
    };

    if (!workflowActionFields) {
// console.log('Using default configuration for action:', actionName);
      return defaultConfigs[actionName] || defaultConfigs.updateStatus;
    }

    return workflowActionFields.find(config => config.Action === actionName) || defaultConfigs[actionName] || defaultConfigs.updateStatus;
  }

  // Function to get field container ID from field name
  function getContainerId(field) {
    switch(field) {
      case 'Status': return '#status-container';
      case 'Rule': return '#rule-container';
      case 'SearchField': return '#search-field-container';
      case 'SearchValue': return '#search-value-container';
      case 'Subject': return '#subject-container';
      case 'Message': return '#message-container';
      case 'To': return '#to-container';
      case 'Rerun': return '#rerun-container';
      case 'Multiple run': return '#multiple-runs-container';
      case 'Active': return '#active-container';
      case 'Target Field': return '#targetFieldContainer';
      case 'New Value': return '#targetFieldValueContainer';
      default: return null;
    }
  }

  // Function to log field visibility
  function logFieldVisibility(field, shouldShow, containerId) {
// console.log(`Field: ${field.padEnd(15)} | Status: ${shouldShow ? 'SHOWING' : 'HIDDEN '.padEnd(7)} | Container: ${containerId}`);
  }

  // Function to toggle field visibility based on configuration
  function toggleFieldVisibility(actionConfig) {
// console.log('\n=== Field Visibility for Action: ' + actionConfig.Action + ' ===');
// console.log('Field'.padEnd(15) + ' | Status  | Container');
// console.log('─'.repeat(50));

    // Log visibility for all possible fields
    ALL_FIELDS.forEach(field => {
      const containerId = getContainerId(field);
      if (containerId) {
        const shouldShow = actionConfig[field] === 'Yes';
        const container = $(containerId);
        
        // Log the status regardless of whether we found the element
        logFieldVisibility(field, shouldShow, containerId);
        
        // Toggle visibility if element exists
        if (container.length) {
          if (shouldShow) {
            container.show();
          } else {
            container.hide();
          }
        } else {
// console.log(`Warning: Container not found for ${field} (${containerId})`);
        }
      }
    });

    // Special handling for Target Field and New Value in changeData action
    const isChangeData = actionConfig.Action === 'changeData';
    
    // Handle the add field button visibility
    $('#addFieldButton').toggle(isChangeData);
    
    // Handle the dynamic fields container visibility
    const dynamicContainer = $('#dynamicFieldsContainer');
    if (isChangeData) {
      dynamicContainer.show();
    } else {
      dynamicContainer.hide().empty();
    }

// console.log('─'.repeat(50));
// console.log('Dynamic Fields  | Status  | Container');
// console.log('─'.repeat(50));
// console.log(`Add Button      | ${isChangeData ? 'SHOWING' : 'HIDDEN '} | #addFieldButton`);
// console.log(`Fields Container| ${isChangeData ? 'SHOWING' : 'HIDDEN '} | #dynamicFieldsContainer`);
  }

  // Try multiple possible paths for the JSON file
  const possiblePaths = [
    '/assets/js/workflow-action-fields.json',
    '/build/workflow-action-fields.json',
    '/workflow-action-fields.json'
  ];

  function tryLoadConfig(paths) {
    if (paths.length === 0) {
      console.warn('Could not load configuration from any path, using defaults');
      return;
    }

    const path = paths[0];
    $.ajax({
      url: (window.location.origin + path).replace("http://", "https://"),
      dataType: 'json',
      success: function(data) {
// console.log('Successfully loaded workflow action fields configuration from:', path);
        workflowActionFields = data;
        
        // Handle initial load after JSON is loaded
        const selectedAction = $("#form_action").val();
// console.log('\n=== INITIAL PAGE LOAD ===');
// console.log('Selected Action:', selectedAction);
        const initialConfig = getFieldConfig(selectedAction);
        toggleFieldVisibility(initialConfig);
      },
      error: function() {
// console.log('Failed to load from:', path);
        tryLoadConfig(paths.slice(1));
      }
    });
  }

  // Start trying to load the configuration
  tryLoadConfig(possiblePaths);

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
  const config = getFieldConfig(actionValue);
  toggleFieldVisibility(config);

  if ((!isEditMode || isEditModeValue) && actionValue !== 'changeData') {
    $("#form_ruleId").val('').trigger('change');
  }
  isEditModeValue = true;

  if (actionValue === 'changeData' && window.workflowRuleMap) {
    const wfId = $('#form_Workflow').val();
    const ruleId = wfId ? (window.workflowRuleMap[wfId] || '') : '';
    if (ruleId) {
      setRuleFieldValue(ruleId);
    }
  }

  if (actionValue === 'changeData' && window.workflowRuleMap) {
    const wfId = $('#form_Workflow').val();
    const ruleId = wfId ? (window.workflowRuleMap[wfId] || '') : '';
    if (ruleId) {
      setRuleFieldValue(ruleId);
    }
  }
});
});

document.addEventListener("DOMContentLoaded", function () {
  function toggleIcon(button, content) {
        if (!button || !content) {
      return;
    }
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

  // Check if required elements exist before proceeding
  if (!formAction) {
    console.log("form_action element not found, skipping workflow action field initialization");
    return;
  }

  // Cacher les champs et le bouton au démarrage
  if (targetFieldContainer) targetFieldContainer.style.display = "none";
  if (targetFieldValueContainer) targetFieldValueContainer.style.display = "none";
  if (addFieldButton) addFieldButton.style.display = "none";
  if (dynamicFieldsContainer) dynamicFieldsContainer.style.display = "none";

  // Fonction pour afficher/masquer les champs et le bouton en fonction de la valeur de form_action
  function toggleTargetFields() {
    if (!formAction) {
      console.log("formAction element is null, cannot toggle target fields");
      return;
    }
    
    const actionValue = formAction.value;

    if (actionValue === "changeData") {
        // Show the add button and container for changeData action
        if (addFieldButton) addFieldButton.style.display = "block";
        if (dynamicFieldsContainer) dynamicFieldsContainer.style.display = "block";
    } else {
        // Hide for other actions
        if (targetFieldContainer) targetFieldContainer.style.display = "none";
        if (targetFieldValueContainer) targetFieldValueContainer.style.display = "none";
        if (addFieldButton) addFieldButton.style.display = "none";
        if (dynamicFieldsContainer) {
          dynamicFieldsContainer.style.display = "none";
          dynamicFieldsContainer.innerHTML = '';
        }
    }
  }

  toggleTargetFields();

  // Ecouter le changement de valeur sur le champ "Action"
  formAction.addEventListener("change", function () {
    toggleTargetFields();
  });

  // Ecouter le changement de la règle
  if (ruleIdField) {
    ruleIdField.addEventListener("change", function () {
      const ruleId = ruleIdField.value;

      // Vérifier à nouveau si l'action est bien "changeData" avant d'afficher les champs
      if (formAction && formAction.value === "changeData") {
      if (ruleId !== "") {
        if (addFieldButton) addFieldButton.style.display = "block";
        if (dynamicFieldsContainer) dynamicFieldsContainer.style.display = "block";

        // Envoyer la requête AJAX pour récupérer les champs liés à la règle sélectionnée
        const updatedUrl = workflowTargetFieldUrl.replace("ruleFields", ruleId);
        $.ajax({
          url: updatedUrl.replace("http://", "https://"),
          type: "GET",
          success: function (data) {
            if (dynamicFieldsContainer) {
              dynamicFieldsContainer.innerHTML = '';
              addNewTargetField(data.fields);
            }
          },
          error: function (xhr, status, error) {
            console.error("Erreur lors de la récupération des champs:", status, error);
          },
        });
      } else {
        if (addFieldButton) addFieldButton.style.display = "none";
        if (dynamicFieldsContainer) {
          dynamicFieldsContainer.style.display = "none";
          dynamicFieldsContainer.innerHTML = '';
        }
      }
    } else {
      if (addFieldButton) addFieldButton.style.display = "none";
      if (dynamicFieldsContainer) {
        dynamicFieldsContainer.style.display = "none";
        dynamicFieldsContainer.innerHTML = '';
      }
    }
    });
  } else {
    console.log("form_ruleId element not found, skipping rule change listener");
  }

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
    if (dynamicFieldsContainer) {
      dynamicFieldsContainer.appendChild(newFieldRow);
    }
  }

  // Ecouter le clic sur le bouton "Add Field"
  if (addFieldButton && ruleIdField) {
    addFieldButton.addEventListener('click', function () {
      const ruleId = ruleIdField.value;

    $.ajax({
      url: (workflowTargetFieldUrl.replace("ruleFields", ruleId).replace("http://", "https://")),
      type: "GET",
      success: function (data) {
        addNewTargetField(data.fields);
      },
      error: function (xhr, status, error) {
        console.error("Erreur lors de la récupération des champs :", status, error);
      },
    });
    });
  } else {
    console.log("addFieldButton or form_ruleId element not found, skipping add field listener");
  }
});

function setRuleFieldValue(ruleId) {
  const $rule = $('#form_ruleId');
  if (!$rule.length) {
    return;
  }
  if (ruleId && !$rule.find('option[value="' + ruleId + '"]').length) {
    $rule.append($('<option>', { value: ruleId, text: '(auto) ' + ruleId }));
  }

  $rule.val(ruleId || '');

  if ($rule.data('select2')) $rule.trigger('change.select2');  
  else $rule.trigger('change');
}

$('#form_Workflow').on('change', function () {
  const wfId = $(this).val();

  if (!window.workflowRuleMap) {
   return;
  }

  const ruleId = window.workflowRuleMap[wfId] || '';

  setRuleFieldValue(ruleId);

  setTimeout(() => {
    const cur = $('#form_ruleId').val();
    if (cur !== (ruleId || '')) {
      setRuleFieldValue(ruleId);
    }
  }, 50);
});
