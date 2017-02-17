"use strict";String.prototype.format=function(){var e=arguments,t=0;return this.replace(/(%(s|d))/g,function(n){var a;switch(n){case"%d":a=parseInt(e[t]),isNaN(a)&&(a=0);break;default:a=e[t]}return++t,a})},Number.prototype.forceInRange=function(e,t){return this<e?e:this>t?t:this},function(e){var t=function(t,n){this.$element=e(t),this.options=e.extend({},this.options,n||{}),this.init()};t.prototype={$element:null,options:{inputChangeTimeout:300},filter:null,userSettings:null,moduleParameterPrefix:null,$tableInner:null,controls:{$first:null,$previous:null,$numberOfRecords:null,$page:null,$pagesMax:null,$next:null,$last:null},urls:{load:null,lock:null,unlock:null,delete:null,deleteSelected:null},state:{ajaxCall:null,inputChangeTimeout:null,maxPages:1,selectedRecords:[],lastClickedRecord:null},contextMenu:null,init:function(){this.filter=this.$element.data("filter"),this.userSettings=this.$element.data("settings"),this.moduleParameterPrefix=this.$element.data("moduleParameterPrefix"),this.contextMenuType=this.$element.data("menu"),this.urls.load=this.$element.data("ajaxUrlLoad"),this.urls.lock=this.$element.data("ajaxUrlLock"),this.urls.unlock=this.$element.data("ajaxUrlUnlock"),this.urls.delete=this.$element.data("ajaxUrlDelete"),this.urls.deleteSelected=this.$element.data("ajaxUrlDeleteSelected"),this.$tableInner=this.$element.find(".urlTable__body__inner"),this.controls.$first=this.$element.find(".js-paginationFirst"),this.controls.$previous=this.$element.find(".js-paginationPrevious"),this.controls.$numberOfRecords=this.$element.find(".js-numberOfRecords"),this.controls.$page=this.$element.find(".js-page"),this.controls.$pagesMax=this.$element.find(".js-pagesMax"),this.controls.$next=this.$element.find(".js-paginationNext"),this.controls.$last=this.$element.find(".js-paginationLast"),this.controls.$reload=this.$element.find(".js-reload");var e=this;"ClickMenu"===this.contextMenuType?require(["TYPO3/CMS/Backend/ClickMenu"],function(t){e.contextMenu=t}):require(["TYPO3/CMS/Backend/ContextMenu"],function(t){e.contextMenu=t}),this.initListener(),this.initContextMenuEvents(),this.loadUrls()},initListener:function(){var t=this;e('[name="DomainMenu"]').change(function(){var n=e(this.options[this.selectedIndex]).data("domain");n&&!isNaN(n)&&n!==t.filter.domain&&(t.filter.domain=n,t.filter.offset=0,t.loadUrls())}),e('[name="TypesMenu[]"]').change(function(){var n=e(this).data("type");if(n&&n.length>0){if(e(this).is(":checked")&&t.filter.types.indexOf(n)<0)t.filter.types.push(n);else if(!e(this).is(":checked")&&t.filter.types.indexOf(n)>-1){var a=t.filter.types.indexOf(n);isNaN(a)||t.filter.types.splice(a,1)}t.filter.offset=0,t.loadUrls()}}),e('[name="LanguageMenu"]').change(function(){var n=parseInt(e(this.options[this.selectedIndex]).data("language"));if(!isNaN(n)){switch(n){case-1:t.filter.ignoreLanguage=1,t.filter.language=null;break;case 0:t.filter.ignoreLanguage=0,t.filter.language=null;break;default:t.filter.ignoreLanguage=0,t.filter.language=n}t.filter.offset=0,t.loadUrls()}}),e('[name="ScopeMenu"]').change(function(){var n=e(this.options[this.selectedIndex]).data("scope");n&&n.length>0&&n!==t.filter.scope&&(t.filter.scope=n,t.filter.offset=0,t.loadUrls())}),this.$element.find(".js-icon").click(function(){var t=e(this);t.toggleClass("isVisible"),t.siblings(".js-icon").toggleClass("isVisible");var n=t.siblings(".urlTable__column__search").toggleClass("isVisible");n.hasClass("isVisible")?n[0].focus():(n.val(""),n.trigger("change"))}),this.$element.find(".js-pathInput").on("customChange",function(){var n=e(this);null!==t.state.inputChangeTimeout&&clearTimeout(t.state.inputChangeTimeout),setTimeout(function(){n.val()!==t.filter.path&&(t.filter.path=n.val(),t.filter.offset=0,t.loadUrls())},t.options.inputChangeTimeout)}).keyup(function(){e(this).trigger("customChange")}).change(function(){e(this).trigger("customChange")}),this.$element.find(".js-parametersInput").on("customChange",function(){var n=e(this);null!==t.state.inputChangeTimeout&&clearTimeout(t.state.inputChangeTimeout),setTimeout(function(){n.val()!==t.filter.parameters&&(t.filter.parameters=n.val(),t.filter.offset=0,t.loadUrls())},t.options.inputChangeTimeout)}).keyup(function(){e(this).trigger("customChange")}).change(function(){e(this).trigger("customChange")}),this.controls.$first.click(function(){e(this).hasClass("disabled")||(t.filter.offset=0,t.updatePagination(t.filter.offset),t.loadUrls())}),this.controls.$previous.click(function(){e(this).hasClass("disabled")||(t.filter.offset=t.filter.offset.forceInRange(0,t.filter.offset-1),t.updatePagination(t.filter.offset),t.loadUrls())}),this.controls.$next.click(function(){e(this).hasClass("disabled")||(t.filter.offset=t.filter.offset.forceInRange(t.filter.offset+1,t.state.maxPages-1),t.updatePagination(t.filter.offset),t.loadUrls())}),this.controls.$last.click(function(){e(this).hasClass("disabled")||(t.filter.offset=t.state.maxPages-1,t.updatePagination(t.filter.offset),t.loadUrls())}),this.controls.$page.keyup(function(){null!==t.state.inputChangeTimeout&&clearTimeout(t.state.inputChangeTimeout),t.state.inputChangeTimeout=setTimeout(function(){var e=parseInt(t.controls.$page.val())-1;if(!isNaN(e)){var n=e.forceInRange(0,t.state.maxPages-1);n!=t.filter.offset?(t.filter.offset=n,t.loadUrls()):t.updatePagination(t.filter.offset)}},t.options.inputChangeTimeout)}),this.controls.$reload.click(function(){t.loadUrls()})},initTableRowSelect:function(){var t=this;this.state.lastClickedRecord=null,this.$tableInner.find(".urlTable__row").each(function(n,a){e(a).data("rowIndex",n),e(a).find(".js-icon").click(function(n){n.stopImmediatePropagation(),t.openClickMenu(e(this).parents(".urlTable__row").data("uid"))})}).click(function(n){var a=e(this);if(n.ctrlKey===!1&&n.metaKey===!1&&n.shiftKey===!1||!t.state.lastClickedRecord)t.deSelectAllRecords(),t.selectRecord(a),n.shiftKey===!0&&window.getSelection().removeAllRanges();else if(n.ctrlKey!==!0&&n.metaKey!==!0||n.shiftKey!==!1){if(n.shiftKey===!0){if(i=t.state.lastClickedRecord,t.state.lastClickedRecord.data("rowIndex")<a.data("rowIndex")){do{i.hasClass("isSelected")||t.selectRecord(i);var i=i.next()}while(i.data("rowIndex")<=a.data("rowIndex"))}else do i.hasClass("isSelected")||t.selectRecord(i),i=i.prev();while(i.data("rowIndex")>=a.data("rowIndex"));window.getSelection().removeAllRanges()}}else a.hasClass("isSelected")?t.deSelectRecord(a):t.selectRecord(a);t.state.lastClickedRecord=a}).contextmenu(function(n){for(var a=e(n.currentTarget),i=!0,s=0;s<window.getSelection().rangeCount;s++){var o=window.getSelection().getRangeAt(s).toString();if(o&&o.length>0){var l=e(window.getSelection().getRangeAt(s).startContainer).parents(".urlTable__row");l.data("rowIndex")===a.data("rowIndex")?i=!1:(l=e(window.getSelection().getRangeAt(s).endContainer).parents(".urlTable__row"),l.data("rowIndex")===a.data("rowIndex")&&(i=!1))}}i&&(n.preventDefault(),t.openClickMenu(e(this).data("uid")))})},initContextMenuEvents:function(){this.$element.on("lock",function(t,n){var a=e(this).data("urlModule");e.ajax({url:a.urls.lock,data:{tx_naworkuri_naworkuri_naworkuriuri:{url:n}},success:function(){a.loadUrls()},error:function(t,n,a){var i=top.TYPO3.Modal.confirm(tx_naworkuri_labels.title.error,tx_naworkuri_labels.message.error.format(a),top.TYPO3.Severity.error,[{text:e(this).data("button-ok-text")||top.TYPO3.lang["button.ok"]||"OK",btnClass:"btn-"+top.TYPO3.Modal.getSeverityClass(top.TYPO3.Severity.error),name:"ok"}]);i.on("confirm.button.ok",function(){top.TYPO3.Modal.dismiss()})}})}),this.$element.on("unlock",function(t,n){var a=e(this).data("urlModule");e.ajax({url:a.urls.unlock,data:{tx_naworkuri_naworkuri_naworkuriuri:{url:n}},success:function(){a.loadUrls()},error:function(t,n,a){var i=top.TYPO3.Modal.confirm(tx_naworkuri_labels.title.error,tx_naworkuri_labels.message.error.format(a),top.TYPO3.Severity.error,[{text:e(this).data("button-ok-text")||top.TYPO3.lang["button.ok"]||"OK",btnClass:"btn-"+top.TYPO3.Modal.getSeverityClass(top.TYPO3.Severity.error),name:"ok"}]);i.on("confirm.button.ok",function(){top.TYPO3.Modal.dismiss()})}})}),this.$element.on("delete",function(t,n){var a=e(this).data("urlModule"),i=a.$element.find('[data-uid="'+n+'"]').first();if(i&&1===i.length){var s=i.find(".urlTable__column--text").first();if(s&&1===s.length){var o=s.text(),l=top.TYPO3.Modal.confirm(tx_naworkuri_labels.title.delete,tx_naworkuri_labels.message.delete.format(o),top.TYPO3.Severity.warning);l.on("confirm.button.cancel",function(){top.TYPO3.Modal.dismiss()}),l.on("confirm.button.ok",function(){top.TYPO3.Modal.dismiss(),e.ajax({url:a.urls.delete,data:{tx_naworkuri_naworkuri_naworkuriuri:{url:n}},success:function(){a.loadUrls()},error:function(t,n,a){var i=top.TYPO3.Modal.confirm(tx_naworkuri_labels.title.error,tx_naworkuri_labels.message.error.format(a),top.TYPO3.Severity.error,[{text:e(this).data("button-ok-text")||top.TYPO3.lang["button.ok"]||"OK",btnClass:"btn-"+top.TYPO3.Modal.getSeverityClass(top.TYPO3.Severity.error),name:"ok"}]);i.on("confirm.button.ok",function(){top.TYPO3.Modal.dismiss()})}})})}}}),this.$element.on("deleteSelected",function(){var t=e(this).data("urlModule"),n=[];e.each(t.state.selectedRecords,function(e,t){var a=t.data("uid");isNaN(a)||n.push(a)});var a=top.TYPO3.Modal.confirm(tx_naworkuri_labels.title.deleteSelected,tx_naworkuri_labels.message.deleteSelected.format(n.length),top.TYPO3.Severity.warning);a.on("confirm.button.cancel",function(){top.TYPO3.Modal.dismiss()}),a.on("confirm.button.ok",function(){top.TYPO3.Modal.dismiss(),e.ajax({url:t.urls.deleteSelected,data:{tx_naworkuri_naworkuri_naworkuriuri:{uids:n}},success:function(){t.loadUrls()},error:function(t,n,a){var i=top.TYPO3.Modal.confirm(tx_naworkuri_labels.title.error,tx_naworkuri_labels.message.error.format(a),top.TYPO3.Severity.error,[{text:e(this).data("button-ok-text")||top.TYPO3.lang["button.ok"]||"OK",btnClass:"btn-"+top.TYPO3.Modal.getSeverityClass(top.TYPO3.Severity.error),name:"ok"}]);i.on("confirm.button.ok",function(){top.TYPO3.Modal.dismiss()})}})})})},selectRecord:function(e){this.state.selectedRecords.push(e),e.addClass("isSelected")},deSelectRecord:function(t){var n=-1;e.each(this.state.selectedRecords,function(e,a){a.data("uid")==t.data("uid")&&(n=e)}),!isNaN(n)&&n>-1&&(t.removeClass("isSelected"),this.state.selectedRecords.splice(n,1))},deSelectAllRecords:function(){e.each(this.state.selectedRecords,function(e,t){t.removeClass("isSelected")}),this.state.selectedRecords.length=0},loadUrls:function(){var t=this;this.$tableInner.html('<div class="urlTable__row"><span class="urlTable__column urlTable__column--fullWidth">%s</span></div>'.format(tx_naworkuri_labels.loadingMessage)),null!==this.state.ajaxCall&&this.state.ajaxCall.readyState!==XMLHttpRequest.DONE&&this.state.ajaxCall.abort();var n={};n[this.moduleParameterPrefix]={},n[this.moduleParameterPrefix].filter=this.filter,this.state.ajaxCall=e.getJSON(this.urls.load,n,function(e){e&&(e.html&&e.html.length>0&&(t.$tableInner.html(e.html),t.initTableRowSelect()),e.start&&e.end?t.controls.$numberOfRecords.text(tx_naworkuri_labels.numberOfRecords.format(e.start,e.end)):t.controls.$numberOfRecords.text(tx_naworkuri_labels.numberOfRecords.format(0,0)),isNaN(e.page)||isNaN(e.pagesMax)?t.updatePagination(0,0):(t.updatePagination(e.page,e.pagesMax),e.page>0?(t.controls.$first.removeClass("disabled"),t.controls.$previous.removeClass("disabled")):(t.controls.$first.addClass("disabled"),t.controls.$previous.addClass("disabled")),e.page<e.pagesMax-1?(t.controls.$next.removeClass("disabled"),t.controls.$last.removeClass("disabled")):(t.controls.$next.addClass("disabled"),t.controls.$last.addClass("disabled"))))})},updatePagination:function(e,t){this.controls.$page.val(e+1),isNaN(t)||(this.state.maxPages=t,this.controls.$pagesMax.text(t))},openClickMenu:function(e){"ClickMenu"===this.contextMenuType?this.contextMenu.show("tx_naworkuri_uri",e,"1",encodeURIComponent("+")+"show,edit,lock,unlock,delete"+(this.state.selectedRecords.length>0?",deleteSelected":""),""):this.contextMenu.show("tx_naworkuri_uri",e,this.state.selectedRecords.length<2?"single":"multiple")}},e.fn.UrlModule=function(n){return this.each(function(a,i){e(i).data("urlModule",new t(i,n))})},e(document).ready(function(){e(".urlTable").UrlModule()})}(jQuery);