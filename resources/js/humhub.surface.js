humhub.module('surface', function(module, require, $) {

    var client = require('client');
    var modal = require('ui.modal');

    var disabledContainers = [];
    var isAdmin = false;

    /**
     * Initialize the Surface module
     */
    var init = function() {
        console.log('Surface module initialized');

        if (isAdmin) {
            initAdminMode();

            setTimeout(function() {
                $('body').addClass('surface-indicator-hidden');
            }, 5000);
        }

        applyDisabledRules();
    };

    /**
     * Set disabled containers from server.
     * Accepts an array of selector strings — these can be:
     *   - data-surface-container values  e.g. "class-foo-bar"
     *   - raw CSS class selectors        e.g. ".my-class"
     *   - raw CSS id selectors           e.g. "#my-id"
     *   - any valid jQuery selector      e.g. "div.btn-group.dark-mode"
     */
    var setDisabledContainers = function(selectors) {
        disabledContainers = selectors || [];
    };

    /**
     * Set admin status
     */
    var setAdminStatus = function(status) {
        isAdmin = status === true || status === 'true';
    };

    /**
     * Initialize admin mode.
     *
     * Key behaviour: when the user double-clicks a small/inline element (icon,
     * link, span) we walk UP the DOM to the nearest meaningful container div
     * instead of refusing to act. Double-clicking the <i class="fa-moon-o">
     * inside <div class="btn-group dark-mode"> correctly targets the div.
     */
    var initAdminMode = function() {

        $(document).on('mouseenter', '*', function(e) {
            if (!isAdmin) return;
            var $target = $(e.target);
            if ($target.closest('.modal').length || $target.hasClass('surface-flag-btn')) return;
            if (isHardExcluded($target)) return;

            $('.surface-hover-highlight').removeClass('surface-hover-highlight');
            getBestTarget($target).addClass('surface-hover-highlight');
        });

        $(document).on('mouseleave', '*', function(e) {
            $(e.target).removeClass('surface-hover-highlight');
        });

        $(document).on('dblclick', '*', function(e) {
            if (!isAdmin) return;

            var $clicked = $(e.target);

            if ($clicked.closest('.modal').length || $clicked.hasClass('surface-flag-btn')) return;
            if (isHardExcluded($clicked)) return;

            e.preventDefault();
            e.stopPropagation();

            var $target  = getBestTarget($clicked);
            var selector = generateUniqueSelector($target);
            var name     = getElementName($target);

            $target.attr('data-surface-container', selector);
            $target.attr('data-surface-name', name);

            console.log('Surface: targeting element', $target[0], '→ selector:', selector);

            openRuleModal(selector, name);
        });

        var tooltipTimeout;
        $(document).on('mouseenter', '*', function(e) {
            if (!isAdmin) return;
            var $target = $(e.target);
            if (isHardExcluded($target) || $target.closest('.modal').length) return;

            clearTimeout(tooltipTimeout);
            tooltipTimeout = setTimeout(function() {
                var $best = getBestTarget($target);
                if ($best.is(':hover') || $target.is(':hover')) {
                    showSurfaceTooltip($best);
                }
            }, 500);
        });

        $(document).on('mouseleave', '*', function() {
            clearTimeout(tooltipTimeout);
            hideSurfaceTooltip();
        });
    };

    /**
     * Hard exclusions — elements that should NEVER be targeted regardless of size.
     * Replaces the old shouldSkipElement size check which incorrectly blocked
     * small but valid containers like icon buttons.
     */
    var isHardExcluded = function($el) {
        if ($el.is('body, html, script, style, meta, link')) return true;
        if ($el.hasClass('surface-flag-btn') || $el.hasClass('surface-tooltip')) return true;
        if ($el.closest('.surface-tooltip, .surface-flag-btn').length) return true;
        return false;
    };

    /**
     * Given any clicked element, walk up the DOM to find the nearest ancestor
     * (or self) that is a meaningful block-level container.
     *
     * Priority:
     *  1. Element itself is already a container tag (div, section, nav…) → use it
     *  2. Element is inline/icon (a, i, span, button, svg…) → climb to nearest container
     *  3. Nothing found above → fall back to original element
     */
    var CONTAINER_TAGS = 'div, section, nav, header, footer, aside, article, li, ul, ol';
    var INLINE_TAGS    = ['a', 'i', 'span', 'button', 'svg', 'img', 'small', 'strong',
                          'em', 'b', 'label', 'input', 'textarea', 'select', 'icon'];

    var getBestTarget = function($el) {
        var tag = ($el.prop('tagName') || '').toLowerCase();

        if ($el.is(CONTAINER_TAGS)) {
            return $el;
        }

        if (INLINE_TAGS.indexOf(tag) !== -1) {
            var $ancestor = $el.closest(CONTAINER_TAGS);
            if ($ancestor.length && !$ancestor.is('body')) {
                return $ancestor;
            }
        }

        return $el;
    };

    var showSurfaceTooltip = function($element) {
        $('.surface-tooltip').remove();

        var $tooltip = $('<div>')
            .addClass('surface-tooltip')
            .html('<i class="fa fa-flag"></i> Double-click to configure with Surface')
            .appendTo('body');

        var offset = $element.offset();
        $tooltip.css({
            top:  offset.top + $element.outerHeight() + 5,
            left: offset.left,
        });
    };

    var hideSurfaceTooltip = function() {
        $('.surface-tooltip').remove();
    };

    /**
     * Generate a stable, UNIQUE selector for a container element.
     *
     * The core problem with class-only selectors (e.g. "li.nav-item") is that
     * many sibling elements share the same classes, so disabling one disables all.
     *
     * Strategy:
     *  1. Existing data-surface-container  (already resolved in this session)
     *  2. ID on the element itself → "id-{value}"  (always unique)
     *  3. Scoped path selector — walk up to the nearest uniquely-identifiable
     *     ancestor (has ID or is body) and build a child path with :nth-child
     *     to pinpoint this exact element.
     *     e.g. "#top-menu>ul.navbar-nav>li.nav-item:nth-child(3)"
     *
     * The scoped path is stored as-is. resolveSelector() passes it through
     * unchanged because it contains '>' or ':' characters.
     */
    var generateUniqueSelector = function($element) {
        var existing = $element.attr('data-surface-container');
        if (existing) return existing;

        var id = $element.attr('id');
        if (id) return 'id-' + id;

        return buildScopedPath($element);
    };

    /**
     * Build a scoped CSS path from $element up to the nearest ancestor that
     * has an ID (giving us a stable root anchor) or until we hit <body>.
     *
     * Each step uses:  tagName.firstClass.secondClass:nth-child(n)
     * The nth-child index disambiguates siblings that share the same classes.
     *
     * Examples:
     *   li.nav-item (3rd child of ul.navbar-nav inside #top-menu)
     *   → "#top-menu > ul.navbar-nav > li.nav-item:nth-child(3)"
     *
     *   div.btn-group.dark-mode (unique enough on its own inside a named parent)
     *   → "#page-wrapper > div.btn-group.dark-mode"
     */
    var buildScopedPath = function($element) {
        var parts  = [];
        var $node  = $element;
        var MAX_DEPTH = 6;

        for (var depth = 0; depth < MAX_DEPTH; depth++) {
            var nodePart = getNodeSegment($node);
            parts.unshift(nodePart);

            var $parent = $node.parent();

            if ($parent.length === 0 || $parent.is('body') || $parent.is('html')) {
                break;
            }

            var parentId = $parent.attr('id');
            if (parentId) {
                parts.unshift('#' + parentId);
                break;
            }

            $node = $parent;
        }

        return parts.join(' > ');
    };

    /**
     * Build the CSS segment for a single element node:
     *   tagName[.class1.class2][:nth-child(n)]
     *
     * nth-child is only appended when there are siblings that share the same
     * tag+class combination — i.e. when the element would not be unique without it.
     */
    var getNodeSegment = function($el) {
        var tag = $el.prop('tagName').toLowerCase();

        var classes = ($el.attr('class') || '').split(/\s+/).filter(function(c) {
            return c && !c.startsWith('surface-');
        });

        var segment = tag + (classes.length ? '.' + classes.slice(0, 3).join('.') : '');

        var $parent = $el.parent();
        if ($parent.length) {
            var $siblings = $parent.children(segment);
            if ($siblings.length > 1) {
                var nthIndex = $el.index() + 1;
                segment += ':nth-child(' + nthIndex + ')';
            }
        }

        return segment;
    };

    /**
     * Get a human-readable name for an element
     */
    var getElementName = function($element) {
        var existing = $element.attr('data-surface-name');
        if (existing) return existing;

        var text = $element.clone().children().remove().end().text().trim();
        if (text && text.length < 50) return text.substring(0, 30) + (text.length > 30 ? '...' : '');

        var heading = $element.find('h1,h2,h3,h4,h5,h6').first().text().trim();
        if (heading) return heading.substring(0, 30) + (heading.length > 30 ? '...' : '');

        var title = $element.attr('title') || $element.attr('alt');
        if (title) return title.substring(0, 30);

        var id = $element.attr('id');
        if (id) return 'Element: #' + id;

        var classes = $element.attr('class');
        if (classes) return 'Element: .' + classes.split(/\s+/)[0];

        return $element.prop('tagName') + ' Element';
    };

    /**
     * Resolve a stored selector string to a usable jQuery selector.
     *
     * "id-{value}"                         → "#value"
     * scoped path (contains > or :nth)     → pass through as-is
     * contains '.' or '#'                  → pass through as-is
     * anything else                        → [data-surface-container="value"] (legacy)
     */
    var resolveSelector = function(storedSelector) {
        if (storedSelector.startsWith('id-')) {
            return '#' + storedSelector.substring(3);
        }

        // all contain one of these characters — pass straight through to jQuery.
        if (storedSelector.indexOf('.') !== -1 ||
            storedSelector.indexOf('#') !== -1 ||
            storedSelector.indexOf('>') !== -1 ||
            storedSelector.indexOf(':') !== -1) {
            return storedSelector;
        }

        return '[data-surface-container="' + storedSelector + '"]';
    };

    /**
     * Apply disabled rules — adds .surface-disabled to every matched element.
     *
     * Also installs a MutationObserver so rules apply to elements that are
     * injected into the DOM after init (e.g. HumHub stream items, widgets).
     */
    var applyDisabledRules = function() {
        if (disabledContainers.length === 0) return;

        disabledContainers.forEach(function(storedSelector) {
            applySingleRule(storedSelector);
        });

        if (typeof MutationObserver !== 'undefined') {
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length) {
                        disabledContainers.forEach(function(storedSelector) {
                            applySingleRule(storedSelector);
                        });
                    }
                });
            });

            observer.observe(document.body, { childList: true, subtree: true });
        }
    };

    /**
     * Apply a single stored selector rule to the DOM.
     */
    var applySingleRule = function(storedSelector) {
        var jqSelector = resolveSelector(storedSelector);

        try {
            var $matched = $(jqSelector).not('.surface-disabled');
            if ($matched.length) {
                $matched.addClass('surface-disabled');
                console.log('Surface: disabled ' + $matched.length + ' element(s) → "' + jqSelector + '"');
            }
        } catch (e) {
            console.error('Surface: invalid selector "' + jqSelector + '"', e);
        }
    };

    var openRuleModal = function(selector, name) {
        $.ajax({
            url:  '/surface/admin/get-rule-data',
            type: 'GET',
            data: { selector: selector },
            success: function(response) {
                modal.global.load('/surface/admin/rule-modal', {
                    data: {
                        selector:      selector,
                        name:          name,
                        existingRules: JSON.stringify(response.rules || [])
                    }
                });
            },
            error: function() {
                modal.global.load('/surface/admin/rule-modal', {
                    data: { selector: selector, name: name }
                });
            }
        });
    };

    var initFormHandler = function() {
        $(document).on('change', '#surfaceruleform-disabled_for_all', function() {
            var $checkbox   = $(this);
            var $userSelect = $('#user-select-container');

            if ($checkbox.is(':checked')) {
                $userSelect.addClass('d-none');
                $('#surfaceruleform-user_id').val('');
            } else {
                $userSelect.removeClass('d-none');
            }
        });

        $(document).on('submit', '#surface-rule-form', function(e) {
            e.preventDefault();

            var $form    = $(this);
            var formData = $form.serialize();

            $.ajax({
                url:      $form.attr('action'),
                type:     'POST',
                data:     formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        modal.global.close();
                        refreshRules();
                    } else {
                        $('#globalModal .modal-content').html(response);
                        initFormHandler();
                    }
                },
                error: function(xhr) {
                    if (xhr.responseText && xhr.responseText.indexOf('modal-dialog') !== -1) {
                        $('#globalModal').html(xhr.responseText);
                        initFormHandler();
                    } else {
                        alert('Error saving rule. Please try again.');
                    }
                }
            });

            return false;
        });

        var $checkbox = $('#surfaceruleform-disabled_for_all');
        if ($checkbox.length && $checkbox.is(':checked')) {
            $('#user-select-container').addClass('d-none');
        }

        displayExistingRules();
    };

    var refreshRules = function() {
        window.location.reload();
    };

    var displayExistingRules = function() {
        var $container = $('#existing-rules-container');
        if (!$container.length) return;

        var rules = $container.data('rules');

        if (!rules || rules.length === 0) {
            $container.html(
                '<p class="text-muted"><i class="fa fa-info-circle"></i> No existing rules for this container.</p>'
            );
            return;
        }

        var html = '<div class="existing-rules-list"><h5>Existing Rules:</h5><ul class="list-group">';

        rules.forEach(function(rule) {
            var scope = rule.disabled_for_all
                ? '<span class="badge bg-danger">All Users</span>'
                : '<span class="badge bg-info">User: ' + (rule.username || rule.user_id) + '</span>';

            var del = '<button type="button" class="btn btn-sm btn-danger delete-rule-btn" data-rule-id="' + rule.id + '">' +
                      '<i class="fa fa-trash"></i></button>';

            html += '<li class="list-group-item d-flex justify-content-between align-items-center">' +
                    scope + del + '</li>';
        });

        html += '</ul></div>';
        $container.html(html);

        $(document).on('click', '.delete-rule-btn', function() {
            deleteRule($(this).data('rule-id'));
        });
    };

    var deleteRule = function(ruleId) {
        if (!confirm('Are you sure you want to delete this rule?')) return;

        $.ajax({
            url:  '/surface/admin/delete',
            type: 'POST',
            data: { id: ruleId },
            success: function() {
                var selector = $('#surfaceruleform-container_selector').val();
                var name     = $('#surfaceruleform-container_name').val();
                openRuleModal(selector, name);
            },
            error: function() {
                alert('Error deleting rule. Please try again.');
            }
        });
    };

    var shouldSkipElement = function($el) { return isHardExcluded($el); };

    var addFlagToElement = function($el) {
        console.log('Surface: addFlagToElement is deprecated — use double-click', $el);
    };

    module.export({
        init:                   init,
        setDisabledContainers:  setDisabledContainers,
        setAdminStatus:         setAdminStatus,
        openRuleModal:          openRuleModal,
        refreshRules:           refreshRules,
        initFormHandler:        initFormHandler,
        displayExistingRules:   displayExistingRules,
        deleteRule:             deleteRule,
        generateUniqueSelector: generateUniqueSelector,
        getElementName:         getElementName,
        showSurfaceTooltip:     showSurfaceTooltip,
        hideSurfaceTooltip:     hideSurfaceTooltip,
        resolveSelector:        resolveSelector,
        applyDisabledRules:     applyDisabledRules,
        applySingleRule:        applySingleRule,
        getBestTarget:          getBestTarget,
        isHardExcluded:         isHardExcluded,
        buildScopedPath:        buildScopedPath,
        getNodeSegment:         getNodeSegment
    });
});