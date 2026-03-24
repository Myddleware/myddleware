const $ = require('jquery');

$(function() {
    var debounceTimer, currentXhr;

    var $select = $('#rule_name_searchbar').selectize({
        plugins: ['remove_button'],
        persist: false,
        create: false,
        onItemAdd: function() {
            this.$wrapper.find('.item').hide();
            updateBadges(this);
        },
        onItemRemove: function() {
            updateBadges(this);
        },
        onInitialize: function() {
            if (this.getValue().length > 0) {
                $("#clear_rule_search").show();
                updateBadges(this);
            }
        }
    });

    var selectizeControl = $select[0].selectize;

    function updateBadges(control) {
        var $container = $('#selected-rules-badges');
        if (!$container.length) return;
        $container.empty();
        control.getValue().forEach(function(val) {
            var badge = $(
                '<span class="mapping-src-badge rounded-pill px-2 me-2 mb-2 d-inline-flex align-items-center">' +
                    '<span class="mapping-src-badge-label">' + val + '</span>' +
                    '<button type="button" class="p-0 ms-2 mapping-src-badge-remove remove-badge" data-value="' + val + '">×</button>' +
                '</span>'
            );
            $container.append(badge);
        });
    }

    $(document).on('click', '.remove-badge', function() {
        selectizeControl.removeItem($(this).data('value'));
    });

    $("#rule_name_searchbar").on("change", function() {
        var q = $(this).val();
        var url = $(this).data("url");
        if (!q || q.length === 0) {
            $("#clear_rule_search").hide();
            return;
        }
        $("#clear_rule_search").show();

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            if (currentXhr) currentXhr.abort();
            $("#tbody_rule_list").html('<tr><td colspan="100%" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div> Loading…</td></tr>');
            currentXhr = $.get(url, { rule_name: q.join(',') }, function(data) {
                var $resp = $(data);
                $("#tbody_rule_list").html($resp.find("#tbody_rule_list").html() || '<tr><td colspan="100%" class="text-center">No rules found</td></tr>');
                $("#pagination_container").html($resp.find("#pagination_container").html());
            });
        }, 400);
    });

    $("#clear_rule_search").on("click", function() {
        selectizeControl.clear();
        window.location.href = $("#rule_name_searchbar").data("url");
    });

    $(".form-check-input.toggle-switch-rule").on('change', function() {
        $.post($(this).attr('title'));
    });

    $(document).on('click', '.edit-button-name-list', function() {
        $(this).closest('td').find('.edit-form-container').show();
    });

    $(document).on('click', '.close-button-name-list', function() {
        $(this).closest('.edit-form-container').hide();
    });

    $(document).on('submit', '.edit-form-name-list', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $display = $form.closest('td').find('.rule-name-display');
        var newValue = $form.find('input[name="ruleName"]').val().trim();
        var ruleId = $form.find('input[name="ruleId"]').val();
        var updateUrl = $form.attr('action');

        if (newValue === "") {
            alert("Rule name is empty");
            return;
        }

        $.ajax({
            type: 'GET',
            url: window.checkRuleNameUrlList,
            data: { ruleId: ruleId, ruleName: newValue },
            success: function (response) {
                if (response.exists) {
                    alert("This rule name already exists. Please choose a different name.");
                } else {
                    $.ajax({
                        type: 'POST',
                        url: updateUrl,
                        data: { ruleId: ruleId, ruleName: newValue },
                        success: function () {
                            $display.text(newValue);
                            $form.closest('.edit-form-container').hide();
                        },
                        error: function () {
                            alert("An error occurred while updating the rule name.");
                        }
                    });
                }
            },
            error: function () {
                alert("An error occurred while checking the rule name.");
            }
        });
    });
});