/**
 * Frontend JavaScript for Filterly plugin.
 *
 * @since      1.0.0
 * @package    Filterly
 */

(function($) {
    'use strict';

    // Filterly main object
    var Filterly = {
        /**
         * Initialize the plugin
         */
        init: function() {
            // Initialize all filter containers
            $('.filterly-container').each(function() {
                Filterly.initFilterContainer($(this));
            });

            // Handle browser back/forward navigation
            $(window).on('popstate', function(event) {
                if (event.originalEvent.state && event.originalEvent.state.filterly) {
                    var container = $('#' + event.originalEvent.state.containerId);
                    if (container.length) {
                        // Load content from browser history state
                        container.html(event.originalEvent.state.content);
                        
                        // Update form values based on URL parameters
                        Filterly.updateFormFromUrl();
                    }
                }
            });
        },

        /**
         * Initialize a single filter container
         * 
         * @param {jQuery} $container The filter container element
         */
        initFilterContainer: function($container) {
            var useAjax = $container.data('use-ajax') !== false;
            var $form = $container.find('.filterly-form');
            var $resultsContainer = $container.find('.filterly-results-container');
            var containerId = $resultsContainer.attr('id');
            
            // Initialize range sliders
            $container.find('.filterly-range-slider').each(function() {
                Filterly.initRangeSlider($(this));
            });
            
            // Handle filter form submission
            $form.on('submit', function(e) {
                if (useAjax) {
                    e.preventDefault();
                    Filterly.submitFilters($form, $container);
                }
                // If not using AJAX, let the form submit normally
            });
            
            // Update filters on select change
            $form.find('select').on('change', function() {
                if (useAjax) {
                    $form.submit();
                }
            });
            
            // Handle instant filtering checkboxes
            if (useAjax) {
                $form.find('input[type="checkbox"], input[type="radio"]').on('change', function() {
                    var $filter = $(this).closest('.filterly-filter');
                    var instantFilter = $filter.data('instant-filter') !== false;
                    
                    if (instantFilter) {
                        $form.submit();
                    }
                });
            }
            
            // Handle pagination links
            $resultsContainer.on('click', '.pagination a, .woocommerce-pagination a', function(e) {
                if (useAjax) {
                    e.preventDefault();
                    var page = Filterly.getPageNumberFromUrl($(this).attr('href'));
                    Filterly.submitFilters($form, $container, page);
                }
                // If not using AJAX, let the link work normally
            });
            
            // Handle reset button
            $container.find('.filterly-reset-button').on('click', function(e) {
                if (useAjax) {
                    e.preventDefault();
                    Filterly.resetFilters($form, $container);
                }
                // If not using AJAX, let the link work normally
            });
        },

        /**
         * Initialize a range slider
         * 
         * @param {jQuery} $rangeSlider The range slider element
         */
        initRangeSlider: function($rangeSlider) {
            var $container = $rangeSlider.find('.filterly-range-slider-container');
            var $minInput = $rangeSlider.find('.filterly-range-min');
            var $maxInput = $rangeSlider.find('.filterly-range-max');
            var $minSlider = $container.find('.filterly-slider-min');
            var $maxSlider = $container.find('.filterly-slider-max');
            
            var min = parseFloat($container.data('min'));
            var max = parseFloat($container.data('max'));
            var step = parseFloat($container.data('step')) || 1;
            
            // Sync numeric inputs with sliders
            $minSlider.on('input', function() {
                var value = parseFloat($(this).val());
                var maxValue = parseFloat($maxSlider.val());
                
                // Ensure min doesn't exceed max
                if (value > maxValue) {
                    value = maxValue;
                    $(this).val(value);
                }
                
                $minInput.val(value);
            });
            
            $maxSlider.on('input', function() {
                var value = parseFloat($(this).val());
                var minValue = parseFloat($minSlider.val());
                
                // Ensure max isn't less than min
                if (value < minValue) {
                    value = minValue;
                    $(this).val(value);
                }
                
                $maxInput.val(value);
            });
            
            // Sync sliders with numeric inputs
            $minInput.on('input', function() {
                var value = parseFloat($(this).val());
                
                if (isNaN(value)) {
                    value = min;
                } else if (value < min) {
                    value = min;
                } else if (value > max) {
                    value = max;
                }
                
                $(this).val(value);
                $minSlider.val(value);
            });
            
            $maxInput.on('input', function() {
                var value = parseFloat($(this).val());
                
                if (isNaN(value)) {
                    value = max;
                } else if (value < min) {
                    value = min;
                } else if (value > max) {
                    value = max;
                }
                
                $(this).val(value);
                $maxSlider.val(value);
            });
        },

        /**
         * Submit filters via AJAX
         * 
         * @param {jQuery} $form The filter form
         * @param {jQuery} $container The filter container
         * @param {int} page Optional page number for pagination
         */
        submitFilters: function($form, $container, page) {
            var $resultsContainer = $container.find('.filterly-results-container');
            var containerId = $resultsContainer.attr('id');
            var postType = $container.data('post-type') || 'post';
            
            // Show loading state
            $container.addClass('filterly-loading');
            
            // Collect form data
            var formData = $form.serializeArray();
            var filters = {};
            
            // Convert form data to structured filters object
            $.each(formData, function(i, field) {
                var name = field.name;
                var value = field.value;
                
                // Handle array fields (with [] in the name)
                if (name.endsWith('[]')) {
                    var baseName = name.slice(0, -2);
                    if (!filters[baseName]) {
                        filters[baseName] = [];
                    }
                    filters[baseName].push(value);
                } else {
                    filters[name] = value;
                }
            });
            
            // AJAX call to get filtered results
            $.ajax({
                url: filterly_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'filterly_filter',
                    filters: filters,
                    post_type: postType,
                    paged: page || 1,
                    container_id: containerId,
                    nonce: filterly_params.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update results container
                        $resultsContainer.html(response.data.content);
                        
                        // Update browser history
                        Filterly.updateBrowserHistory(response.data.filtered_url, response.data.content, containerId);
                        
                        // Scroll to results if needed
                        if (page) {
                            $('html, body').animate({
                                scrollTop: $resultsContainer.offset().top - 100
                            }, 500);
                        }
                        
                        // Trigger event for third-party integrations
                        $(document).trigger('filterly:updated', [response.data, $container]);
                    } else {
                        console.error('Filterly Error:', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Filterly AJAX Error:', error);
                },
                complete: function() {
                    // Hide loading state
                    $container.removeClass('filterly-loading');
                }
            });
        },

        /**
         * Reset filters to default state
         * 
         * @param {jQuery} $form The filter form
         * @param {jQuery} $container The filter container
         */
        resetFilters: function($form, $container) {
            // Reset checkboxes and radio buttons
            $form.find('input[type="checkbox"], input[type="radio"]').prop('checked', false);
            
            // Reset selects
            $form.find('select').prop('selectedIndex', 0);
            
            // Reset range sliders
            $form.find('.filterly-range-slider').each(function() {
                var $slider = $(this);
                var $container = $slider.find('.filterly-range-slider-container');
                var $minInput = $slider.find('.filterly-range-min');
                var $maxInput = $slider.find('.filterly-range-max');
                var $minSlider = $container.find('.filterly-slider-min');
                var $maxSlider = $container.find('.filterly-slider-max');
                
                var min = parseFloat($container.data('min'));
                var max = parseFloat($container.data('max'));
                
                $minInput.val(min);
                $maxInput.val(max);
                $minSlider.val(min);
                $maxSlider.val(max);
            });
            
            // Submit the form to update results
            Filterly.submitFilters($form, $container);
        },

        /**
         * Update browser history
         * 
         * @param {string} url The URL to push to browser history
         * @param {string} content The HTML content for the results container
         * @param {string} containerId The ID of the results container
         */
        updateBrowserHistory: function(url, content, containerId) {
            if (window.history && window.history.pushState) {
                window.history.pushState({
                    filterly: true,
                    content: content,
                    containerId: containerId
                }, '', url);
            }
        },

        /**
         * Update form values based on URL parameters
         */
        updateFormFromUrl: function() {
            // Get URL parameters
            var urlParams = new URLSearchParams(window.location.search);
            
            // Loop through all filter forms
            $('.filterly-form').each(function() {
                var $form = $(this);
                
                // Reset form first
                $form.find('input[type="checkbox"], input[type="radio"]').prop('checked', false);
                $form.find('select').prop('selectedIndex', 0);
                
                // Update form elements based on URL parameters
                urlParams.forEach(function(value, key) {
                    var $field = $form.find('[name="' + key + '"]');
                    var $arrayField = $form.find('[name="' + key + '[]"]');
                    
                    // Handle regular inputs
                    if ($field.length) {
                        if ($field.is('select')) {
                            $field.val(value);
                        } else if ($field.is('input[type="radio"]')) {
                            $field.filter('[value="' + value + '"]').prop('checked', true);
                        } else {
                            $field.val(value);
                        }
                    }
                    
                    // Handle array inputs (checkboxes)
                    else if ($arrayField.length) {
                        // Split comma-separated values
                        var values = value.split(',');
                        values.forEach(function(val) {
                            $arrayField.filter('[value="' + val + '"]').prop('checked', true);
                        });
                    }
                    
                    // Handle range inputs
                    if (key.match(/\[min\]$/) || key.match(/\[max\]$/)) {
                        var baseKey = key.replace(/\[(min|max)\]$/, '');
                        var $rangeSlider = $form.find('.filterly-filter-' + baseKey + ' .filterly-range-slider');
                        
                        if ($rangeSlider.length) {
                            var type = key.match(/\[min\]$/) ? 'min' : 'max';
                            var $input = $rangeSlider.find('.filterly-range-' + type);
                            var $slider = $rangeSlider.find('.filterly-slider-' + type);
                            
                            $input.val(value);
                            $slider.val(value);
                        }
                    }
                });
            });
        },

        /**
         * Extract page number from pagination URL
         * 
         * @param {string} url The pagination URL
         * @return {int} The page number
         */
        getPageNumberFromUrl: function(url) {
            var matches = url.match(/page\/(\d+)/);
            if (matches) {
                return parseInt(matches[1], 10);
            }
            
            var urlObj = new URL(url);
            var page = urlObj.searchParams.get('paged') || urlObj.searchParams.get('page');
            if (page) {
                return parseInt(page, 10);
            }
            
            return 1;
        }
    };

    // Initialize when document is ready
    $(function() {
        Filterly.init();
    });

})(jQuery);