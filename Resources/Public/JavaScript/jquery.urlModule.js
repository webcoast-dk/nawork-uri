(function($) {
	$.fn.UrlModule = function(opts) {
		var options = $.extend({
			"mode": "",
			"ajaxUrl": "",
			"settings": {},
			"errorMessage": "",
			"loadingMessage": ""
		}, opts ? opts : {});
		this.each(function(moduleIndex, moduleElement) {
			var module = $(moduleElement);
			var filter = {
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
			};
			var pagination = {
				select: module.find("#pagination_pageSize"),
				controls: {
					first: module.find("#pagination_first"),
					previous: module.find("#pagination_previous"),
					next: module.find("#pagination_next"),
					last: module.find("#pagination_last"),
					reload: module.find("#pagination_reload")
				},
				currentPage: 0,
				maxPages: 0,
				pageSize: 0,
				recordCount: 0
			};
			var loadingLayer = $('<div class="tx_naworkuri_loadingLayer"><p>' + options.loadingMessage + '</p></div>');
			var tableContent = $("#table_body tbody", module);
			$("#table_body").append(loadingLayer);
			var request = null;
			var settingsRequest = null;


			/* initialize domain filter */
			filter.domain.object = module.find("#domain");
			filter.domain.object.on("filterChange", function(ev, value, isInitial) {
				filter.domain.value = value;
				if(!isInitial) {
					module.trigger("loadUrls");
				}
			}); // must be registered before plugin is called to catch the initial value event
			filter.domain.object.UrlModuleFilterSelect({
				defaultValue: (options.settings.filter.domain ? options.settings.filter.domain : 0)
			});

			/* initialize the types filter */
			filter.types.object = module.find("#type");
			filter.types.object.on("filterChange", function(ev, value, isInitial) {
				filter.types.value = value;
				if(!isInitial) {
					module.trigger("loadUrls");
				}
			});
			filter.types.object.UrlModuleFilterCheckbox();

			/* initialize language filter */
			filter.language.object = module.find("#language");
			filter.language.object.on("filterChange", function(ev, value, isInitial) {
				filter.language.value = value;
				if(!isInitial) {
					module.trigger("loadUrls");
				}
			}); // must be registered before plugin is called to catch the initial value event
			filter.language.object.UrlModuleFilterSelect({
				defaultValue: (options.settings.filter.language ? options.settings.filter.language : 0)
			});

			/* initialize scope filter */
			filter.scope.object = module.find("#scope");
			filter.scope.object.on("filterChange", function(ev, value, isInitial) {
				filter.scope.value = value;
				if(!isInitial) {
					module.trigger("loadUrls");
				}
			}); // must be registered before plugin is called to catch the initial value event
			filter.scope.object.UrlModuleFilterSelect({
				defaultValue: (options.settings.filter.scope ? options.settings.filter.scope : 0)
			});

			/* initialize path filter */
			filter.path.object = module.find("#path");
			filter.path.object.on("filterChange", function(ev, value, isInitial) {
				filter.path.value = value;
				if(!isInitial) {
					module.trigger("loadUrls");
				}
			}); // must be registered before plugin is called to catch the initial vaue event
			filter.path.object.UrlModuleFilterInput();

			pagination.controls.first.click(function(ev) {
				ev.preventDefault();
				ev.stopImmediatePropagation();
				if(pagination.currentPage > 0) {
					pagination.currentPage = 0;
					module.trigger("loadUrls");
				}
			});
			pagination.controls.previous.click(function(ev) {
				ev.preventDefault();
				ev.stopImmediatePropagation();
				if(pagination.currentPage > 0) {
					--pagination.currentPage;
					module.trigger("loadUrls");
				}
			});
			pagination.controls.next.click(function(ev) {
				ev.preventDefault();
				ev.stopImmediatePropagation();
				if(pagination.currentPage < pagination.maxPages) {
					++pagination.currentPage;
					module.trigger("loadUrls");
				}
			});
			pagination.controls.last.click(function(ev) {
				ev.preventDefault();
				ev.stopImmediatePropagation();
				if(pagination.currentPage < pagination.maxPages) {
					pagination.currentPage = pagination.maxPages - 1;
					module.trigger("loadUrls");
				}
			});
			pagination.select.on("filterChange", function(ev, value, isInitial) {
				pagination.pageSize = value;
				pagination.maxPages = Math.ceil(pagination.recordCount / pagination.pageSize);
				pagination.currentPage = Math.min(pagination.currentPage, Math.max(0, pagination.maxPages - 1));
				if(!isInitial) {
					module.trigger("loadUrls");
				}
			});
			pagination.select.UrlModuleFilterSelect({
				defaultValue: (options.settings.filter.pageSize ? options.settings.filter.pageSize : 0)
			});

			pagination.controls.reload.on("click", function(ev) {
				module.trigger("loadUrls");
			});

			module.on("bodyResize", function() {
				var w = $(window);
				var tableOuter = module.find("#table_outer");
				var tableBody = module.find("#table_body");
				var pagination = module.find("#pagination");
				tableBody.css({
					"height": w.height() - tableBody.offset().top - (tableBody.outerHeight(true) - tableBody.height()) - pagination.outerHeight(true)
				});
			});
			module.trigger("bodyResize");
			$(window).on("resize", function() {
				module.trigger("bodyResize");
			})

			/* initialize table header */
			var selectedHeaderCell = null;
			var dragStart = 0;
			var resizeableTableHeaders = {

			};
			module.find("#table_head thead th").each(function(thIndex, thElement) {
				thElement = $(thElement);
				if(!thElement.hasClass("fixedwidth")) {
					resizeableTableHeaders[thElement.attr("data-cellClassName")] = {
						element: thElement,
						resizer: module.find("#table_body thead th." + thElement.attr("data-cellClassName")),
						minWidth: 0
					}
				}
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
					if(selectedHeaderCell != null && ev.buttons == 1) {
						if((ev.pageX - selectedHeaderCell.position().left) >= resizeableTableHeaders[selectedHeaderCell.attr("data-cellClassName")].minWidth) {
							selectedHeaderCell.css({
								"width": ev.pageX - selectedHeaderCell.position().left
							});
							resizeableTableHeaders[selectedHeaderCell.attr("data-cellClassName")].resizer.css({
								"width": ev.pageX - selectedHeaderCell.position().left
							});
						}
					}
				});

				thElement.on("mousedown", function(ev) {
					ev.preventDefault();
					ev.stopImmediatePropagation();
					if(thElement.hasClass("resizeable")) {
						var dimensions = {
							x1: thElement.offset().left,
							x2: thElement.offset().left + thElement.width(),
							y1: thElement.offset().top,
							y2: thElement.offset().top + thElement.height()
						}
						if(ev.pageX > dimensions.x2 - 5) {
							selectedHeaderCell = thElement;
							dragStart = ev.pageX;
						}
					}
				});

				thElement.on("mouseup", function(ev) {
					if(selectedHeaderCell != null) {
						if(settingsRequest != null) {
							settingsRequest.abort();
						}
						settingsRequest = $.ajax({
							url: options.settingsUrl,
							dataType: "json",
							data: {
								tx_naworkuri_txnaworkurim1_naworkuriurl: {
									key: "columnWidth." + selectedHeaderCell.attr("data-cellClassName"),
									value: selectedHeaderCell.width()
								}
							}
						});
						selectedHeaderCell = null;
					}
					dragStart = 0;
				});
			});

			/* initialize cell width */
			module.on("initializeCellWidth", function() {
				/* check if we have a scroll bar and set filler cell's width */
				if(module.find("#table_body").height() < module.find("#table_body table").height()) {
					var scrollbarFilllerCell = module.find("#table_head .scrollbarFiller");
					var diff = module.find("#table_head table").width() - module.find("#table_body table").width() - (scrollbarFilllerCell.outerWidth() - scrollbarFilllerCell.innerWidth());
					scrollbarFilllerCell.css({
						"width": diff
					});
				} else {
					module.find("#table_head .scrollbarFiller").css({
						"width": 0
					});
				}
				for(var header in resizeableTableHeaders) {
					var element = resizeableTableHeaders[header].element;
					var className = $.trim(element.attr("data-cellClassName"));
					var defaultWidth = element.width();
					/* evaluate min width for header */
					element.css({
						"width": 0
					});
					var minWidth = element.width();
					element.css({
						"width": "auto"
					});
					/* evaluate min width for cells */
					var cells = module.find("#table_body tbody td." + className);
					cells.css({
						"width": 0
					});
					cells.each(function(cellIndex, cell) {
						cell = $(cell);
						minWidth = Math.max(minWidth, cell.width());
					});
					cells.css({
						"width":"auto"
					});
					if(element.hasClass("minwidth")) {
						element.css({
							"width": minWidth
						});
						module.find("#table_body thead ." + className).css({
							"width": minWidth
						});
					} else if(element.hasClass("resizeable")) {
						resizeableTableHeaders[header].minWidth = minWidth;
						//						var customWidth = parseInt(resizeableTableHeaders[header].element.attr("data-customWidth"));
						var customWidth = parseInt(options.settings.columnWidth[className]);
						if(customWidth > 0 && customWidth > minWidth) {
							resizeableTableHeaders[header].element.css({
								"width": customWidth
							});
							resizeableTableHeaders[header].resizer.css({
								"width": customWidth
							});
						} else {
							resizeableTableHeaders[header].element.css({
								"width": defaultWidth
							});
							resizeableTableHeaders[header].resizer.css({
								"width": defaultWidth
							});
						}
					}
				}
			});

			module.on('loadUrls', function() {
				if(request != null) {
					request.abort();
				}
				var tableBody = $("#table_body");
				loadingLayer.css({
					top: tableBody.position().top,
					left: tableBody.position().left,
					width: tableBody.outerWidth(true),
					height: tableBody.outerHeight(true),
					display: "block"
				});
				request = $.ajax({
					url: options.ajaxUrl,
					dataType: "json",
					data: {
						tx_naworkuri_txnaworkurim1_naworkuriurl: {
							domain: filter.domain.value,
							types: filter.types.value,
							language: filter.language.value,
							scope: filter.scope.value,
							path: filter.path.value,
							offset: pagination.currentPage * pagination.pageSize,
							limit: pagination.pageSize
						}
					},
					success: function(response, status, request) {
						if(response && response.html && $.trim(response.html).length > 0) {
							tableContent.html(response.html);
							pagination.recordCount = response.count;
							pagination.maxPages = Math.ceil(response.count / pagination.pageSize);
							module.find("#pagination_maxpages").html(pagination.maxPages);
							module.find("#pagination_pagenumber").html(pagination.currentPage + 1);
							module.find("#pagination_urlcount_from").html(pagination.currentPage * pagination.pageSize + 1);
							module.find("#pagination_urlcount_to").html(Math.min((pagination.currentPage + 1 )* pagination.pageSize, pagination.recordCount));
							module.find("#pagination_urlcount_of").html(pagination.recordCount);
							module.trigger("initializeCellWidth");
							loadingLayer.css({
								"display": "none"
							});
						}
					}
				});
			});

			module.trigger('loadUrls');
		});
	}

	$.fn.UrlModuleFilterSelect = function(opts) {
		var options = $.extend({
			defaultValue: 0
		}, opts ? opts : {});

		this.each(function(filterIndex, filterElement) {
			var filter = $(filterElement);
			var currentValue = filter.val();
			if(filter.find("option[value=\"" + options.defaultValue + "\"]").length > 0) {
				filter.find("option").removeAttr("selected");
				filter.find("option[value=\"" + options.defaultValue + "\"]").attr("selected", "selected");
				currentValue = options.defaultValue;
			}

			filter.on("filterInteraction", function() {
				if(filter.val() != currentValue) {
					currentValue = filter.val();
					filter.trigger("filterChange", currentValue);
				}
			});

			filter.on("change", function() {
				filter.trigger("filterInteraction");
			});
			filter.on("click", function() {
				filter.trigger("filterInteraction");
			});
			filter.trigger("filterChange", [currentValue, true]);
		});
	}

	$.fn.UrlModuleFilterCheckbox = function(opts) {
		var options = $.extend({

			}, opts ? opts : {});

		this.each(function(filterIndex, filterElement) {
			var filter = $(filterElement);
			var checkboxes = [];
			filter.find("input[type=\"checkbox\"]").each(function(checkboxIndex, checkboxElement) {
				var checkbox = $(checkboxElement);
				checkboxes[checkboxIndex] = {
					object: checkbox,
					checked: (checkbox.is(":checked")) ? true : false
				};
				checkbox.on("click", function() {
					var newValue;
					if(checkbox.is(":checked")) {
						newValue = true;
					} else {
						newValue = false;
					}
					if(newValue != checkboxes[checkboxIndex].checked) {
						checkboxes[checkboxIndex].checked = newValue;
						var value = [];
						for(var i=0; i<checkboxes.length; i++) {
							if(checkboxes[i].checked) {
								value[value.length] = checkboxes[i].object.val();
							}
						}
						filter.trigger("filterChange", [value]); // value array must be put in an array to be handled as one event parameter
					}
				});
			});
			var value = [];
			for(var i=0; i<checkboxes.length; i++) {
				if(checkboxes[i].checked) {
					value[value.length] = checkboxes[i].object.val();
				}
			}
			filter.trigger("filterChange", [value, true]);
		});
	}

	$.fn.UrlModuleFilterInput = function(opts) {
		var options = $.extend({

			}, opts ? opts : {});

		this.each(function(filterIndex, filter) {
			filter = $(filter);
			var currentValue = filter.val();

			filter.on("filterInteraction", function() {
				if(filter.val() != currentValue) {
					currentValue = filter.val();
					filter.trigger("filterChange", currentValue);
				}
			});

			filter.on("change", function() {
				filter.trigger("filterInteraction");
			});
			filter.on("keyup", function() {
				filter.trigger("filterInteraction");
			});
			filter.trigger("filterChange", [currentValue, true]);
		});
	}
})(jQuery);

