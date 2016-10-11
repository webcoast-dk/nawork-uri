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

(function($) {
    var UrlModule = function(element, options) {
        this.$element = $(element);
        this.options = $.extend({}, this.options, options || {});
        this.init();
    };

    UrlModule.prototype = {
        $element: null,
        options: {},
        filter: null,
        userSettings: null,
        ajaxUrl: null,
        $tableInner: null,
        $numberOfRecords: null,
        $page: null,
        $pagesMax: null,
        controls: {
            $first: null,
            $previous: null,
            $next: null,
            $last: null
        },

        init: function() {
            this.filter = this.$element.data('filter');
            this.userSettings = this.$element.data('settings');
            this.ajaxUrl = this.$element.data('ajaxUrl');
            this.$tableInner = this.$element.find('.urlTable__body__inner');
            this.$numberOfRecords = this.$element.find('.numberOfRecords');
            this.$page = this.$element.find('.paginator-input');
            this.$pagesMax = this.$element.find('.pagesMax');
            this.controls.$first = this.$element.find('.js-paginationFirst');
            this.controls.$previous = this.$element.find('.js-paginationPrevious');
            this.controls.$next = this.$element.find('.js-paginationNext');
            this.controls.$last = this.$element.find('.js-paginationLast');
            this.controls.$reload = this.$element.find('.js-reload');
            this.initListener();
            this.loadUrls()
        },

        initListener: function() {
            var _this = this;
            this.controls.$reload.click(function() {
                _this.loadUrls();
            });
        },

        loadUrls: function() {
            var _this = this;
            this.$tableInner.html('<div class="urlTable__row"><span class="urlTable__column urlTable__column--fullWidth">%s</span></div>'.format(tx_naworkuri_labels.loadingMessage));
            $.getJSON(this.ajaxUrl, {
                tx_naworkuri_naworkuri_naworkuriuri: {
                    filter: this.filter
                }
            }, function(data) {
                if (data) {
                    if (data.html && data.html.length > 0) {
                        _this.$tableInner.html(data.html);
                    }
                    if (data.start && data.end) {
                        _this.$numberOfRecords.text(tx_naworkuri_labels.numberOfRecords.format(data.start, data.end));
                    }
                    if (data.page && data.pagesMax) {
                        _this.$page.val(data.page);
                        _this.$pagesMax.text(data.pagesMax);
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
