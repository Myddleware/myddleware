
console.log("workflow-action-label-rule-change.js loaded");
document.addEventListener('DOMContentLoaded', function() {
    // Get the action select element
    const actionSelect = document.querySelector('select[name$="[action]"]');
    // Get the rule field label
    const ruleLabel = document.querySelector('label[for$="_ruleId"]');
    
    // Initial check
    updateRuleLabel();
    
    // Add event listener for changes
    actionSelect.addEventListener('change', updateRuleLabel);
    
    function updateRuleLabel() {
        if (actionSelect.value === 'changeData') {
            ruleLabel.textContent = 'Changing Rule';
        } else {
            ruleLabel.textContent = 'Generating Rule';
        }
    }
});