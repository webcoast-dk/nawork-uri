Ext.namespace('tx','tx.naworkuri');

tx.naworkuri.PageUris = Ext.extend(Ext.grid.GridPanel, {
	filterTimeout:  null,
	baseParams: null,

	constructor: function(config) {
		var showColumn = new Ext.grid.ButtonColumn({
			header: '',
			width: 26,
			icon: '/typo3/sysext/t3skin/icons/gfx/zoom.gif',
			isLink: true
		});
		this.data_store = new Ext.data.JsonStore({
			url: config.backPath + 'ajax.php?ajaxID=tx_naworkuri::getpageuris&page=' + config.page,
			root: 'urls',
			totalProperty: 'totalCount',
			fields: [
			{
				name: 'icon',
				type: 'string'
			},
			{
				name: 'uid',
				type: 'int'
			},
			{
				name: 'path',
				type: 'string'
			},
			{
				name: 'domain',
				type: 'string'
			},
			{
				name: 'params',
				type: 'string'
			},
			{
				name: 'flag',
				type: 'string'
			},
			{
				name: 'locked',
				type: 'boolean'
			},
			{
				name: 'redirect_path',
				type: 'string'
			},
			'show'
			],
			baseParams: {
				start: 0,
				limit: 10,
				locked: -1,
				type: 0,
				language: -1
			},
			autoLoad: false
		});
		this.languageFilter = new Ext.form.ComboBox({
			id: 'filterLanguage',
			store: new Ext.data.JsonStore({
				url: config.backPath + 'ajax.php?ajaxID=tx_naworkuri::getlanguages',
				root: 'languages',
				totalProperty: 'totalCount',
				fields: [
				'uid',
				'flag',
				'label'
				],
				autoLoad: true
			}),
			editable: false,
			value: 0,
			valueField: 'uid',
			displayField: 'label',
			triggerAction: 'all',
			width: 150,
			stateful: true,
			stateEvents: [
			'collapse'
			],
			grid: this,
			getState: function() {
				return {
					value: this.getValue()
				}
			},
			applyState: function(state) {
				this.setValue(state.value);
			}
		});
		/* apply the current value to the store after loading */
		this.languageFilter.store.addListener('load', function() {
			this.languageFilter.setValue(this.languageFilter.getValue());
		}, this);

		config = Ext.apply({
			collapsible: true,
			store: this.data_store,
			autoHeight: false,
			height: 400,
			loadMask: true,
			colModel: new Ext.grid.ColumnModel({
				columns: [
				new Ext.grid.Column({
					header: '',
					width: 32,
					renderer: function(path) {
						return '<img src="/typo3/' + path + '" alt="" height="18" width="16" />';
					},
					dataIndex: 'icon'
				}),
				new Ext.grid.Column({
					header: '',
					width: 32,
					renderer: function(flag) {
						if(flag.length > 0) {
							return '<img src="/typo3/gfx/flags/' + flag + '" alt="" height="13" width="21" />';
						}
						return '';
					},
					dataIndex: 'flag'
				}),
				showColumn,
				new Ext.grid.Column({
					header: 'Path',
					dataIndex: 'path',
					id: 'path',
					width: 200,
					tooltip: 'The path shown in the address line'
				}),
				new Ext.grid.Column({
					header: 'Redirect path',
					dataIndex: 'redirect_path',
					id: 'redirect',
					width: 200,
					hidden: true
				}),
				new Ext.grid.Column({
					header: 'Domain',
					dataIndex: 'domain',
					id: 'domain',
					width: 80
				}),
				new Ext.grid.Column({
					header: 'Params',
					dataIndex: 'params',
					id: 'params',
					width: 250
				})
				]
			}),
			plugins: [showColumn],
			tbar: [
			{
				id: 'addButton',
				xtype: "button",
				text: 'Add',
				icon: '../Resources/GFX/Icons/add.png',
				handler: this.onAdd,
				scope: this
			},
			'-',
			{
				id: 'editButton',
				xtype: "button",
				text: 'Edit',
				icon: '../Resources/GFX/Icons/edit.png',
				disabled: true,
				handler: this.onEdit,
				scope: this
			}, '-',
			{
				id: 'deleteButton',
				xtype: "button",
				text: 'Delete',
				icon: '../Resources/GFX/Icons/delete.png',
				disabled: true,
				handler: this.onDelete,
				scope: this
			},'-',
			{
				id: 'lockButton',
				xtype: "button",
				text: 'Lock',
				icon: '../Resources/GFX/Icons/locked_unlocked.png',
				disabled: true,
				enableToggle: true,
				toggleHandler: this.onLock,
				scope: this
			},' ','-',' ', 'Filter path:',
			{
				id: 'filterPath',
				xtype: 'textfield',
				name: 'filterPath',
				enableKeyEvents: true
			},' ', '|', ' ', 'Show',' ',
			new Ext.form.ComboBox({
				id: 'filterShow',
				store: [
				[-1, 'All URLs'],
				[0, 'Normal URLs'],
				[1, 'Locked URLs'],
				[2, 'Normal & locked URLs'],
				[3, 'Old URLs'],
				[4, 'Redirects']
				],
				editable: false,
				triggerAction: 'all',
				value: 0,
				width: 150,
				stateful: true,
				stateEvents: [
				'collapse'
				],
				grid: this,
				getState: function() {
					return {
						value: this.getValue()
					};
				},
				applyState: function(state) {
					this.setValue(state.value);
				}
			}), ' ','|',' ','Language:',' ',
			this.languageFilter
			],
			bbar: new tx.naworkuri.pagingToolbar({
				id: "pagingToolbar",
				store: this.data_store,
				pageSize: 10,
				displayInfo: true,
				displayMsg: 'Displaying URLs {0} - {1} of {2}',
				emptyMsg: "No URLs to display",
				grid: this
			}),
			view: new Ext.grid.GridView({
				emptyText: 'No URLs to display.'
			}),
			stateful: true,
			stateEvents: [
			'changeBaseParams'
			],
			getState: function() {
				return this.getStore().baseParams;
			},
			applyState: function(state) {
				this.baseParams = state;
			}
		}, config);
		this.addEvents({
			'cellclicked': true
		});

		this.getSelectionModel().addListener('selectionchange', this.onSelectionChange, this);
		showColumn.addListener('click', this.onShow, this);

		tx.naworkuri.PageUris.superclass.constructor.call(this, config);
		/* apply base params to the store */
		if(this.baseParams) {
			Ext.iterate(this.baseParams, function(name, value) {
				if(name != "url") {
					this.getStore().setBaseParam(name, value);
				}
			}, this);
		}

		/* add listeners */
		this.getTopToolbar().findById('filterPath').addListener('keyup', this.onChangeFilterPath, this);
		this.getTopToolbar().findById('filterShow').addListener('collapse', this.onChangeFilterShow, this);
		this.getBottomToolbar().addListener('changePageSize', this.onChangePageSize, this);
		this.getBottomToolbar().addListener('change', this.onChangePage, this);
		this.getStore().addListener('datachanged', this.checkPage, this);
		this.languageFilter.addListener('collapse', this.onChangeFilterLanguage, this);

		/* after rendering load the store */
		this.addListener('afterrender', function() {
			this.initializeColumns();
			this.reloadStore();
		}, this);
	},

	/**
	 * On every selection change evaluate the selection state. Changes only edit,delete and lock buttons
	 *
	 * If there is no record selected, only the buttons are disabled
	 * If there is one record selected, all buttons are enabled
	 * If there are more than on record selected, only the delete button is enabled
	 *
	 */
	initializeButtons: function() {
		var selection = this.getSelectionModel().getSelections();
		var lockBtn = this.getTopToolbar().findById('lockButton');
		var editBtn = this.getTopToolbar().findById('editButton');
		var deleteBtn = this.getTopToolbar().findById('deleteButton');
		if(selection.length > 0) {
			if(selection.length == 1) {
				selection = selection[0];
				if(selection.data.locked == 1) {
					lockBtn.toggle(true, true); // set to pressed mode
					lockBtn.setText('Unlock');
					lockBtn.setIcon('../Resources/GFX/Icons/locked.png');
				} else {
					lockBtn.toggle(false, true); // set to released mode
					lockBtn.setText('Lock');
					lockBtn.setIcon('../Resources/GFX/Icons/locked_unlocked.png');
				}
				lockBtn.enable();
				editBtn.enable();
			} else {
				lockBtn.disable();
				editBtn.disable();
			}
			deleteBtn.enable();
		} else {
			lockBtn.disable();
			lockBtn.toggle(false, true);
			editBtn.disable();
			deleteBtn.disable();
		}
	},

	initializeColumns: function() {
		var show = this.getTopToolbar().findById('filterShow').getValue();
		if(show == 4) {
			this.getColumnModel().setHidden(6, true);
			this.getColumnModel().setHidden(4, false);
		} else {
			this.getColumnModel().setHidden(6, false);
			this.getColumnModel().setHidden(4, true);
		}
	},

	/**
	 * Reload the store using the base params
	 * The base param are set everytime a filter changes
	 */
	reloadStore: function() {
		this.getStore().reload({
			params: this.getStore().baseParams
		});
	},

	/**
	 * Check if the current page contains records. If not change the page
	 * to fit the record number. Through the function "changePage", the change
	 * event of the toolbar is triggered so the base param start is set automatically
	 * @see onChangePage
	 */
	checkPage: function() {
		var t = this.getBottomToolbar()
		var activePage = Math.ceil((t.cursor + t.pageSize) / t.pageSize);
		var pages = Math.ceil(this.getStore().getTotalCount() / t.pageSize);
		if(pages > 0 && activePage > pages) {
			t.changePage(pages);
		}
	},

	/**
	 * Set the given base params to the store and fire the changeBaseParams event
	 * to save the state of the base params
	 *
	 * @param params An object like the base params
	 */
	setBaseParams: function(params) {
		Ext.iterate(params, function(name, value, object) {
			this.getStore().setBaseParam(name, value);
		}, this);
		this.fireEvent('changeBaseParams');
	},

	/**
	 * Called when the row selection changes. Depending on the selection the
	 * buttons must be en- or disabled.
	 */
	onSelectionChange: function(selectionModel) {
		this.initializeButtons();
	},

	/**
	 * called after clicking the delete button
	 * Shows a confirm dialog and on "yes" deletes the selected urls
	 */
	onDelete: function(btn, ev) {
		Ext.Msg.confirm(
			"Delete selected URLs?",
			"Do you really want to delete the selected URLs?",
			function(button) {
				if(button == "yes") {
					var selection = this.getSelectionModel().getSelections();
					if(selection.length > 0) {
						var conn = new Ext.data.Connection();
						if(selection.length > 1) {
							var deleteUids = [];
							for(var i=0; i<selection.length; i++) {
								deleteUids[i] = selection[i].data.uid;
							}
							conn.request({
								url: this.initialConfig.backPath + "ajax.php?ajaxID=tx_naworkuri::modpageuris&mode=delete_multiple&uids=" + deleteUids.join(","),
								scope: this,
								success: this.reloadStore

							});
						} else {
							conn.request({
								url: this.initialConfig.backPath + 'ajax.php?ajaxID=tx_naworkuri::modpageuris&mode=delete&uid=' + selection[0].data.uid,
								scope: this,
								success: this.reloadStore
							});
						}
					}
				}
			},
			this
			)
	},

	/**
	 * Opens the tce form to add a new url
	 */
	onAdd: function(btn, ev) {
		var url = '/typo3/alt_doc.php?returnUrl=' + location.href + '&edit[tx_naworkuri_uri][' + this.initialConfig.storagePage + ']=new';
		location.href = url;
	},

	/**
	 * Opens the tce form to edit the selected record
	 */
	onEdit: function(btn, ev) {
		var selection = this.getSelectionModel().getSelections();
		if(selection.length == 1) {
			var record = selection[0];
			var url = '/typo3/alt_doc.php?returnUrl=' + location.href + '&edit[tx_naworkuri_uri][' + record.data.uid + ']=edit';
			location.href = url;
		}
	},

	/**
	 * Switches the locked state for the selected url
	 */
	onLock: function(btn, active) {
		var selection = this.getSelectionModel().getSelections();
		if(selection.length == 1) {
			var record = selection[0];
			var state = parseInt(record.data.locked) == 1 ? true : false;
			var modified = parseInt(record.data.locked) != parseInt(active) ? true : false;
			if(modified) {
				var conn = new Ext.data.Connection();
				if(!state) {
					conn.request({
						url: this.initialConfig.backPath + 'ajax.php?ajaxID=tx_naworkuri::modpageuris&mode=lock&uid=' + parseInt(record.data.uid),
						scope: this,
						success: this.reloadStore
					});
				} else {
					conn.request({
						url: this.initialConfig.backPath + 'ajax.php?ajaxID=tx_naworkuri::modpageuris&mode=unlock&uid=' + parseInt(record.data.uid),
						scope: this,
						success: this.reloadStore
					});
				}
			}

		}
	},

	/**
	 * Opens a new window and loads the selected url
	 */
	onShow: function(btn, ev, rec) {
		var url = location.protocol + "//" +  location.host + "/" + rec.data.url;
		window.open(url, "popup", null);
	},

	/**
	 * This is called if the grid's page changes. Sets the store's base param
	 * on which record number to start on query
	 *
	 * @stateful The current page is not stateful
	 */
	onChangePage: function() {
		var t = this.getBottomToolbar()
		var activePage = Math.ceil((t.cursor + t.pageSize) / t.pageSize);
		this.getStore().setBaseParam('start', (activePage-1)*t.pageSize);
	},

	/**
	 * Change the page size of the pagination toolbar
	 *
	 * @stateful The page size is saved in the limit base param
	 */
	onChangePageSize: function(pageSize) {
		this.setBaseParams({
			limit: pageSize
		});
		this.reloadStore();
	},

	/**
	 * Called when any key is pressed in the path filter field.
	 * Sets the url base param for the query and reloads the store
	 * if there isn't any other key pressed within 200 ms.
	 *
	 * @stateful We dont want to make this settings stateful
	 */
	onChangeFilterPath: function() {
		this.getStore().setBaseParam('url', this.getTopToolbar().findById('filterPath').getValue());
		clearTimeout(this.filterTimeout);
		this.filterTimeout = this.reloadStore.defer(200, this);
	},

	/**
	 * The show filter changes so we must adjust the base param of the store and
	 * reload it.
	 *
	 * @stateful This settings is stateful
	 */
	onChangeFilterShow: function() {
		var show = this.getTopToolbar().findById('filterShow').getValue();
		var type = this.getStore().baseParams.type;
		var locked = this.getStore().baseParams.locked;
		switch(show) {
			case -1:
				type = -1;
				locked = -1;
				break;
			case 1:
				type = 0;
				locked = 1;
				break;
			case 2:
				type = 0;
				locked = -1;
				break;
			case 3:
				type = 1;
				locked = -1;
				break;
			case 4:
				type = 2;
				locked = -1;
				break;
			default:
				type = 0;
				locked = 0;
				break;
		}
		this.setBaseParams({
			type: type,
			locked: locked
		});
		this.initializeColumns();
		this.reloadStore();
	},

	/**
	 * Change the language filter for the url records
	 *
	 * @stateful The language filter is stateful
	 */
	onChangeFilterLanguage: function() {
		this.setBaseParams({
			language: this.languageFilter.getValue()
		});
		this.reloadStore();
	}
});

Ext.reg( 'naworkuri-pageuris', tx.naworkuri.PageUris );


