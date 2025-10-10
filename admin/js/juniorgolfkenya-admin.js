/**
 * Junior Golf Kenya Admin JavaScript
 *
 * @link       https://github.com/kanji8210/juniorgolfkenya
 * @since      1.0.0
 *
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/admin/js
 */

(function($) {
    'use strict';

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        
        // Initialize datepickers if jQuery UI is available
        if ($.fn.datepicker) {
            $('.jgk-datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                yearRange: '-100:+0'
            });
        }

        // Initialize tooltips if available
        if ($.fn.tooltip) {
            $('[data-toggle="tooltip"]').tooltip();
        }

        // Confirm delete actions
        $('.jgk-delete-action').on('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
                return false;
            }
        });

        // Handle tab navigation
        $('.jgk-nav-tab').on('click', function(e) {
            e.preventDefault();
            var target = $(this).data('tab');
            
            // Remove active class from all tabs
            $('.jgk-nav-tab').removeClass('nav-tab-active');
            $('.jgk-tab-content').removeClass('active');
            
            // Add active class to clicked tab
            $(this).addClass('nav-tab-active');
            $('#' + target).addClass('active');
            
            // Update URL hash
            window.location.hash = target;
        });

        // Activate tab from URL hash on page load
        if (window.location.hash) {
            var hash = window.location.hash.substring(1);
            $('[data-tab="' + hash + '"]').trigger('click');
        }

        // Toggle advanced filters
        $('.jgk-toggle-filters').on('click', function(e) {
            e.preventDefault();
            $('.jgk-advanced-filters').slideToggle();
            $(this).find('.dashicons').toggleClass('dashicons-arrow-down dashicons-arrow-up');
        });

        // Auto-save functionality
        var autoSaveTimer;
        $('.jgk-autosave-field').on('input', function() {
            clearTimeout(autoSaveTimer);
            var $field = $(this);
            
            autoSaveTimer = setTimeout(function() {
                // Show saving indicator
                $field.after('<span class="jgk-saving">Saving...</span>');
                
                // Simulate save (replace with actual AJAX call)
                setTimeout(function() {
                    $('.jgk-saving').remove();
                    $field.after('<span class="jgk-saved">Saved!</span>');
                    setTimeout(function() {
                        $('.jgk-saved').fadeOut(function() {
                            $(this).remove();
                        });
                    }, 2000);
                }, 500);
            }, 1000);
        });

        // Bulk actions
        $('#doaction, #doaction2').on('click', function(e) {
            var action = $(this).prev('select').val();
            if (action === '-1') {
                alert('Please select an action.');
                e.preventDefault();
                return false;
            }
            
            var checkedItems = $('.jgk-bulk-checkbox:checked').length;
            if (checkedItems === 0) {
                alert('Please select at least one item.');
                e.preventDefault();
                return false;
            }
            
            if (action === 'delete') {
                if (!confirm('Are you sure you want to delete ' + checkedItems + ' item(s)?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });

        // Select all checkboxes
        $('.jgk-select-all').on('change', function() {
            $('.jgk-bulk-checkbox').prop('checked', $(this).prop('checked'));
        });

        // Update select all checkbox when individual checkboxes change
        $('.jgk-bulk-checkbox').on('change', function() {
            var total = $('.jgk-bulk-checkbox').length;
            var checked = $('.jgk-bulk-checkbox:checked').length;
            $('.jgk-select-all').prop('checked', total === checked);
        });

        // Modal functionality
        $('.jgk-open-modal').on('click', function(e) {
            e.preventDefault();
            var modalId = $(this).data('modal');
            $('#' + modalId).fadeIn();
            $('body').addClass('jgk-modal-open');
        });

        $('.jgk-close-modal, .jgk-modal-overlay').on('click', function() {
            $(this).closest('.jgk-modal').fadeOut();
            $('body').removeClass('jgk-modal-open');
        });

        // Prevent modal content click from closing modal
        $('.jgk-modal-content').on('click', function(e) {
            e.stopPropagation();
        });

        // Close modal on escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.jgk-modal').fadeOut();
                $('body').removeClass('jgk-modal-open');
            }
        });

        // Form validation
        $('.jgk-validate-form').on('submit', function(e) {
            var isValid = true;
            var firstInvalidField = null;

            $(this).find('[required]').each(function() {
                var $field = $(this);
                var value = $field.val();

                // Remove previous error
                $field.removeClass('jgk-field-error');
                $field.next('.jgk-field-error-message').remove();

                // Check if empty
                if (!value || value.trim() === '') {
                    isValid = false;
                    $field.addClass('jgk-field-error');
                    $field.after('<span class="jgk-field-error-message">This field is required.</span>');
                    
                    if (!firstInvalidField) {
                        firstInvalidField = $field;
                    }
                }

                // Email validation
                if ($field.attr('type') === 'email' && value) {
                    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        isValid = false;
                        $field.addClass('jgk-field-error');
                        $field.after('<span class="jgk-field-error-message">Please enter a valid email address.</span>');
                        
                        if (!firstInvalidField) {
                            firstInvalidField = $field;
                        }
                    }
                }
            });

            if (!isValid) {
                e.preventDefault();
                if (firstInvalidField) {
                    firstInvalidField.focus();
                }
                return false;
            }
        });

        // Character counter
        $('.jgk-char-counter').each(function() {
            var $field = $(this);
            var maxLength = $field.attr('maxlength');
            
            if (maxLength) {
                var $counter = $('<span class="jgk-char-count">0 / ' + maxLength + '</span>');
                $field.after($counter);
                
                $field.on('input', function() {
                    var length = $(this).val().length;
                    $counter.text(length + ' / ' + maxLength);
                    
                    if (length >= maxLength * 0.9) {
                        $counter.addClass('jgk-char-count-warning');
                    } else {
                        $counter.removeClass('jgk-char-count-warning');
                    }
                });
                
                // Trigger initial count
                $field.trigger('input');
            }
        });

        // Sortable tables
        if ($.fn.sortable) {
            $('.jgk-sortable-table tbody').sortable({
                handle: '.jgk-sort-handle',
                axis: 'y',
                cursor: 'move',
                update: function(event, ui) {
                    // Update order via AJAX
                    var order = $(this).sortable('toArray', { attribute: 'data-id' });
                    console.log('New order:', order);
                    // Add AJAX call here to save new order
                }
            });
        }

        // Image preview on upload
        $('.jgk-image-upload').on('change', function(e) {
            var file = e.target.files[0];
            var $preview = $(this).next('.jgk-image-preview');
            
            if (file && file.type.startsWith('image/')) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    if ($preview.length === 0) {
                        $preview = $('<img class="jgk-image-preview" />');
                        $(this).after($preview);
                    }
                    $preview.attr('src', e.target.result).show();
                }.bind(this);
                
                reader.readAsDataURL(file);
            }
        });

        // Copy to clipboard
        $('.jgk-copy-to-clipboard').on('click', function(e) {
            e.preventDefault();
            var text = $(this).data('text') || $(this).text();
            
            // Create temporary textarea
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
            
            // Show feedback
            var $btn = $(this);
            var originalText = $btn.html();
            $btn.html('<span class="dashicons dashicons-yes"></span> Copied!');
            
            setTimeout(function() {
                $btn.html(originalText);
            }, 2000);
        });

        // Expandable rows
        $('.jgk-expand-row').on('click', function(e) {
            e.preventDefault();
            $(this).closest('tr').next('.jgk-expanded-row').toggle();
            $(this).find('.dashicons').toggleClass('dashicons-arrow-right dashicons-arrow-down');
        });

        // Search with live results
        var searchTimer;
        $('.jgk-live-search').on('input', function() {
            clearTimeout(searchTimer);
            var $input = $(this);
            var query = $input.val();
            
            if (query.length >= 3) {
                searchTimer = setTimeout(function() {
                    // Add AJAX search here
                    console.log('Searching for:', query);
                }, 300);
            }
        });

        // Number input spinner
        $('.jgk-spinner-btn').on('click', function(e) {
            e.preventDefault();
            var $input = $(this).siblings('input[type="number"]');
            var currentVal = parseFloat($input.val()) || 0;
            var step = parseFloat($input.attr('step')) || 1;
            var min = parseFloat($input.attr('min'));
            var max = parseFloat($input.attr('max'));
            
            if ($(this).hasClass('jgk-spinner-up')) {
                var newVal = currentVal + step;
                if (isNaN(max) || newVal <= max) {
                    $input.val(newVal);
                }
            } else {
                var newVal = currentVal - step;
                if (isNaN(min) || newVal >= min) {
                    $input.val(newVal);
                }
            }
            
            $input.trigger('change');
        });

        // Print functionality
        $('.jgk-print-btn').on('click', function(e) {
            e.preventDefault();
            window.print();
        });

        // Export functionality
        $('.jgk-export-btn').on('click', function(e) {
            e.preventDefault();
            var format = $(this).data('format') || 'csv';
            console.log('Exporting as:', format);
            // Add export logic here
        });

        // Notification dismiss
        $('.jgk-notice-dismiss').on('click', function() {
            $(this).closest('.jgk-notice').fadeOut(function() {
                $(this).remove();
            });
        });

        // Auto-dismiss notifications
        $('.jgk-notice[data-autodismiss]').each(function() {
            var delay = parseInt($(this).data('autodismiss')) || 5000;
            var $notice = $(this);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, delay);
        });

        console.log('Junior Golf Kenya Admin JS loaded successfully');
    });

})(jQuery);
