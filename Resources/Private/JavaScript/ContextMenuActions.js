/**
 * Module: TYPO3/CMS/Nawork/NaworkUri/ContextMenuActions
 *
 * JavaScript to handle permissions module from context menu
 * @exports TYPO3/CMS/Nawork/NaworkUri/ContextMenuActions
 */
define(['jquery'], function ($) {
    'use strict';

    /**
     * @exports TYPO3/CMS/NaworkUri/ContextMenuActions
     */
    var ContextMenuActions = {};

    /**
     * Open permission module for given uid
     *
     * @param {string} table
     * @param {int} uid of the page
     */
    ContextMenuActions.lockUrl = function (table, uid) {
        $('.urlTable').trigger('lock', [uid]);
    };

    ContextMenuActions.unlockUrl = function(table, uid) {
        $('.urlTable').trigger('unlock', [uid]);
    };

    ContextMenuActions.delete = function(table, uid) {
        $('.urlTable').trigger('delete', [uid]);
    };

    ContextMenuActions.deleteSelected = function() {
        $('.urlTable').trigger('deleteSelected');
    };

    return ContextMenuActions;
});
