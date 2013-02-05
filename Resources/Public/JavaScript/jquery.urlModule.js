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
			var contextMenuRequest = null;
			var tableMaxWidth = 0;
			var scrollBarWidth = 0;

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
					"height": w.height() - tableBody.offset().top - (tableBody.outerHeight(true) - tableBody.innerHeight()) - (tableOuter.outerHeight(true) - tableOuter.innerHeight()) - pagination.outerHeight(true)
				});
				module.trigger("initializeCellWidth");
			});
			module.trigger("bodyResize");
			$(window).on("resize", function() {
				module.trigger("bodyResize");
			});

			/* initialize table header */
			var selectedHeaderCell = null;
			var lastDraggedPosition = 0;
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
						/* shrink */
						if(ev.pageX < lastDraggedPosition) {
							selectedHeaderCell.css({
								"width": selectedHeaderCell.width() + (ev.pageX - lastDraggedPosition)
							});
							resizeableTableHeaders[selectedHeaderCell.attr("data-cellClassName")].resizer.css({
								"width": resizeableTableHeaders[selectedHeaderCell.attr("data-cellClassName")] + (ev.pageX - lastDraggedPosition)
							});
							var nextResizeable = selectedHeaderCell.find("+ .resizeable");
							if(nextResizeable.length > 0) {
								var cellClass = nextResizeable.attr("data-cellClassName");
								if(cellClass && cellClass.length > 0) {
									if(resizeableTableHeaders[cellClass].resizer.length > 0) {
										nextResizeable.css({
											"width": nextResizeable.width() - (ev.pageX - lastDraggedPosition)
										});
										resizeableTableHeaders[cellClass].resizer.css({
											"width": resizeableTableHeaders[cellClass].resizer.width() - (ev.pageX - lastDraggedPosition)
										});
									}
								}
							}
							console.log("shrink");
						} else {
							if(module.find("#table_body table").outerWidth() < tableMaxWidth - scrollBarWidth) {
								var newWidth = ev.pageX - selectedHeaderCell.position().left
								selectedHeaderCell.css({
									"width": newWidth - (selectedHeaderCell.innerWidth() - selectedHeaderCell.width())
								});
								resizeableTableHeaders[selectedHeaderCell.attr("data-cellClassName")].resizer.css({
									"width": newWidth
								});
								options.settings.columnWidth[selectedHeaderCell.attr("data-cellClassName")].customWidth = newWidth;
							} else {
								var nextResizeable = selectedHeaderCell.find("+ .resizeable");
								if(nextResizeable.length > 0) {
									var cellClass = nextResizeable.attr("data-cellClassName");
									if(cellClass && cellClass.length > 0) {
										if(resizeableTableHeaders[cellClass].resizer.length > 0) {
											nextResizeable.css({
												"width": nextResizeable.width() - (ev.pageX - selectedHeaderCell.position().left - selectedHeaderCell.width()) - (nextResizeable.innerWidth() - nextResizeable.width())
											});
											resizeableTableHeaders[cellClass].resizer.css({
												"width": nextResizeable.width() - (ev.pageX - selectedHeaderCell.position().left - selectedHeaderCell.width())
											});
										}
									}
								}
							}
							console.log("grow");
						}
						lastDraggedPosition = ev.pageX;
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
							lastDraggedPosition = ev.pageX;
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
								tx_naworkuri_web_naworkuritxnaworkuriuri: {
									key: "columnWidth." + selectedHeaderCell.attr("data-cellClassName"),
									value: selectedHeaderCell.width()
								}
							}
						});
						selectedHeaderCell = null;
					}
					lastDraggedPosition = 0;
				});
			});

			/* initialize cell width */
			module.on("initializeCellWidth", function() {
				tableMaxWidth = $("#table_outer").width();
				/* check if we have a scroll bar and set filler cell's width */
				if(module.find("#table_body").height() < module.find("#table_body table").height()) {
					var scrollbarFilllerCell = module.find("#table_head .scrollbarFiller");
					/* find scrollbar width */
					var testElement = $("<div style=\"position: absolute; top: -999; overflow: scroll; height: 100px; width: 100px;\"><div></div></div>");
					$(document.body).append(testElement);
					scrollBarWidth = testElement.width() - testElement.find("div").width();
					scrollbarFilllerCell.css({
						"width": scrollBarWidth - (scrollbarFilllerCell.outerWidth() - scrollbarFilllerCell.innerWidth())
					});
					testElement.remove();
				} else {
					module.find("#table_head .scrollbarFiller").css({
						"width": 0
					});
				}
				var shrinkColumns = module.find("#table_body table").width() > module.find("#table_body").width();
				module.find("#table_head table").css({
					"width": "auto"
				});
				module.find("#table_body table").css({
					"width": "auto"
				});
				module.find("#table_head .minwidth").each(function(columnIndex, column) {
					column = $(column);
					var cellClass = column.attr("data-cellClassName");
					if(cellClass && cellClass.length > 0) {
						var headerWidth = column.innerWidth();
						var cellWidth = 0;
						module.find("#table_body tbody ." + cellClass).each(function(cellIndex, cell) {
							cellWidth = Math.max(cellWidth, $(cell).innerWidth());
						});
						var columnWidth = Math.max(headerWidth, cellWidth);
						column.css({
							"width": columnWidth - (column.innerWidth() - column.width())
						});
						module.find("#table_body thead ." + cellClass).css({
							"width": columnWidth
						});
					}
				});
				if(shrinkColumns) {
					module.find("#table_head table").css({
						"width":"100%"
					});
				}

				module.find("#table_head .dynamic").css({
					"width": "auto"
				})
				module.find("#table_head .dynamic").each(function(columnIndex, column) {
					column = $(column);
					var cellClass = column.attr("data-cellClassName");
					if(cellClass && cellClass.length > 0) {
						var headerWidth = column.innerWidth();
						var columnWidth = 0;
						if(!shrinkColumns) {
							var cellWidth = 0;
							module.find("#table_body tbody ." + cellClass).each(function(cellIndex, cell) {
								cellWidth = Math.max(cellWidth, $(cell).innerWidth());
							});
							columnWidth = Math.max(columnWidth, cellWidth);
						}
						columnWidth = Math.max(headerWidth, columnWidth);
						column.css({
							"width": columnWidth - (column.innerWidth() - column.width())
						});
						module.find("#table_body thead ." + cellClass).css({
							"width": columnWidth
						});
					}
				});
				if(shrinkColumns) {
					module.find("#table_body table").css({
						"width": "100%"
					});
				}
				/* if there are resizable columns, try to apply the saved width */
				module.find("#table_head .resizeable").each(function(columnIndex, column) {
					column = $(column);
					var currentWidth = column.width();
					if(options.settings.columnWidth[column.attr("data-cellClassName")] && options.settings.columnWidth[column.attr("data-cellClassName")].length > 0) {
						if(module.find("#table_body thead ." + column.attr("data-cellClassName")).length > 0) {
							column.css({
								"width": options.settings.columnWidth[column.attr("data-cellClassName")] - (column.innerWidth() - column.width())
							});
							/* if the table is too wide, reset the css to the value before; else set the resizer th to the new width */
							if(module.find("#table_body table").width() > module.find("#table_body").width()) {
								column.css({
									"width": currentWidth - (column.innerWidth() - column.width())
								});
							} else {
								module.find("#table_body thead ." + column.attr("data-cellClassName")).css({
									"width": options.settings.columnWidth[column.attr("data-cellClassName")]
								});
							}
						}
					}
				});
			});

			module.on("initializeTableRows", function() {
				tableContent.find("tr").each(function(rowIndex, row) {
					row = $(row);
					row.on("contextmenu", function(ev) {
						ev.preventDefault();
						ev.stopImmediatePropagation();
						if(contextMenuRequest != null) {
							contextMenuRequest.abort();
						}
						$.ajax({
							url: row.attr("data-contextmenuurl"),
							dataType: "html",
							success: function(response, status, request) {
								if($.trim(response).length > 0) {
									if($("#tx_naworkuri_contextMenu").length > 0) {
										$("#tx_naworkuri_contextMenu").remove();
									}
									$("#table_body").append($(response));
									$("#tx_naworkuri_contextMenu").css({
										display: "blocK",
										left: ev.pageX,
										top: ev.pageY - $("#table_body").position().top
									});
									module.trigger("initializeContextMenu");
								}
							}
						})
					});
					row.on("click", function(ev) {
						ev.preventDefault();
						ev.stopImmediatePropagation();
						module.trigger("closeContextMenu");
					});
				})
			});

			module.on("closeContextMenu", function(ev) {
				if($("#tx_naworkuri_contextMenu").length > 0) {
					$("#tx_naworkuri_contextMenu").remove();
				}
			});

			module.on("initializeContextMenu", function() {
				var cm = $("#tx_naworkuri_contextMenu");
				cm.find(".show").click(function(ev) {
					var popup = window.open(window.location.protocol + "//" + (window.location.host ? window.location.host : window.location.hostname) + "/" + $(this).attr("data-path"), "tx_naworkuri_preview");
					popup.focus();
					module.trigger("closeContextMenu");
				});
				cm.find(".lock").click(function(ev) {
					if(request != null && request.abort) {
						request.abort();
					}
					request = $.ajax({
						url: $(this).attr("data-ajaxurl"),
						dataType: "html",
						success: function(response, status, request) {
							if($.trim(response).length == 0) {
								module.trigger("loadUrls");
							}
						}
					});
					module.trigger("closeContextMenu");
				});
				cm.find(".unlock").click(function(ev) {
					if(request != null && request.abort) {
						request.abort();
					}
					request = $.ajax({
						url: $(this).attr("data-ajaxurl"),
						dataType: "html",
						success: function(response, status, request) {
							if($.trim(response).length == 0) {
								module.trigger("loadUrls");
							}
						}
					});
					module.trigger("closeContextMenu");
				});
				cm.find(".edit").click(function(ev) {
					window.location.href = "alt_doc.php?returnUrl=" + encodeURIComponent(window.location.href) + "&edit[tx_naworkuri_uri][" + $(this).attr("data-uid") + "]=edit";
				});
				cm.find(".delete").click(function(ev) {
					if(window.confirm("Do you really want to delete the url \"" + $(this).attr("data-path") + "\"?")) {
						if(request != null && request.abort) {
							request.abort();
						}
						request = $.ajax({
							url: $(this).attr("data-ajaxurl"),
							dataType: "html",
							success: function(response, status, request) {
								if($.trim(response).length == 0) {
									module.trigger("loadUrls");
								}
							}
						});
					}
					module.trigger("closeContextMenu");
				});
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
						tx_naworkuri_web_naworkuritxnaworkuriuri: {
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
							module.trigger("initializeTableRows");
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

