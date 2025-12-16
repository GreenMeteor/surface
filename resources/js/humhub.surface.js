/**
 * Surface Module - Main JavaScript
 * File: resources/js/humhub.surface.js
 */

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
            
            // Hide the admin indicator after 5 seconds
            setTimeout(function() {
                $('body').addClass('surface-indicator-hidden');
            }, 5000);
        }
        
        applyDisabledRules();
    };
    
    /**
     * Set disabled containers from server
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
     * Initialize admin mode - add flag buttons to ANY element on hover or double-click
     */
    var initAdminMode = function() {
        var currentHighlightedElement = null;
        
        // Method 1: Hover detection (with visual highlight)
        $(document).on('mouseenter', '*', function(e) {
            if (!isAdmin) return;
            
            var $target = $(e.target);
            
            // Skip if hovering over modal or flag button
            if ($target.closest('.modal').length || $target.hasClass('surface-flag-btn')) {
                return;
            }
            
            // Skip excluded elements
            if (shouldSkipElement($target)) {
                return;
            }
            
            // Remove previous highlight
            $('.surface-hover-highlight').removeClass('surface-hover-highlight');
            
            // Add highlight to current element
            $target.addClass('surface-hover-highlight');
            currentHighlightedElement = $target;
        });
        
        $(document).on('mouseleave', '*', function(e) {
            var $target = $(e.target);
            $target.removeClass('surface-hover-highlight');
        });
        
        // Method 2: Double-click to open modal directly
        $(document).on('dblclick', '*', function(e) {
            if (!isAdmin) return;
            
            var $target = $(e.target);
            
            // Skip if clicking on modal or flag button
            if ($target.closest('.modal').length || $target.hasClass('surface-flag-btn')) {
                return;
            }
            
            // Skip excluded elements
            if (shouldSkipElement($target)) {
                return;
            }
            
            // Prevent default double-click behavior (text selection, etc.)
            e.preventDefault();
            e.stopPropagation();
            
            // Generate selector and name
            var selector = generateUniqueSelector($target);
            var name = getElementName($target);
            
            // Add data attributes to element for future reference
            $target.attr('data-surface-container', selector);
            $target.attr('data-surface-name', name);
            
            // Open modal directly
            openRuleModal(selector, name);
        });
        
        // Method 3: Right-click context menu (optional - for future enhancement)
        // This will show "Surface: Configure Element" in context menu
        
        // Show tooltip on hover to indicate double-click is available
        var tooltipTimeout;
        $(document).on('mouseenter', '*', function(e) {
            if (!isAdmin) return;
            
            var $target = $(e.target);
            
            if (shouldSkipElement($target) || $target.closest('.modal').length) {
                return;
            }
            
            clearTimeout(tooltipTimeout);
            tooltipTimeout = setTimeout(function() {
                if ($target.is(':hover')) {
                    showSurfaceTooltip($target);
                }
            }, 500); // Show tooltip after 500ms hover
        });
        
        $(document).on('mouseleave', '*', function() {
            clearTimeout(tooltipTimeout);
            hideSurfaceTooltip();
        });
    };
    
    /**
     * Show tooltip indicating double-click is available
     */
    var showSurfaceTooltip = function($element) {
        // Remove any existing tooltip
        $('.surface-tooltip').remove();
        
        var $tooltip = $('<div>')
            .addClass('surface-tooltip')
            .html('<i class="fa fa-flag"></i> Double-click to configure with Surface')
            .appendTo('body');
        
        // Position tooltip near element
        var offset = $element.offset();
        var elementHeight = $element.outerHeight();
        
        $tooltip.css({
            top: offset.top + elementHeight + 5,
            left: offset.left,
        });
    };
    
    /**
     * Hide tooltip
     */
    var hideSurfaceTooltip = function() {
        $('.surface-tooltip').remove();
    };
    
    /**
     * Check if element should be skipped for flagging
     */
    var shouldSkipElement = function($element) {
        // Skip if it's the body, html, or document
        if ($element.is('body') || $element.is('html') || $element.is(document)) {
            return true;
        }
        
        // Skip if it's a script, style, or meta tag
        if ($element.is('script') || $element.is('style') || $element.is('meta') || $element.is('link')) {
            return true;
        }
        
        // Skip if inside a modal
        if ($element.closest('.modal').length > 0) {
            return true;
        }
        
        // Skip if it's too small (likely just text or icon) - but only for hover, not double-click
        var width = $element.outerWidth();
        var height = $element.outerHeight();
        if (width < 30 || height < 20) {
            return true;
        }
        
        // Skip if it's the flag button or tooltip
        if ($element.hasClass('surface-flag-btn') || $element.hasClass('surface-tooltip')) {
            return true;
        }
        
        // Skip Surface's own elements
        if ($element.closest('.surface-tooltip').length || $element.closest('.surface-flag-btn').length) {
            return true;
        }
        
        return false;
    };
    
    /**
     * Remove flag button (no longer needed with double-click)
     */
    var addFlagToElement = function($element) {
        // This function is deprecated but kept for backward compatibility
        // Double-click now directly opens the modal
        console.log('Surface: Element flagged (use double-click to configure)', $element);
    };
    
    /**
     * Generate a unique selector for an element
     */
    var generateUniqueSelector = function($element) {
        // If element already has data-surface-container, use it
        var existingSelector = $element.attr('data-surface-container');
        if (existingSelector) {
            return existingSelector;
        }
        
        // Try to use ID
        var id = $element.attr('id');
        if (id) {
            return 'id-' + id;
        }
        
        // Try to use class combination
        var classes = $element.attr('class');
        if (classes) {
            var classArray = classes.split(' ').filter(function(c) {
                return c && !c.startsWith('surface-');
            }).slice(0, 3);
            if (classArray.length > 0) {
                return 'class-' + classArray.join('-');
            }
        }
        
        // Use tag name + index among siblings
        var tagName = $element.prop('tagName').toLowerCase();
        var index = $element.index();
        var parentClasses = $element.parent().attr('class') || 'body';
        
        return tagName + '-' + index + '-in-' + parentClasses.split(' ')[0];
    };
    
    /**
     * Get a human-readable name for an element
     */
    var getElementName = function($element) {
        // Check for data-surface-name
        var existingName = $element.attr('data-surface-name');
        if (existingName) {
            return existingName;
        }
        
        // Try to get text content (limited)
        var text = $element.clone().children().remove().end().text().trim();
        if (text && text.length > 0 && text.length < 50) {
            return text.substring(0, 30) + (text.length > 30 ? '...' : '');
        }
        
        // Try to get heading text
        var heading = $element.find('h1, h2, h3, h4, h5, h6').first().text().trim();
        if (heading && heading.length > 0) {
            return heading.substring(0, 30) + (heading.length > 30 ? '...' : '');
        }
        
        // Try to get title or alt attribute
        var title = $element.attr('title') || $element.attr('alt');
        if (title) {
            return title.substring(0, 30);
        }
        
        // Use ID
        var id = $element.attr('id');
        if (id) {
            return 'Element: #' + id;
        }
        
        // Use class names
        var classes = $element.attr('class');
        if (classes) {
            var mainClass = classes.split(' ')[0];
            return 'Element: .' + mainClass;
        }
        
        // Fallback to tag name
        var tagName = $element.prop('tagName');
        return tagName + ' Element';
    };
    
    /**
     * Open the rule configuration modal
     */
    var openRuleModal = function(selector, name) {
        // First check if there are existing rules for this container
        $.ajax({
            url: '/surface/admin/get-rule-data',
            type: 'GET',
            data: { selector: selector },
            success: function(response) {
                // Load the modal with existing rule data if available
                modal.global.load('/surface/admin/rule-modal', {
                    data: {
                        selector: selector,
                        name: name,
                        existingRules: JSON.stringify(response.rules || [])
                    }
                });
            },
            error: function() {
                // Load modal anyway even if check fails
                modal.global.load('/surface/admin/rule-modal', {
                    data: {
                        selector: selector,
                        name: name
                    }
                });
            }
        });
    };
    
    /**
     * Apply disabled rules by hiding containers
     */
    var applyDisabledRules = function() {
        if (disabledContainers.length === 0) {
            return;
        }
        
        disabledContainers.forEach(function(selector) {
            $('[data-surface-container="' + selector + '"]').addClass('surface-disabled');
        });
    };
    
    /**
     * Handle form submission via AJAX
     */
    var initFormHandler = function() {
        // Toggle user select visibility based on checkbox
        $(document).on('change', '#surfaceruleform-disabled_for_all', function() {
            var $checkbox = $(this);
            var $userSelect = $('#user-select-container');
            
            if ($checkbox.is(':checked')) {
                $userSelect.addClass('d-none');
                $('#surfaceruleform-user_id').val('');
            } else {
                $userSelect.removeClass('d-none');
            }
        });

        // Handle modal form submission
        $(document).on('submit', '#surface-rule-form', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var formData = $form.serialize();
            
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    // Check if it's a success response
                    if (response.success) {
                        modal.global.close();
                        refreshRules();
                    } else {
                        // If it's not JSON success, it means validation errors
                        // The response should contain HTML
                        $('#globalModal .modal-content').html(response);
                        initFormHandler(); // Reinitialize handlers
                    }
                },
                error: function(xhr) {
                    // If response is HTML (validation errors), replace modal content
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

        // Initialize checkbox state on modal open
        var $checkbox = $('#surfaceruleform-disabled_for_all');
        if ($checkbox.length && $checkbox.is(':checked')) {
            $('#user-select-container').addClass('d-none');
        }

        // Load existing rules and display them
        displayExistingRules();
    };
    
    /**
     * Refresh rules after save
     */
    var refreshRules = function() {
        // Reload the page to apply new rules
        // In a production environment, you might want to use AJAX to update rules dynamically
        window.location.reload();
    };
    
    /**
     * Display existing rules in the modal
     */
    var displayExistingRules = function() {
        var $existingRulesContainer = $('#existing-rules-container');
        
        if (!$existingRulesContainer.length) {
            return;
        }
        
        var existingRulesData = $existingRulesContainer.data('rules');
        
        if (!existingRulesData || existingRulesData.length === 0) {
            $existingRulesContainer.html(
                '<p class="text-muted"><i class="fa fa-info-circle"></i> No existing rules for this container.</p>'
            );
            return;
        }
        
        var html = '<div class="existing-rules-list"><h5>Existing Rules:</h5><ul class="list-group">';
        
        existingRulesData.forEach(function(rule) {
            var scope = rule.disabled_for_all 
                ? '<span class="badge bg-danger">All Users</span>' 
                : '<span class="badge bg-info">User: ' + rule.username + '</span>';
            
            var deleteBtn = '<button type="button" class="btn btn-sm btn-danger delete-rule-btn" data-rule-id="' + rule.id + '">' +
                '<i class="fa fa-trash"></i></button>';
            
            html += '<li class="list-group-item d-flex justify-content-between align-items-center">' +
                scope + deleteBtn + '</li>';
        });
        
        html += '</ul></div>';
        
        $existingRulesContainer.html(html);
        
        // Handle delete button clicks
        $(document).on('click', '.delete-rule-btn', function() {
            var ruleId = $(this).data('rule-id');
            deleteRule(ruleId);
        });
    };
    
    /**
     * Delete a rule via AJAX
     */
    var deleteRule = function(ruleId) {
        if (!confirm('Are you sure you want to delete this rule?')) {
            return;
        }
        
        $.ajax({
            url: '/surface/admin/delete',
            type: 'POST',
            data: { id: ruleId },
            success: function() {
                // Reload the modal to show updated rules
                var selector = $('#surfaceruleform-container_selector').val();
                var name = $('#surfaceruleform-container_name').val();
                openRuleModal(selector, name);
            },
            error: function() {
                alert('Error deleting rule. Please try again.');
            }
        });
    };
    
    // Export public methods
    module.export({
        init: init,
        setDisabledContainers: setDisabledContainers,
        setAdminStatus: setAdminStatus,
        openRuleModal: openRuleModal,
        refreshRules: refreshRules,
        initFormHandler: initFormHandler,
        displayExistingRules: displayExistingRules,
        deleteRule: deleteRule,
        generateUniqueSelector: generateUniqueSelector,
        getElementName: getElementName,
        showSurfaceTooltip: showSurfaceTooltip,
        hideSurfaceTooltip: hideSurfaceTooltip
    });
});