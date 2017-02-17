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
        moduleParameterPrefix: null,
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
        urls: {
            load: null,
            lock: null,
            unlock: null,
            delete: null,
            deleteSelected: null
        },
        state: {
            ajaxCall: null,
            inputChangeTimeout: null,
            maxPages: 1,
            selectedRecords: [],
            lastClickedRecord: null
        },
        contextMenu: null,

        init: function() {
            this.filter = this.$element.data('filter');
            this.userSettings = this.$element.data('settings');
            this.moduleParameterPrefix = this.$element.data('moduleParameterPrefix');
            this.contextMenuType = this.$element.data('menu');
            // set urls
            this.urls.load = this.$element.data('ajaxUrlLoad');
            this.urls.lock = this.$element.data('ajaxUrlLock');
            this.urls.unlock = this.$element.data('ajaxUrlUnlock');
            this.urls.delete = this.$element.data('ajaxUrlDelete');
            this.urls.deleteSelected = this.$element.data('ajaxUrlDeleteSelected');
            // table body
            this.$tableInner = this.$element.find('.urlTable__body__inner');
            // controls
            this.controls.$first = this.$element.find('.js-paginationFirst');
            this.controls.$previous = this.$element.find('.js-paginationPrevious');
            this.controls.$numberOfRecords = this.$element.find('.js-numberOfRecords');
            this.controls.$page = this.$element.find('.js-page');
            this.controls.$pagesMax = this.$element.find('.js-pagesMax');
            this.controls.$next = this.$element.find('.js-paginationNext');
            this.controls.$last = this.$element.find('.js-paginationLast');
            this.controls.$reload = this.$element.find('.js-reload');
            // load the context menu
            var me = this;
            // if available use the old ClickMenu
            if (this.contextMenuType === 'ClickMenu') {
                require(['TYPO3/CMS/Backend/ClickMenu'], function(ClickMenu) {
                    me.contextMenu = ClickMenu;
                });
            } else {
                // otherwise require the new ContextMenu
                require(['TYPO3/CMS/Backend/ContextMenu'], function (ContextMenu) {
                    me.contextMenu = ContextMenu;
                });
            }
            this.initListener();
            this.initContextMenuEvents();
            this.loadUrls()
        },

        initListener: function() {
            var _this = this;

             // domain select
            $('[name="DomainMenu"]').change(function() {
                var domainValue = $(this.options[this.selectedIndex]).data('domain');
                if (domainValue && !isNaN(domainValue) && domainValue !== _this.filter.domain) {
                    _this.filter.domain = domainValue;
                    _this.filter.offset = 0; // always reset pagination on search
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
                    _this.filter.offset = 0; // always reset pagination on search
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
                    _this.filter.offset = 0; // always reset pagination on search
                    _this.loadUrls();
                }
            });

            // scope select
            $('[name="ScopeMenu"]').change(function() {
                var scopeValue = $(this.options[this.selectedIndex]).data('scope');
                if (scopeValue && scopeValue.length > 0 && scopeValue !== _this.filter.scope) {
                    _this.filter.scope = scopeValue;
                    _this.filter.offset = 0; // always reset pagination on search
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
                    $input.trigger('change');
                } else {
                    // focus the input field
                    $input[0].focus();
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
                        _this.filter.offset = 0; // always reset pagination on search
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
                        _this.filter.offset = 0; // always reset pagination on search
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
                    _this.updatePagination(_this.filter.offset);
                    _this.loadUrls();
                }
            });

            // previous button
            this.controls.$previous.click(function() {
                if (!$(this).hasClass('disabled')) {
                    _this.filter.offset = _this.filter.offset.forceInRange(0, _this.filter.offset - 1);
                    _this.updatePagination(_this.filter.offset);
                    _this.loadUrls();
                }
            });

            // next button
            this.controls.$next.click(function() {
                if (!$(this).hasClass('disabled')) {
                    _this.filter.offset = _this.filter.offset.forceInRange(_this.filter.offset + 1, _this.state.maxPages - 1);
                    _this.updatePagination(_this.filter.offset);
                    _this.loadUrls();
                }
            });

            // last button
            this.controls.$last.click(function() {
                if (!$(this).hasClass('disabled')) {
                    _this.filter.offset = _this.state.maxPages -1;
                    _this.updatePagination(_this.filter.offset);
                    _this.loadUrls();
                }
            });

            // pagination input
            this.controls.$page.keyup(function() {
                if (_this.state.inputChangeTimeout !== null) {
                    clearTimeout(_this.state.inputChangeTimeout);
                }
                _this.state.inputChangeTimeout = setTimeout(function() {
                    var page = parseInt(_this.controls.$page.val()) - 1;
                    if (!isNaN(page)) {
                        var pageForcedValue = page.forceInRange(0, _this.state.maxPages - 1);
                        if (pageForcedValue != _this.filter.offset) {
                            _this.filter.offset = pageForcedValue;
                            _this.loadUrls();
                        } else {
                            _this.updatePagination(_this.filter.offset);
                        }
                    }
                }, _this.options.inputChangeTimeout);
            });

            // refresh/reload button
            this.controls.$reload.click(function() {
                _this.loadUrls();
            });
        },

        initTableRowSelect: function() {
            var _this = this;
            // reset the last clicked record
            this.state.lastClickedRecord = null;

            this.$tableInner.find('.urlTable__row').each(function(index, element) {
                // use this indexing for identifying the click direction later
                $(element).data('rowIndex', index);
                $(element).find('.js-icon').click(function(ev) {
                    ev.stopImmediatePropagation();
                    _this.openClickMenu($(this).parents('.urlTable__row').data('uid'));
                });
            }).click(function(ev) {
                var $clickedItem = $(this);
                if (ev.ctrlKey === false && ev.metaKey === false && ev.shiftKey === false || !_this.state.lastClickedRecord) {
                    // default case, normal click
                    _this.deSelectAllRecords();
                    _this.selectRecord($clickedItem);
                    if (ev.shiftKey === true) {
                        // remove selection if the first item is selected with pressed shift key
                        window.getSelection().removeAllRanges();
                    }
                } else if ((ev.ctrlKey === true || ev.metaKey === true) && ev.shiftKey === false) {
                    // add/remove single item with CTRL or CMD
                    if ($clickedItem.hasClass('isSelected')) {
                        _this.deSelectRecord($clickedItem);
                    } else {
                        _this.selectRecord($clickedItem);
                    }
                } else if (ev.shiftKey === true) {
                    // add range
                    $nextItem = _this.state.lastClickedRecord;
                    if (_this.state.lastClickedRecord.data('rowIndex') < $clickedItem.data('rowIndex')) {
                        // select records downwards from the last to the currently clicked item
                        do {
                            if (!$nextItem.hasClass('isSelected')) {
                                // avoid double section
                                _this.selectRecord($nextItem);
                            }
                            var $nextItem = $nextItem.next();
                        } while($nextItem.data('rowIndex') <= $clickedItem.data('rowIndex'));
                    } else {
                        // select records upwards from the last to the currently clicked item
                        do {
                            if (!$nextItem.hasClass('isSelected')) {
                                // avoid double section
                                _this.selectRecord($nextItem);
                            }
                            $nextItem = $nextItem.prev();
                        } while ($nextItem.data('rowIndex') >= $clickedItem.data('rowIndex'));
                    }
                    // seems a bit ugly, but avoids text selection after shift click
                    window.getSelection().removeAllRanges();
                }
                _this.state.lastClickedRecord = $clickedItem;
            }).contextmenu(function(ev) {
                // show the TYPO3 click menu on "right" click
                var $target = $(ev.currentTarget);
                var catchEvent = true;
                for (var i=0; i<window.getSelection().rangeCount; i++) {
                    var selected = window.getSelection().getRangeAt(i).toString();
                    if (selected && selected.length > 0) {
                        // if there is text selected in the clicked row, do not catch the event and do not show
                        // the TYPO3 click menu, but the default context menu to allow to copy the selected text
                        var $container = $(window.getSelection().getRangeAt(i).startContainer).parents('.urlTable__row');
                        if ($container.data('rowIndex') === $target.data('rowIndex')) {
                            catchEvent = false;
                        } else {
                            $container = $(window.getSelection().getRangeAt(i).endContainer).parents('.urlTable__row');
                            if ($container.data('rowIndex') === $target.data('rowIndex')) {
                                catchEvent = false;
                            }
                        }
                    }
                }
                if (catchEvent) {
                    ev.preventDefault();
                    _this.openClickMenu($(this).data('uid'));
                }
            });
        },

        initContextMenuEvents: function() {
            this.$element.on('lock', function(ev, uid) {
                var _this = $(this).data('urlModule');
                $.ajax({
                    url: _this.urls.lock,
                    data: {
                        tx_naworkuri_naworkuri_naworkuriuri: {
                            url: uid
                        }
                    },
                    success: function() {
                        // just reload the urls
                        _this.loadUrls();
                    },
                    error: function(request, status, serverMessage) {
                        var $modal = top.TYPO3.Modal.confirm(tx_naworkuri_labels.title.error, tx_naworkuri_labels.message.error.format(serverMessage), top.TYPO3.Severity.error, [{
                            text: $(this).data('button-ok-text') || top.TYPO3.lang['button.ok'] || 'OK',
                            btnClass: 'btn-' + top.TYPO3.Modal.getSeverityClass(top.TYPO3.Severity.error),
                            name: 'ok'
                        }]);
                        $modal.on('confirm.button.ok', function() {
                            top.TYPO3.Modal.dismiss();
                        });
                    }
                });
            });

            this.$element.on('unlock', function(ev, uid) {
                var _this = $(this).data('urlModule');
                $.ajax({
                    url: _this.urls.unlock,
                    data: {
                        tx_naworkuri_naworkuri_naworkuriuri: {
                            url: uid
                        }
                    },
                    success: function() {
                        // just reload the urls
                        _this.loadUrls();
                    },
                    error: function(request, status, serverMessage) {
                        var $modal = top.TYPO3.Modal.confirm(tx_naworkuri_labels.title.error, tx_naworkuri_labels.message.error.format(serverMessage), top.TYPO3.Severity.error, [{
                            text: $(this).data('button-ok-text') || top.TYPO3.lang['button.ok'] || 'OK',
                            btnClass: 'btn-' + top.TYPO3.Modal.getSeverityClass(top.TYPO3.Severity.error),
                            name: 'ok'
                        }]);
                        $modal.on('confirm.button.ok', function() {
                            top.TYPO3.Modal.dismiss();
                        });
                    }
                });
            });

            this.$element.on('delete', function(ev, uid) {
                var _this = $(this).data('urlModule');
                var $row = _this.$element.find('[data-uid="' + uid + '"]').first();
                if ($row && $row.length === 1) {
                    var $url = $row.find('.urlTable__column--text').first();
                    if ($url && $url.length === 1) {
                        var path = $url.text();
                        var $modal = top.TYPO3.Modal.confirm(tx_naworkuri_labels.title.delete, tx_naworkuri_labels.message.delete.format(path), top.TYPO3.Severity.warning);
                        $modal.on('confirm.button.cancel', function () {
                            top.TYPO3.Modal.dismiss();
                        });
                        $modal.on('confirm.button.ok', function () {
                            top.TYPO3.Modal.dismiss();
                            $.ajax({
                                url: _this.urls.delete,
                                data: {
                                    tx_naworkuri_naworkuri_naworkuriuri: {
                                        url: uid
                                    }
                                },
                                success: function () {
                                    // just reload the urls
                                    _this.loadUrls();
                                },
                                error: function (request, status, serverMessage) {
                                    var $modal = top.TYPO3.Modal.confirm(tx_naworkuri_labels.title.error, tx_naworkuri_labels.message.error.format(serverMessage), top.TYPO3.Severity.error, [{
                                        text: $(this).data('button-ok-text') || top.TYPO3.lang['button.ok'] || 'OK',
                                        btnClass: 'btn-' + top.TYPO3.Modal.getSeverityClass(top.TYPO3.Severity.error),
                                        name: 'ok'
                                    }]);
                                    $modal.on('confirm.button.ok', function () {
                                        top.TYPO3.Modal.dismiss();
                                    });
                                }
                            });
                        });
                    }
                }
            });

            this.$element.on('deleteSelected', function() {
                var _this = $(this).data('urlModule');
                var uids = [];
                $.each(_this.state.selectedRecords, function(index, $element) {
                    var uid = $element.data('uid');
                    if (!isNaN(uid)) {
                        uids.push(uid);
                    }
                });
                var $modal = top.TYPO3.Modal.confirm(tx_naworkuri_labels.title.deleteSelected, tx_naworkuri_labels.message.deleteSelected.format(uids.length), top.TYPO3.Severity.warning);
                $modal.on('confirm.button.cancel', function() {
                    top.TYPO3.Modal.dismiss();
                });
                $modal.on('confirm.button.ok', function() {
                    top.TYPO3.Modal.dismiss();
                    $.ajax({
                        url: _this.urls.deleteSelected,
                        data: {
                            tx_naworkuri_naworkuri_naworkuriuri: {
                                uids: uids
                            }
                        },
                        success: function() {
                            // just reload the urls
                            _this.loadUrls();
                        },
                        error: function(request, status, serverMessage) {
                            var $modal = top.TYPO3.Modal.confirm(tx_naworkuri_labels.title.error, tx_naworkuri_labels.message.error.format(serverMessage), top.TYPO3.Severity.error, [{
                                text: $(this).data('button-ok-text') || top.TYPO3.lang['button.ok'] || 'OK',
                                btnClass: 'btn-' + top.TYPO3.Modal.getSeverityClass(top.TYPO3.Severity.error),
                                name: 'ok'
                            }]);
                            $modal.on('confirm.button.ok', function() {
                                top.TYPO3.Modal.dismiss();
                            });
                        }
                    });
                });
            });
        },

        selectRecord: function($clickedItem) {
            this.state.selectedRecords.push($clickedItem);
            $clickedItem.addClass('isSelected');
        },

        deSelectRecord: function($clickedItem) {
            var indexToDelete = -1;
            $.each(this.state.selectedRecords, function (index, $element) {
                if ($element.data('uid') == $clickedItem.data('uid')) {
                    indexToDelete = index;
                }
            });
            if (!isNaN(indexToDelete) && indexToDelete > -1) {
                $clickedItem.removeClass('isSelected');
                this.state.selectedRecords.splice(indexToDelete, 1);
            }
        },

        deSelectAllRecords: function() {
            // clear the array
            $.each(this.state.selectedRecords, function(index, element) {
               element.removeClass('isSelected');
            });
            this.state.selectedRecords.length = 0;
        },

        loadUrls: function() {
            var _this = this;
            this.$tableInner.html('<div class="urlTable__row"><span class="urlTable__column urlTable__column--fullWidth">%s</span></div>'.format(tx_naworkuri_labels.loadingMessage));
            // if there is a running call, cancel it
            if (this.state.ajaxCall !== null && this.state.ajaxCall.readyState !== XMLHttpRequest.DONE) {
                this.state.ajaxCall.abort();
            }
            var requestData = {};
            requestData[this.moduleParameterPrefix] = {};
            requestData[this.moduleParameterPrefix].filter = this.filter;
            this.state.ajaxCall = $.getJSON(this.urls.load, requestData, function(data) {
                if (data) {
                    if (data.html && data.html.length > 0) {
                        _this.$tableInner.html(data.html);
                        _this.initTableRowSelect();
                    }
                    if (data.start && data.end) {
                        _this.controls.$numberOfRecords.text(tx_naworkuri_labels.numberOfRecords.format(data.start, data.end));
                    } else {
                        _this.controls.$numberOfRecords.text(tx_naworkuri_labels.numberOfRecords.format(0, 0));
                    }
                    if (!isNaN(data.page) && !isNaN(data.pagesMax)) {
                        _this.updatePagination(data.page, data.pagesMax);
                        if (data.page > 0) {
                            _this.controls.$first.removeClass('disabled');
                            _this.controls.$previous.removeClass('disabled');
                        } else {
                            _this.controls.$first.addClass('disabled');
                            _this.controls.$previous.addClass('disabled');
                        }

                        if (data.page < data.pagesMax -1) {
                            _this.controls.$next.removeClass('disabled');
                            _this.controls.$last.removeClass('disabled');
                        } else {
                            _this.controls.$next.addClass('disabled');
                            _this.controls.$last.addClass('disabled');
                        }
                    } else {
                        _this.updatePagination(0, 0);
                    }
                }
            });
        },

        updatePagination: function(currentPage, maxPages) {
            // the +1 is for the user, internal is one less
            this.controls.$page.val(currentPage + 1);
            if (!isNaN(maxPages)) {
                this.state.maxPages = maxPages;
                this.controls.$pagesMax.text(maxPages);
            }
        },

        openClickMenu: function(uid) {
            if (this.contextMenuType === 'ClickMenu') {
                // old menu used in version 7
                this.contextMenu.show('tx_naworkuri_uri', uid, '1', encodeURIComponent('+') + 'show,edit,lock,unlock,delete' + (this.state.selectedRecords.length > 0 ? ',deleteSelected' : ''), '');
            } else {
                // new menu used in version 8 and above
                this.contextMenu.show('tx_naworkuri_uri', uid, this.state.selectedRecords.length < 2 ? 'single' : 'multiple');
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
