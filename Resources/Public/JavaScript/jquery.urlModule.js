(function($) {
	$.fn.UrlModule = function(opts) {
		var options = $.extend({
			"mode": "",
			"ajaxUrl": ""
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
					last: module.find("#pagination_last")
				},
				currentPage: 0,
				maxPages: 0,
				pageSize: 0,
				recordCount: 0
			};
			var tableContent = $("tbody", module);
			var request = null;

			/* initialize domain filter */
			filter.domain.object = module.find("#domain");
			filter.domain.object.on("filterChange", function(ev, value, isInitial) {
				filter.domain.value = value;
				if(!isInitial) {
					module.trigger("loadUrls");
				}
			}); // must be registered before plugin is called to catch the initial value event
			filter.domain.object.UrlModuleFilterSelect();

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
			filter.language.object.UrlModuleFilterSelect();

			/* initialize scope filter */
			filter.scope.object = module.find("#scope");
			filter.scope.object.on("filterChange", function(ev, value, isInitial) {
				filter.scope.value = value;
				if(!isInitial) {
					module.trigger("loadUrls");
				}
			}); // must be registered before plugin is called to catch the initial value event
			filter.scope.object.UrlModuleFilterSelect();

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
				if(!isInitial) {
					module.trigger("loadUrls");
				}
			});
			pagination.select.UrlModuleFilterSelect();

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
			module.find("#table_head thead th").each(function(thIndex, thElement) {
				thElement = $(thElement);
				if(thElement.hasClass("resizeable")) {
					thElement.on("mousemove", function(ev) {
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
						if(ev.buttons == 1) {
							console.debug("dragging");
						}
					});

					thElement.on("mousedown", function(ev) {
						ev.preventDefault();
						ev.stopImmediatePropagation();
						var dimensions = {
							x1: thElement.offset().left,
							x2: thElement.offset().left + thElement.width(),
							y1: thElement.offset().top,
							y2: thElement.offset().top + thElement.height()
						}
					})
				}
			});


			module.on('loadUrls', function() {
				if(request != null) {
					request.abort();
				}
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
						}
					}
				});
			});

			module.trigger('loadUrls');
		});
	}

	$.fn.UrlModuleFilterSelect = function(opts) {
		var options = $.extend({

			}, opts ? opts : {});

		this.each(function(filterIndex, filterElement) {
			var filter = $(filterElement);
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
					checked: (checkbox.attr("checked")) ? true : false
				};
				checkbox.on("click", function() {
					var newValue;
					if(checkbox.attr("checked")) {
						newValue = true;
					} else {
						newValue = false;
					}
					if(newValue != checkboxes[checkboxIndex].value) {
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
				var value = [];
				for(var i=0; i<checkboxes.length; i++) {
					if(checkboxes[i].checked) {
						value[value.length] = checkboxes[i].object.val();
					}
				}
				filter.trigger("filterChange", [value, true]);
			});

		});
	}
})(jQuery);

