'use strict';

// extend string with a format method
String.prototype.format = function() {
    var replacements = arguments;
    var argumentCounter = 0;
    return this.replace(/(%(s|d))/g, function(m) {
        var value;
        switch (m) {
            case '%d':
                value = parseInt(replacements[argumentCounter]);
                if (isNaN(value)) {
                    value = 0;
                }
                break;
            default:
                value = replacements[argumentCounter];
        }
        ++argumentCounter;
        return value;
    });
};

// extend number prototype
Number.prototype.forceInRange = function(minimum, maximum) {
    if (this < minimum) {
        return minimum;
    } else if (this > maximum) {
        return maximum;
    }
    return this;
};

(function($) {
    var UrlModule = function(element, options) {
        this.$element = $(element);
        this.options = $.extend({}, this.options, options || {});
        this.init();
    };

    UrlModule.prototype = {
        $element: null,
        options: {
            inputChangeTimeout: 300
        },
        filter: null,
        userSettings: null,
        ajaxUrl: null,
        $tableInner: null,
        controls: {
            $first: null,
            $previous: null,
            $numberOfRecords: null,
            $page: null,
            $pagesMax: null,
            $next: null,
            $last: null
        },
        state: {
            ajaxCall: null,
            inputChangeTimeout: null,
            maxPages: 1
        },

        init: function() {
            this.filter = this.$element.data('filter');
            this.userSettings = this.$element.data('settings');
            this.ajaxUrl = this.$element.data('ajaxUrl');
            this.$tableInner = this.$element.find('.urlTable__body__inner');
            this.controls.$first = this.$element.find('.js-paginationFirst');
            this.controls.$previous = this.$element.find('.js-paginationPrevious');
            this.controls.$numberOfRecords = this.$element.find('.js-numberOfRecords');
            this.controls.$page = this.$element.find('.js-page');
            this.controls.$pagesMax = this.$element.find('.js-pagesMax');
            this.controls.$next = this.$element.find('.js-paginationNext');
            this.controls.$last = this.$element.find('.js-paginationLast');
            this.controls.$reload = this.$element.find('.js-reload');
            this.initListener();
            this.loadUrls()
        },

        initListener: function() {
            var _this = this;

             // domain select
            $('[name="DomainMenu"]').change(function() {
                var domainValue = $(this.options[this.selectedIndex]).data('domain');
                if (domainValue && !isNaN(domainValue) && domainValue !== _this.filter.domain) {
                    _this.filter.domain = domainValue;
                    _this.loadUrls();
                }
            });

            // type select
            $('[name="TypesMenu[]"]').change(function() {
                var typeValue = $(this).data('type');
                if (typeValue && typeValue.length > 0) {
                    if ($(this).is(':checked') && _this.filter.types.indexOf(typeValue) < 0) {
                        _this.filter.types.push(typeValue);
                    } else if (!$(this).is(':checked') && _this.filter.types.indexOf(typeValue) > -1) {
                        var indexToDelete = _this.filter.types.indexOf(typeValue);
                        if (!isNaN(indexToDelete)) {
                            _this.filter.types.splice(indexToDelete, 1);
                        }
                    }
                    _this.loadUrls();
                }
            });

            // language select
            $('[name="LanguageMenu"]').change(function() {
                var languageValue = parseInt($(this.options[this.selectedIndex]).data('language'));
                if (!isNaN(languageValue)) {
                    switch (languageValue) {
                        case -1:
                            _this.filter.ignoreLanguage = 1;
                            _this.filter.language = null;
                            break;
                        case 0:
                            _this.filter.ignoreLanguage = 0;
                            _this.filter.language = null;
                            break;
                        default:
                            _this.filter.ignoreLanguage = 0;
                            _this.filter.language = languageValue;
                    }
                    _this.loadUrls();
                }
            });

            // scope select
            $('[name="ScopeMenu"]').change(function() {
                var scopeValue = $(this.options[this.selectedIndex]).data('scope');
                if (scopeValue && scopeValue.length > 0 && scopeValue !== _this.filter.scope) {
                    _this.filter.scope = scopeValue;
                    _this.loadUrls();
                }
            });

            // search buttons
            this.$element.find('.js-icon').click(function() {
                var $this = $(this);
                $this.toggleClass('isVisible');
                $this.siblings('.js-icon').toggleClass('isVisible');
                var $input = $this.siblings('.urlTable__column__search').toggleClass('isVisible');
                if (!$input.hasClass('isVisible')) {
                    $input.val('');
                }
            });

            // path search field
            this.$element.find('.js-pathInput').on('customChange', function() {
                var $input = $(this);
                if (_this.state.inputChangeTimeout !== null) {
                    clearTimeout(_this.state.inputChangeTimeout);
                }
                setTimeout(function() {
                    if ($input.val() !== _this.filter.path) {
                        _this.filter.path = $input.val();
                        _this.loadUrls();
                    }
                }, _this.options.inputChangeTimeout);
            }).keyup(function() {
                $(this).trigger('customChange');
            }).change(function() {
                $(this).trigger('customChange');
            });

            // parameter search field
            this.$element.find('.js-parametersInput').on('customChange', function() {
                var $input = $(this);
                if (_this.state.inputChangeTimeout !== null) {
                    clearTimeout(_this.state.inputChangeTimeout);
                }
                setTimeout(function() {
                    if ($input.val() !== _this.filter.parameters) {
                        _this.filter.parameters = $input.val();
                        _this.loadUrls();
                    }
                }, _this.options.inputChangeTimeout);
            }).keyup(function() {
                $(this).trigger('customChange');
            }).change(function() {
                $(this).trigger('customChange');
            });

            // first button
            this.controls.$first.click(function() {
                if (!$(this).hasClass('disabled')) {
                    _this.filter.offset = 0;
                    _this.updatePagination(this.filter.offset);
                    _this.loadUrls();
                }
            });

            // previous button
            this.controls.$previous.click(function() {
                if (!$(this).hasClass('disabled')) {
                    _this.filter.offset = Math.min(0, _this.filter.offset - 1);
                    _this.updatePagination(this.filter.offset);
                    _this.loadUrls();
                }
            });

            // next button
            this.controls.$previous.click(function() {
                if (!$(this).hasClass('disabled')) {
                    _this.filter.offset = Math.max(_this.state.maxPages - 1, _this.filter.offset + 1);
                    _this.updatePagination(this.filter.offset);
                    _this.loadUrls();
                }
            });

            // last button
            this.controls.$previous.click(function() {
                if (!$(this).hasClass('disabled')) {
                    _this.filter.offset = _this.state.maxPages - 1;
                    _this.updatePagination(this.filter.offset);
                    _this.loadUrls();
                }
            });

            // pagination input
            this.controls.$page.keyup(function() {
                if (_this.state.inputChangeTimeout !== null) {
                    clearTimeout(_this.state.inputChangeTimeout);
                }
                _this.state.inputChangeTimeout = setTimeout(function() {
                    var page = parseInt(_this.controls.$page.val());
                    if (!isNaN(page)) {
                        var pageForcedValue = page.forceInRange(1, _this.state.maxPages);
                        if (pageForcedValue != _this.filter.offset + 1) {
                            _this.filter.offset = pageForcedValue - 1;
                        } else {
                            _this.updatePagination(_this.filter.offset + 1);
                        }
                    }
                }, _this.options.inputChangeTimeout);
            });

            // refresh/reload button
            this.controls.$reload.click(function() {
                _this.loadUrls();
            });
        },

        loadUrls: function() {
            var _this = this;
            this.$tableInner.html('<div class="urlTable__row"><span class="urlTable__column urlTable__column--fullWidth">%s</span></div>'.format(tx_naworkuri_labels.loadingMessage));
            // if there is a running call, cancel it
            if (this.state.ajaxCall !== null && this.state.ajaxCall.readyState !== XMLHttpRequest.DONE) {
                this.state.ajaxCall.abort();
            }
            this.state.ajaxCall = $.getJSON(this.ajaxUrl, {
                tx_naworkuri_naworkuri_naworkuriuri: {
                    filter: this.filter
                }
            }, function(data) {
                if (data) {
                    if (data.html && data.html.length > 0) {
                        _this.$tableInner.html(data.html);
                    }
                    if (data.start && data.end) {
                        _this.controls.$numberOfRecords.text(tx_naworkuri_labels.numberOfRecords.format(data.start, data.end));
                    }
                    if (data.page && data.pagesMax) {
                        _this.updatePagination(data.page, data.pagesMax);
                        if (data.page > 1) {
                            _this.controls.$first.removeClass('disabled');
                            _this.controls.$previous.removeClass('disabled');
                        } else {
                            _this.controls.$first.addClass('disabled');
                            _this.controls.$previous.addClass('disabled');
                        }

                        if (data.page < data.pagesMax) {
                            _this.controls.$next.removeClass('disabled');
                            _this.controls.$last.removeClass('disabled');
                        } else {
                            _this.controls.$next.addClass('disabled');
                            _this.controls.$last.addClass('disabled');
                        }
                    }
                }
            });
        },

        updatePagination: function(currentPage, maxPages) {
            this.controls.$page.val(currentPage);
            if (maxPages) {
                this.state.maxPages = maxPages;
                this.controls.$pagesMax.text(maxPages);
            }
        }
    };

    $.fn.UrlModule = function(options) {
        return this.each(function(index, element) {
            $(element).data('urlModule', new UrlModule(element, options));
        });
    };

    $(document).ready(function() {
        $('.urlTable').UrlModule();
    });
})(jQuery);
