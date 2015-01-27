if(typeof NaworkUri != 'object') {
	NaworkUri = {};
}
NaworkUri.RedirectModule = new Class({
	Extends: NaworkUri.UrlModule,
	
	updateUserSettings: function() {
		var obj = this;
		if(this.selectedHeaderCell != null) {
			if(obj.requests.setting != null) {
				obj.requests.setting.abort();
			}
			obj.requests.setting = jQuery.ajax({
				url: obj.options.urls.settings,
				dataType: "json",
				data: {
					tx_naworkuri_tools_naworkuritxnaworkuriredirect: {
						key: "columnWidth." + obj.selectedHeaderCell.attr("data-cellClassName"),
						value: obj.selectedHeaderCell.width()
					}
				},
				success: function() {
					obj.requests.setting = jQuery.ajax({
						url: obj.options.urls.settings,
						dataType: "json",
						data: {
							tx_naworkuri_tools_naworkuritxnaworkuriredirect: {
								key: "columnWidth." + obj.selectedHeaderCell.next(".resizeable").attr("data-cellClassName"),
								value: obj.selectedHeaderCell.next(".resizeable").width()
							}
						},
						complete: function() {
							obj.selectedHeaderCell = null;
						}
					})
				}
			});
		}
	},
	
	loadContextMenu: function(row, ev) {
		var obj = this;
		if(obj.requests.contextMenu != null) {
			obj.requests.contextMenu.abort();
		}
		obj.requests.contextMenu = jQuery.ajax({
			url: row.attr("data-contextmenuurl"),
			dataType: "html",
			data: {
				tx_naworkuri_naworkuri_naworkuriredirect: {
					includeAddOption: true
				}
			},
			success: function(response, status, request) {
				if(jQuery.trim(response).length > 0) {
					obj.openContextMenu(response, ev);
				}
			}
		})
	},
	
	loadUrls: function() {
		if(this.requests.ajax != null) {
			this.requests.ajax.abort();
		}
		var tableBody = this.jqueryObject.find("#table_body");
		this.loadingLayer.css({
			top: tableBody.position().top,
			left: tableBody.position().left,
			width: tableBody.outerWidth(true),
			height: tableBody.outerHeight(true),
			display: "block"
		});
		var obj = this;
		this.requests.ajax = jQuery.ajax({
			url: this.options.urls.ajax,
			dataType: "json",
			data: {
				tx_naworkuri_naworkuri_naworkuriredirect: {
					domain: this.filter.domain.value,
					path: this.filter.path.value,
					offset: this.pagination.currentPage * this.pagination.pageSize,
					limit: this.pagination.pageSize
				}
			},
			success: function(response, status, request) {
				if(response && response.html && jQuery.trim(response.html).length > 0) {
					obj.jqueryObject.find("#table_body tbody").html(response.html);
					obj.pagination.recordCount = response.count;
					obj.pagination.maxPages = Math.ceil(response.count / obj.pagination.pageSize);
					obj.jqueryObject.find("#pagination_maxpages").html(obj.pagination.maxPages);
					obj.jqueryObject.find("#pagination_pagenumber").html(obj.pagination.currentPage + 1);
					obj.jqueryObject.find("#pagination_urlcount_from").html(obj.pagination.currentPage * obj.pagination.pageSize + 1);
					obj.jqueryObject.find("#pagination_urlcount_to").html(Math.min((obj.pagination.currentPage + 1 )* obj.pagination.pageSize, obj.pagination.recordCount));
					obj.jqueryObject.find("#pagination_urlcount_of").html(obj.pagination.recordCount);
					obj.calculateCellWidth();
					obj.loadingLayer.css({
						"display": "none"
					});
					obj.initializeTableRows();
				}
			}
		});
	}
});