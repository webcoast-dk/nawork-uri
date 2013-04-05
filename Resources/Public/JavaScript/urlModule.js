if(typeof NaworkUri != 'object') {
	NaworkUri = {};
}
NaworkUri.UrlModule = new Class({
	Implements: Options,
	
	options: {
		urls: {
			ajax: "",
			settings: ""
		},
		settings:{},
		messages: {
			error: "",
			loading: ""
		}
	},
	
	filter: {
		domain: {
			object: null,
			value: 0
		},
		types: {
			object: null,
			value: []
		},
		language: {
			object: null,
			value: 0
		},
		scope: {
			object: null,
			value: ""
		},
		path: {
			object: null,
			value: ""
		}
	},
	
	/* several object and options that are used within the script */
	requests: {
		ajax: null,
		settings: null,
		contextMenu: null
	},
	
	/* pagination controls and properties */
	pagination: {
		select: null,
		controls: {
			first: null,
			previous: null,
			next: null,
			last: null,
			reload: null
		},
		currentPage: 0,
		maxPages: 0,
		pageSize: 0,
		recordCount: 0
	},
	
	/* layer shown while urls are loaded */
	loadingLayer: null,
	scrollBarWidth: 0,
	lastDraggedPosition: 0,
	selectedHeaderCell: null,
	contextMenuTimeout: null,
	
	initialize: function(selector, options) {
		this.setOptions(options);
		this.jqueryObject = jQuery(selector);
		this.loadingLayer = jQuery('<div class="tx_naworkuri_loadingLayer"><p>' + this.options.messages.loading + '</p></div>');
		
		this.initializeFilters();
		this.initializePagination();
		this.initializeTableHeader();
		this.initializeTableRows();
		this.resize();
		
		var obj = this;
		jQuery(window).on("resize", function() {
			obj.resize();
		});
		
		
		jQuery(window).click(obj.closeContextMenu);
		
		this.loadUrls();
	},
	
	initializeFilters: function() {
		var obj = this;
		/* initialize domain filter */
		obj.filter.domain.object = obj.jqueryObject.find("#domain");
		obj.filter.domain.object.on("filterChange", function(ev, value, isInitial) {
			obj.filter.domain.value = value;
			if(!isInitial) {
				obj.loadUrls();
			}
		}); // must be registered before plugin is called to catch the initial value event
		obj.filter.domain.object.UrlModuleFilterSelect({
			defaultValue: (obj.options.settings.filter.domain || 0)
		});

		/* initialize the types filter */
		obj.filter.types.object = obj.jqueryObject.find("#type");
		obj.filter.types.object.on("filterChange", function(ev, value, isInitial) {
			obj.filter.types.value = value;
			if(!isInitial) {
				obj.loadUrls();
			}
		});
		obj.filter.types.object.UrlModuleFilterCheckbox();

		/* initialize language filter */
		obj.filter.language.object = obj.jqueryObject.find("#language");
		obj.filter.language.object.on("filterChange", function(ev, value, isInitial) {
			obj.filter.language.value = value;
			if(!isInitial) {
				obj.loadUrls();
			}
		}); // must be registered before plugin is called to catch the initial value event
		obj.filter.language.object.UrlModuleFilterSelect({
			defaultValue: (obj.options.settings.filter.language || 0)
		});

		/* initialize scope filter */
		obj.filter.scope.object = obj.jqueryObject.find("#scope");
		obj.filter.scope.object.on("filterChange", function(ev, value, isInitial) {
			obj.filter.scope.value = value;
			if(!isInitial) {
				obj.loadUrls();
			}
		}); // must be registered before plugin is called to catch the initial value event
		obj.filter.scope.object.UrlModuleFilterSelect({
			defaultValue: (obj.options.settings.filter.scope || 0)
		});

		/* initialize path filter */
		obj.filter.path.object = obj.jqueryObject.find("#path");
		obj.filter.path.object.on("filterChange", function(ev, value, isInitial) {
			obj.filter.path.value = value;
			if(!isInitial) {
				obj.loadUrls();
			}
		}); // must be registered before plugin is called to catch the initial vaue event
		obj.filter.path.object.UrlModuleFilterInput();
	},
	
	initializePagination: function() {
		var obj = this;
		obj.pagination.select = obj.jqueryObject.find("#pagination_pageSize");
		obj.pagination.controls.first = obj.jqueryObject.find("#pagination_first");
		obj.pagination.controls.previous = obj.jqueryObject.find("#pagination_previous");
		obj.pagination.controls.next=  obj.jqueryObject.find("#pagination_next");
		obj.pagination.controls.last = obj.jqueryObject.find("#pagination_last");
		obj.pagination.controls.reload = obj.jqueryObject.find("#pagination_reload");
		
		obj.pagination.controls.first.click(function(ev) {
			ev.preventDefault();
			ev.stopImmediatePropagation();
			if(obj.pagination.currentPage > 0) {
				obj.pagination.currentPage = 0;
				obj.loadUrls();
			}
		});
		obj.pagination.controls.previous.click(function(ev) {
			ev.preventDefault();
			ev.stopImmediatePropagation();
			if(obj.pagination.currentPage > 0) {
				--obj.pagination.currentPage;
				obj.loadUrls();
			}
		});
		obj.pagination.controls.next.click(function(ev) {
			ev.preventDefault();
			ev.stopImmediatePropagation();
			if(obj.pagination.currentPage < obj.pagination.maxPages) {
				++obj.pagination.currentPage;
				obj.loadUrls();
			}
		});
		obj.pagination.controls.last.click(function(ev) {
			ev.preventDefault();
			ev.stopImmediatePropagation();
			if(obj.pagination.currentPage < obj.pagination.maxPages) {
				obj.pagination.currentPage = obj.pagination.maxPages - 1;
				obj.loadUrls();
			}
		});
		obj.pagination.select.on("filterChange", function(ev, value, isInitial) {
			obj.pagination.pageSize = value;
			obj.pagination.maxPages = Math.ceil(obj.pagination.recordCount / obj.pagination.pageSize);
			obj.pagination.currentPage = Math.min(obj.pagination.currentPage, Math.max(0, obj.pagination.maxPages - 1));
			if(!isInitial) {
				obj.loadUrls();
			}
		});
		obj.pagination.select.UrlModuleFilterSelect({
			defaultValue: (obj.options.settings.filter.pageSize || 0)
		});

		obj.pagination.controls.reload.on("click", function(ev) {
			obj.loadUrls();
		});
	},
	
	initializeTableHeader: function() {
		var obj = this;
		obj.jqueryObject.find("#table_head thead th").each(function(thIndex, thElement) {
			thElement = jQuery(thElement);
			thElement.on("mousemove", function(ev) {
				if(thElement.hasClass("resizeable")) {
					var dimensions = {
						x1: thElement.offset().left,
						x2: thElement.offset().left + thElement.width(),
						y1: thElement.offset().top,
						y2: thElement.offset().top + thElement.height()
					}
					if(ev.pageX > dimensions.x2 - 5) {
						thElement.css({
							"cursor": "ew-resize"
						});
					} else {
						thElement.css({
							"cursor": "auto"
						});
					}
				}
				if(obj.selectedHeaderCell != null && ev.buttons == 1) {
					/*
					 * works in both directions, because (ev.pageX - lastDraggedPosition) is negativ for shrinking and positiv for growing
					 */
					var selectedHeaderCellResizer = obj.jqueryObject.find("#table_body thead ." + obj.selectedHeaderCell.attr("data-cellClassName"));
					var nextHeaderCell = obj.selectedHeaderCell.next(".resizeable");
					var nextHeaderCellResizer = obj.jqueryObject.find("#table_body thead ." + nextHeaderCell.attr("data-cellClassName"));
					var cellDiff = (ev.pageX - obj.lastDraggedPosition);
					var selectedHeaderCellWidth = obj.selectedHeaderCell.width() + cellDiff;
					var nextHeaderCellWidth = nextHeaderCell.width() - cellDiff;
					if(nextHeaderCell.length > 0) {
						obj.selectedHeaderCell.css({
							"width": selectedHeaderCellWidth
						});
						selectedHeaderCellResizer.css({
							"width": selectedHeaderCellWidth
						});
						nextHeaderCell.css({
							"width": nextHeaderCellWidth
						});
						nextHeaderCellResizer.css({
							"width": nextHeaderCellWidth
						});
					}
					obj.lastDraggedPosition = ev.pageX;
				}
			});

			thElement.on("mousedown", function(ev) {
				ev.preventDefault();
				ev.stopImmediatePropagation();
				if(thElement.hasClass("resizeable") && thElement.next(".resizeable").length > 0) { // only do resizing if we have a next resizeable column
					var dimensions = {
						x1: thElement.offset().left,
						x2: thElement.offset().left + thElement.width(),
						y1: thElement.offset().top,
						y2: thElement.offset().top + thElement.height()
					}
					if(ev.pageX > dimensions.x2 - 5) {
						obj.selectedHeaderCell = thElement;
						obj.lastDraggedPosition = ev.pageX;
					}
				}
			});

			thElement.on("mouseup", function(ev) {
				if(obj.selectedHeaderCell != null) {
					obj.options.settings.columnWidth[obj.selectedHeaderCell.attr("data-cellClassName")] = obj.selectedHeaderCell.width();
					obj.options.settings.columnWidth[obj.selectedHeaderCell.next(".resizeable").attr("data-cellClassName")] = obj.selectedHeaderCell.next(".resizeable").width();
					obj.updateUserSettings();
				}
				obj.lastDraggedPosition = 0;
			});
		});
	},
	
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
					tx_naworkuri_web_naworkuritxnaworkuriuri: {
						key: "columnWidth." + obj.selectedHeaderCell.attr("data-cellClassName"),
						value: obj.selectedHeaderCell.width()
					}
				},
				success: function() {
					obj.requests.setting = jQuery.ajax({
						url: obj.options.urls.settings,
						dataType: "json",
						data: {
							tx_naworkuri_web_naworkuritxnaworkuriuri: {
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
	
	initializeTableRows: function() {
		var obj = this;
		this.jqueryObject.find("#table_body tbody tr").each(function(rowIndex, row) {
			row = jQuery(row);
			row.on("contextmenu", function(ev) {
				ev.preventDefault();
				ev.stopImmediatePropagation();
				obj.loadContextMenu(row, ev);
			});
		});
	},
	
	loadContextMenu: function(row, ev) {
		var obj = this;
		if(obj.requests.contextMenu != null) {
			obj.requests.contextMenu.abort();
		}
		obj.requests.contextMenu = jQuery.ajax({
			url: row.attr("data-contextmenuurl"),
			dataType: "html",
			success: function(response, status, request) {
				if(jQuery.trim(response).length > 0) {
					obj.openContextMenu(response, ev);
				}
			}
		})
	},
	
	openContextMenu: function(response, ev) {
		var obj = this;
		
		if(jQuery("#tx_naworkuri_contextMenu").length > 0) {
			jQuery("#tx_naworkuri_contextMenu").remove();
		}
		jQuery("#table_body").append(jQuery(response));
		var cm = jQuery("#tx_naworkuri_contextMenu");
		cm.css({
			display: "block",
			left: ev.pageX - 10,
			top: ev.pageY - this.jqueryObject.find("#table_body").position().top
		});
		
		cm.find(".show").click(function(ev) {
			var popup = window.open(window.location.protocol + "//" + (window.location.host ? window.location.host : window.location.hostname) + "/" + jQuery(this).attr("data-path"), "tx_naworkuri_preview");
			popup.focus();
			obj.closeContextMenu();
		});
		cm.find(".lock").click(function(ev) {
			if(obj.requests.ajax != null && obj.requests.ajax.abort) {
				obj.requests.ajax.abort();
			}
			obj.requests.ajax = jQuery.ajax({
				url: jQuery(this).attr("data-ajaxurl"),
				dataType: "html",
				success: function(response, status, request) {
					if(jQuery.trim(response).length == 0) {
						obj.loadUrls();
					}
				}
			});
			obj.closeContextMenu();
		});
		cm.find(".unlock").click(function(ev) {
			if(request != null && request.abort) {
				request.abort();
			}
			request = jQuery.ajax({
				url: jQuery(this).attr("data-ajaxurl"),
				dataType: "html",
				success: function(response, status, request) {
					if(jQuery.trim(response).length == 0) {
						obj.loadUrls();
					}
				}
			});
			obj.closeContextMenu();
		});
		cm.find(".edit").click(function(ev) {
			window.location.href = "alt_doc.php?returnUrl=" + encodeURIComponent(window.location.href) + "&edit[tx_naworkuri_uri][" + jQuery(this).attr("data-uid") + "]=edit";
		});
		cm.find(".delete").click(function(ev) {
			if(window.confirm("Do you really want to delete the url \"" + jQuery(this).attr("data-path") + "\"?")) {
				if(obj.request.ajax != null && obj.request.ajax.abort) {
					obj.request.ajax.abort();
				}
				request = jQuery.ajax({
					url: jQuery(this).attr("data-ajaxurl"),
					dataType: "html",
					success: function(response, status, request) {
						if(jQuery.trim(response).length == 0) {
							obj.loadUrls();
						}
					}
				});
			}
			obj.closeContextMenu();
		});
		cm.on("mouseover", function() {
			if(obj.contextMenuTimeout != null) {
				clearTimeout(obj.contextMenuTimeout);
			}
		});
		cm.on("mouseout", function() {
			obj.contextMenuTimeout = setTimeout(obj.closeContextMenu, 500);
		});
		if(this.contextMenuTimeout != null) {
			clearTimeout(this.contextMenuTimeout);
		}
		this.contextMenuTimeout = setTimeout(this.closeContextMenu, 2000);
	},
	
	closeContextMenu: function() {
		if(jQuery("#tx_naworkuri_contextMenu").length > 0) {
			jQuery("#tx_naworkuri_contextMenu").remove();
		}
	},
	
	resize: function() {
		var w = jQuery(window);
		var tableOuter = this.jqueryObject.find("#table_outer");
		var tableBody = this.jqueryObject.find("#table_body");
		var pagination = this.jqueryObject.find("#pagination");
		tableBody.css({
			"height": w.height() - tableBody.offset().top - (tableBody.outerHeight(true) - tableBody.innerHeight()) - (tableOuter.outerHeight(true) - tableOuter.innerHeight()) - pagination.outerHeight(true)
		});
		this.calculateCellWidth();
	},
	
	calculateCellWidth: function() {
		var obj = this;
		/* check if we have a scroll bar and set filler cell's width */
		if(obj.jqueryObject.find("#table_body").height() < obj.jqueryObject.find("#table_body table").height()) {
			/* find scrollbar width */
			var testElement = jQuery("<div style=\"position: absolute; top: -999; overflow: scroll; height: 100px; width: 100px;\"><div></div></div>");
			jQuery(document.body).append(testElement);
			obj.scrollBarWidth = testElement.width() - testElement.find("div").width();
			testElement.remove();
		} else {
			obj.jqueryObject.find("#table_head .scrollbarFiller").css({
				"width": 0
			});
		}
		obj.jqueryObject.find("#table_body table").css({
			"width":"auto"
		});
		obj.jqueryObject.find("#table_head .minwidth:not(.fixedwidth)").each(function(columnIndex, column) {
			column = jQuery(column);
			var cellClass = column.attr("data-cellClassName");
			if(cellClass && cellClass.length > 0) {
				var headerWidth = column.width();
				var cellWidth = 0;
				obj.jqueryObject.find("#table_body tbody ." + cellClass).each(function(cellIndex, cell) {
					cellWidth = Math.max(cellWidth, jQuery(cell).width());
				});
				var columnWidth = Math.max(headerWidth, cellWidth);
				column.css({
					"width": columnWidth
				});
				obj.jqueryObject.find("#table_body thead ." + cellClass).css({
					"width": columnWidth
				});
			}
		});
		obj.jqueryObject.find("#table_body table").css({
			"width": "100%"
		});
		
		/* if there are resizable columns, try to apply the saved width */
		var resizeableColumnWidthDifference = 0;
		var resizeableColumnWidthTotal = 0;
		var resizeableColumnWidthFromOptions = 0;
		obj.jqueryObject.find("#table_head .resizeable").each(function(columnIndex, column) {
			column = jQuery(column);
			if(obj.jqueryObject.find("#table_body thead ." + column.attr("data-cellClassName")).length > 0) {
				if(obj.options.settings.columnWidth[column.attr("data-cellClassName")] && parseInt(obj.options.settings.columnWidth[column.attr("data-cellClassName")]) > 0) {
					resizeableColumnWidthDifference += (parseInt(obj.options.settings.columnWidth[column.attr("data-cellClassName")]) - column.width());
					resizeableColumnWidthFromOptions += parseInt(obj.options.settings.columnWidth[column.attr("data-cellClassName")]);
				} else {
					resizeableColumnWidthFromOptions += parseInt(column.width());
				}
				resizeableColumnWidthTotal += parseInt(obj.options.settings.columnWidth[column.attr("data-cellClassName")]) || parseInt(column.width());
			}
		});
		
		obj.jqueryObject.find("#table_head .dynamic").each(function(columnIndex, column) {
			column = jQuery(column);
			var cellClass = column.attr("data-cellClassName");
			if(cellClass && cellClass.length > 0) {
				var headerWidth = column.width();
				var columnWidth = obj.jqueryObject.find("#table_body thead ." + cellClass).width();
				columnWidth = Math.max(headerWidth, columnWidth);
				if(obj.options.settings.columnWidth[column.attr("data-cellClassName")] && parseInt(obj.options.settings.columnWidth[column.attr("data-cellClassName")]) > 0) {
					columnWidth = parseInt(obj.options.settings.columnWidth[column.attr("data-cellClassName")]);
				}
				column.css({
					"width": columnWidth
				});
				obj.jqueryObject.find("#table_body thead ." + cellClass).css({
					"width": columnWidth
				});
			}
		});
		
		var tableWidth = obj.jqueryObject.find("#table_body table").width();
		var tableOuterWidth = obj.jqueryObject.find("#table_body").width() - obj.scrollBarWidth - 1;
		if(tableWidth > tableOuterWidth || resizeableColumnWidthFromOptions != resizeableColumnWidthTotal) {
			var diff = tableWidth - tableOuterWidth;
			obj.jqueryObject.find("#table_head .resizeable").each(function(columnIndex, column) {
				column = jQuery(column);
				var percentage = 0;
				var columnWidth = 0;
				if(obj.options.settings.columnWidth[column.attr("data-cellClassName")] && parseInt(obj.options.settings.columnWidth[column.attr("data-cellClassName")]) > 0) {
					percentage = parseInt(obj.options.settings.columnWidth[column.attr("data-cellClassName")]) / resizeableColumnWidthFromOptions;
				} else {
					percentage = column.width() / resizeableColumnWidthFromOptions;
				}
				columnWidth = Math.floor((resizeableColumnWidthTotal - diff) * percentage);
				column.css({
					"width": columnWidth
				});
				obj.jqueryObject.find("#table_body thead ." + column.attr("data-cellClassName")).css({
					"width": columnWidth
				});
			});
		}
		obj.jqueryObject.find("#table_head table").css({
			"width": obj.jqueryObject.find("#table_body table").width()
		});
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
				tx_naworkuri_web_naworkuritxnaworkuriuri: {
					domain: this.filter.domain.value,
					types: this.filter.types.value,
					language: this.filter.language.value,
					scope: this.filter.scope.value,
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