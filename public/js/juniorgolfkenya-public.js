/**
 * Junior Golf Kenya Public JavaScript
 *
 * @link       https://github.com/kanji8210/juniorgolfkenya
 * @since      1.0.0
 *
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/public/js
 */

(function($) {
    'use strict';

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        
        // Form validation for member portal
        $('.jgk-form').on('submit', function(e) {
            var isValid = true;
            var firstInvalidField = null;

            // Clear previous errors
            $(this).find('.jgk-field-error').removeClass('jgk-field-error');
            $(this).find('.jgk-error-message').remove();

            // Validate required fields
            $(this).find('[required]').each(function() {
                var $field = $(this);
                var value = $field.val();

                if (!value || value.trim() === '') {
                    isValid = false;
                    $field.addClass('jgk-field-error');
                    $field.after('<span class="jgk-error-message">This field is required.</span>');
                    
                    if (!firstInvalidField) {
                        firstInvalidField = $field;
                    }
                }
            });

            // Email validation
            $(this).find('input[type="email"]').each(function() {
                var $field = $(this);
                var value = $field.val();
                
                if (value && value.trim() !== '') {
                    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        isValid = false;
                        $field.addClass('jgk-field-error');
                        $field.after('<span class="jgk-error-message">Please enter a valid email address.</span>');
                        
                        if (!firstInvalidField) {
                            firstInvalidField = $field;
                        }
                    }
                }
            });

            // Phone validation
            $(this).find('input[type="tel"]').each(function() {
                var $field = $(this);
                var value = $field.val();
                
                if (value && value.trim() !== '') {
                    // Basic phone validation (accepts various formats)
                    var phoneRegex = /^[\d\s\-\+\(\)]+$/;
                    if (!phoneRegex.test(value)) {
                        isValid = false;
                        $field.addClass('jgk-field-error');
                        $field.after('<span class="jgk-error-message">Please enter a valid phone number.</span>');
                        
                        if (!firstInvalidField) {
                            firstInvalidField = $field;
                        }
                    }
                }
            });

            if (!isValid) {
                e.preventDefault();
                
                // Scroll to first error
                if (firstInvalidField) {
                    $('html, body').animate({
                        scrollTop: firstInvalidField.offset().top - 100
                    }, 300);
                    firstInvalidField.focus();
                }
                
                // Show error message
                if ($('.jgk-error-summary').length === 0) {
                    $(this).prepend('<div class="jgk-message jgk-message-error jgk-error-summary"><p>Please correct the errors below.</p></div>');
                }
                
                return false;
            }
        });

        // Auto-dismiss messages
        $('.jgk-message').each(function() {
            var $message = $(this);
            setTimeout(function() {
                $message.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000); // Dismiss after 5 seconds
        });

        // Smooth scroll for anchor links
        $('a[href^="#"]').on('click', function(e) {
            var target = $(this).attr('href');
            
            if (target !== '#' && $(target).length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $(target).offset().top - 100
                }, 500);
            }
        });

        // Toggle password visibility
        $('.jgk-toggle-password').on('click', function() {
            var $input = $(this).siblings('input');
            var type = $input.attr('type');
            
            if (type === 'password') {
                $input.attr('type', 'text');
                $(this).html('<span class="dashicons dashicons-hidden"></span>');
            } else {
                $input.attr('type', 'password');
                $(this).html('<span class="dashicons dashicons-visibility"></span>');
            }
        });

        // Confirmation dialogs
        $('.jgk-confirm-action').on('click', function(e) {
            var message = $(this).data('confirm') || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });

        // Character counter for textareas
        $('textarea[maxlength]').each(function() {
            var $textarea = $(this);
            var maxLength = $textarea.attr('maxlength');
            var $counter = $('<div class="jgk-char-counter"><span class="current">0</span> / <span class="max">' + maxLength + '</span></div>');
            
            $textarea.after($counter);
            
            $textarea.on('input', function() {
                var length = $(this).val().length;
                $counter.find('.current').text(length);
                
                if (length >= maxLength) {
                    $counter.addClass('jgk-char-counter-max');
                } else {
                    $counter.removeClass('jgk-char-counter-max');
                }
            });
            
            // Trigger initial count
            $textarea.trigger('input');
        });

        // Loading state for buttons
        $('.jgk-form').on('submit', function() {
            var $submitBtn = $(this).find('button[type="submit"], input[type="submit"]');
            $submitBtn.prop('disabled', true);
            $submitBtn.addClass('jgk-loading');
            
            var originalText = $submitBtn.val() || $submitBtn.text();
            $submitBtn.data('original-text', originalText);
            $submitBtn.val('Loading...').text('Loading...');
        });

        // File input custom styling
        $('.jgk-file-input').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            var $label = $(this).siblings('.jgk-file-label');
            
            if (fileName) {
                $label.text(fileName);
                $label.addClass('jgk-file-selected');
            } else {
                $label.text('Choose file');
                $label.removeClass('jgk-file-selected');
            }
        });

        // Accordion functionality
        $('.jgk-accordion-header').on('click', function() {
            var $content = $(this).next('.jgk-accordion-content');
            var $icon = $(this).find('.jgk-accordion-icon');
            
            // Close other accordions if needed
            if (!$(this).parent().hasClass('jgk-accordion-multiple')) {
                $('.jgk-accordion-content').not($content).slideUp();
                $('.jgk-accordion-icon').not($icon).removeClass('active');
            }
            
            // Toggle current accordion
            $content.slideToggle();
            $icon.toggleClass('active');
        });

        // Tabs functionality
        $('.jgk-tab-link').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            
            // Remove active class from all tabs
            $('.jgk-tab-link').removeClass('active');
            $('.jgk-tab-panel').removeClass('active');
            
            // Add active class to clicked tab
            $(this).addClass('active');
            $(target).addClass('active');
        });

        // Lazy load images
        if ('IntersectionObserver' in window) {
            var imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('jgk-lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img.jgk-lazy').forEach(function(img) {
                imageObserver.observe(img);
            });
        }

        // Print functionality
        $('.jgk-print-button').on('click', function(e) {
            e.preventDefault();
            window.print();
        });

        // Share functionality
        $('.jgk-share-button').on('click', function(e) {
            e.preventDefault();
            
            if (navigator.share) {
                navigator.share({
                    title: document.title,
                    url: window.location.href
                }).catch(function(error) {
                    console.log('Error sharing:', error);
                });
            } else {
                // Fallback: copy link to clipboard
                var dummy = document.createElement('input');
                document.body.appendChild(dummy);
                dummy.value = window.location.href;
                dummy.select();
                document.execCommand('copy');
                document.body.removeChild(dummy);
                
                alert('Link copied to clipboard!');
            }
        });

        // Tooltip functionality
        $('.jgk-tooltip').hover(
            function() {
                var tooltipText = $(this).data('tooltip');
                var $tooltip = $('<div class="jgk-tooltip-content">' + tooltipText + '</div>');
                $('body').append($tooltip);
                
                var offset = $(this).offset();
                $tooltip.css({
                    top: offset.top - $tooltip.outerHeight() - 10,
                    left: offset.left + ($(this).outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                });
                
                $tooltip.fadeIn(200);
            },
            function() {
                $('.jgk-tooltip-content').fadeOut(200, function() {
                    $(this).remove();
                });
            }
        );

        // Back to top button
        var $backToTop = $('.jgk-back-to-top');
        
        if ($backToTop.length) {
            $(window).on('scroll', function() {
                if ($(this).scrollTop() > 300) {
                    $backToTop.fadeIn();
                } else {
                    $backToTop.fadeOut();
                }
            });
            
            $backToTop.on('click', function(e) {
                e.preventDefault();
                $('html, body').animate({ scrollTop: 0 }, 500);
            });
        }

        console.log('Junior Golf Kenya Public JS loaded successfully');
    });

})(jQuery);
