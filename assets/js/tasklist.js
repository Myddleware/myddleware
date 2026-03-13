const $ = require('jquery');
require('selectize');
require('../vendors/dtsel/dtsel');

$(function () {
    var currentXhr = null;
    var filterDebounce = null;

    function renderParamTags(selectizeInstance) {
        var $container = $('#param-tags');
        $container.empty();
        var values = selectizeInstance.getValue();
        if (!Array.isArray(values)) values = values ? [values] : [];
        values.forEach(function (val) {
            var label = selectizeInstance.getItem(val).text() || val;
            var $tag = $('<span class="param-tag rounded-pill d-inline-flex align-items-center"></span>');
            var $label = $('<span class="param-tag-label"></span>').text(label).attr('title', label);
            var $remove = $('<button type="button" class="p-0 ms-2 param-tag-remove">&times;</button>');
            $remove.on('click', function () {
                selectizeInstance.removeItem(val);
            });
            $tag.append($label).append($remove);
            $container.append($tag);
        });
        setTimeout(function () {
            var tagsHeight = $container[0].offsetHeight || 0;
            $('.table-wrapper-task').css('margin-top', tagsHeight > 0 ? tagsHeight + 8 + 'px' : '');
        }, 0);
    }

    $('#filter_param').selectize({
        allowEmptyOption: true,
        maxItems: null,
        onChange: function () {
            renderParamTags(this);
            applyFilters();
        },
        onInitialize: function () {
            renderParamTags(this);
        }
    });

    $('.task-filter-select:not(#filter_param)').selectize({
        allowEmptyOption: true,
        onChange: function () {
            applyFilters();
        }
    });

    // Initialize dtsel date pickers (date only)
    $('.task-filter-datepicker').each(function () {
        var instance = new dtsel.DTS('#' + this.id, {
            showTime: false,
            dateFormat: 'yyyy-mm-dd',
            direction: 'BOTTOM'
        });

        // dtbox is created lazily on first focus — hook into its value handler
        var hooked = false;
        $(this).on('focus', function () {
            if (!hooked && instance.dtbox) {
                hooked = true;
                instance.dtbox.addHandler('value', function () {
                    debouncedApply();
                });
            }
        });
    });

    // Debounce helper to avoid rapid-fire filtering
    function debouncedApply() {
        clearTimeout(filterDebounce);
        filterDebounce = setTimeout(function () {
            applyFilters();
        }, 100);
    }

    function collectFilters() {
        var filters = {};

        // Parameter: multi-select, join as comma-separated
        var paramEl = document.getElementById('filter_param');
        if (paramEl && paramEl.selectize) {
            var paramVal = paramEl.selectize.getValue();
            if (Array.isArray(paramVal) && paramVal.length > 0) {
                filters['param'] = paramVal.join(',');
            } else if (typeof paramVal === 'string' && paramVal !== '') {
                filters['param'] = paramVal;
            }
        }

        // Status: single-select
        var statusEl = document.getElementById('filter_status');
        if (statusEl && statusEl.selectize) {
            var statusVal = statusEl.selectize.getValue();
            if (statusVal !== '' && statusVal !== null && statusVal !== undefined) {
                filters['status'] = statusVal;
            }
        }

        // Date inputs
        ['begin', 'end'].forEach(function (field) {
            var val = $.trim($('#filter_' + field + '_date').val());
            if (val) {
                filters[field + '_date'] = val;
            }
        });

        return filters;
    }

    function applyFilters() {
        var filters = collectFilters();

        // Update browser URL
        var params = new URLSearchParams(filters);
        var newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        history.replaceState(null, '', newUrl);

        // Show loader
        $('#tbody_task_list').html('<tr><td colspan="9" class="text-center py-3"><div class="spinner-border spinner-border-sm" role="status"></div> Loading...</td></tr>');

        if (currentXhr) { currentXhr.abort(); }

        currentXhr = $.ajax({
            type: 'GET',
            url: task_list_url,
            data: filters,
            success: function (data) {
                var $resp = $(data);
                var $tbody = $resp.find('#tbody_task_list');
                if ($tbody.length) { $('#tbody_task_list').html($tbody.html()); }
                var $pagination = $resp.find('#task_pagination_container');
                if ($pagination.length) { $('#task_pagination_container').html($pagination.html()); }
                bindPaginationLinks();
            },
            error: function (xhr, status) {
                if (status !== 'abort') {
                    $('#tbody_task_list').html('<tr><td colspan="9" class="text-center text-danger py-3">Error loading results</td></tr>');
                }
            },
            complete: function () { currentXhr = null; }
        });
    }

    function bindPaginationLinks() {
        $('#task_pagination_container').off('click', 'a.page-link').on('click', 'a.page-link', function (e) {
            e.preventDefault();
            var href = $(this).attr('href');
            if (!href || href === '#') return;

            $('#tbody_task_list').html('<tr><td colspan="9" class="text-center py-3"><div class="spinner-border spinner-border-sm" role="status"></div> Loading...</td></tr>');
            history.replaceState(null, '', href);

            if (currentXhr) { currentXhr.abort(); }

            currentXhr = $.ajax({
                type: 'GET',
                url: href,
                success: function (data) {
                    var $resp = $(data);
                    var $tbody = $resp.find('#tbody_task_list');
                    if ($tbody.length) { $('#tbody_task_list').html($tbody.html()); }
                    var $pagination = $resp.find('#task_pagination_container');
                    if ($pagination.length) { $('#task_pagination_container').html($pagination.html()); }
                    bindPaginationLinks();
                },
                error: function (xhr, status) {
                    if (status !== 'abort') {
                        $('#tbody_task_list').html('<tr><td colspan="9" class="text-center text-danger py-3">Error loading results</td></tr>');
                    }
                },
                complete: function () { currentXhr = null; }
            });
        });

        // Intercept "Go to page" form
        $('#task_pagination_container').off('submit', 'form').on('submit', 'form', function (e) {
            e.preventDefault();
            var pageValue = $(this).find('input[name="page"]').val();
            var urlModel = $(this).data('url');
            if (urlModel) {
                var finalUrl = urlModel.replace('999999', pageValue);
                var filters = collectFilters();
                var params = new URLSearchParams(filters);
                var separator = finalUrl.indexOf('?') !== -1 ? '&' : '?';
                var href = finalUrl + (params.toString() ? separator + params.toString() : '');

                history.replaceState(null, '', href);
                $('#tbody_task_list').html('<tr><td colspan="9" class="text-center py-3"><div class="spinner-border spinner-border-sm" role="status"></div> Loading...</td></tr>');

                if (currentXhr) { currentXhr.abort(); }

                currentXhr = $.ajax({
                    type: 'GET',
                    url: href,
                    success: function (data) {
                        var $resp = $(data);
                        var $tbody = $resp.find('#tbody_task_list');
                        if ($tbody.length) { $('#tbody_task_list').html($tbody.html()); }
                        var $pagination = $resp.find('#task_pagination_container');
                        if ($pagination.length) { $('#task_pagination_container').html($pagination.html()); }
                        bindPaginationLinks();
                    },
                    error: function (xhr, status) {
                        if (status !== 'abort') {
                            $('#tbody_task_list').html('<tr><td colspan="9" class="text-center text-danger py-3">Error loading results</td></tr>');
                        }
                    },
                    complete: function () { currentXhr = null; }
                });
            }
        });
    }

    // Initial bind
    bindPaginationLinks();
});
