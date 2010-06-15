Ext.namespace('tx','tx.naworkuri');

tx.naworkuri.PageUris = Ext.extend(Ext.grid.GridPanel, {

    constructor: function(config) {
		var stickyColumn = new Ext.grid.CheckColumn({
			header: 'Sticky',
			dataIndex: 'sticky',
			width: 36,
			tooltip: 'Click to change sticky state'
		});
		var editButton = new Ext.grid.ButtonColumn({
			header: '',
			width: 26,
			icon: '../resources/images/edit.png'
		});
		var deleteButton = new Ext.grid.ButtonColumn({
			header: '',
			width: 26,
			icon: '/typo3/sysext/t3skin/icons/gfx/garbage.gif'
		});
		var hiddenColumn = new Ext.grid.ButtonColumn({
			header: '',
			width: 26,
			enableToggle: true,
			icon: '/typo3/sysext/t3skin/icons/gfx/button_hide.gif',
			iconToggle: '/typo3/sysext/t3skin/icons/gfx/button_unhide.gif',
			dataIndex: 'hidden'
		});
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
					'icon',
					'uid',
					'url',
					'domain',
					'params',
					'flag',
					'sticky',
					'edit',
					'delete',
					'hidden',
					'show'
				],
				baseParams: {
					start: 0,
					limit: 10
				},
//				proxy: new Ext.data.HttpProxy({
//					url: config.backPath + 'ajax.php?ajaxID=tx_naworkuri::getpageuris&page=' + config.page,
//				}),
				autoLoad: true
		});
		
		config = Ext.apply({
//			height: 400,
			collapsible: true,
			store: this.data_store,
			autoHeight: true,
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
					new Ext.grid.Column({
						header: 'Path',
						dataIndex: 'url',
						id: 'url',
						width: 200,
						tooltip: 'The path shown in the address line'
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
					}),
					stickyColumn,
					editButton,
					deleteButton,
					hiddenColumn,
					showColumn
//					this.actions
//					{header: 'Edit', width: 150}
				],
				defaults: {
//						editable: false
				}
			}),
			plugins: [stickyColumn, editButton, deleteButton, hiddenColumn, showColumn],
			tbar: [
				{
					id: 'addButton',
					text: 'Add',
					icon: '../resources/images/add.png',
					handler: this.onAdd,
					scope: this
				}/*,'-',
				{
					id: 'deleteButton',
					text: 'Delete',
//					iconCls: 'silk-delete',
					disabled: true,
					handler: this.onDelete,
					scope: this
				},'-',
				{
					id: 'stickyButton',
					text: 'Sticky',
					disabled: true,
					enableToggle: true,
					toggleHandler: this.onSticky,
					scope: this
				}*/
			],
			bbar: new Ext.PagingToolbar({
				store: this.data_store,
				pageSize: 10,
				displayInfo: true,
				displayMsg: 'Displaying URLs {0} - {1} of {2}',
				emptyMsg: "No URLs to display"
			}),
			view: new Ext.grid.GridView({
				emptyText: 'No pid given. Please click on a page in the page tree.'
			})
		}, config);
		this.addEvents({
			'cellclicked': true
		});
		
		this.getSelectionModel().addListener('selectionchange', this.onSelectionChange, this);
		stickyColumn.addListener('click', this.onSticky, this);
		editButton.addListener('click', this.onEdit, this);
		deleteButton.addListener('click', this.onDelete, this);
		hiddenColumn.addListener('click', this.onHidden, this);
		showColumn.addListener('click', this.onShow, this);

		tx.naworkuri.PageUris.superclass.constructor.call(this, config);
//		this.on('afterrender', function() {
//			this.getStore().load()
//		}, this);
	},

	initializeButtons: function(uid) {
//		if(uid) {
//			for(var i = 0; i < this.getStore().getTotalCount(); i++) {
//				console.debug(i + ': ');
//				console.debug(this.getStore().getAt(i));
//				if(parseInt(this.getStore().getAt(i).data.uid) == uid) {
//					this.getSelectionModel().selectRow(i)
//					break;
//				}
//			}
//		}
		var selection = this.getSelectionModel().getSelections();
		var stickyBtn = this.getTopToolbar().findById('stickyButton');
		var deleteBtn = this.getTopToolbar().findById('deleteButton');
		if(selection.length > 0) {
			if(selection.length == 1 && stickyBtn) {
				selection = selection[0];
				if(selection.data.sticky == 1) {
					stickyBtn.toggle(true, true); // set to pressed mode
				} else {
					stickyBtn.toggle(false, true); // set to released mode
				}
				stickyBtn.enable();
			} else if(stickyBtn) {
				stickyBtn.disable();
			}
			if(deleteBtn) {
				deleteBtn.enable();
			}
		} else {
			if(stickyBtn) {
				stickyBtn.disable();
				stickyBtn.toggle(false, true);
			}
			if(deleteBtn) {
				deleteBtn.disable();
			}
		}
	},

	onSelectionChange: function(selectionModel) {
		this.initializeButtons();
	},

	onDelete: function(btn, ev, rec) {
		var conn = new Ext.data.Connection();
		conn.request({
			url: this.initialConfig.backPath + 'ajax.php?ajaxID=tx_naworkuri::modpageuris&mode=delete&uid=' + rec.data.uid,
			scope: this,
			success: function(response) {
				this.getStore().load();
			}
		})
	},

	onHidden: function(btn, ev, rec) {
		var state = parseInt(rec.data.hidden) == 1 ? true : false;
		var modified = parseInt(rec.modified.hidden) == 1 ? true : false;
		var conn;
		if(!state && !modified) {
			conn = new Ext.data.Connection();
			conn.request({
				url: this.initialConfig.backPath + 'ajax.php?ajaxID=tx_naworkuri::modpageuris&mode=hide&uid=' + parseInt(rec.data.uid),
				scope: this,
				success: function() {
					this.getStore().load();
				}
			});
		} else if(!state && modified) {
			conn = new Ext.data.Connection();
			conn.request({
				url: this.initialConfig.backPath + 'ajax.php?ajaxID=tx_naworkuri::modpageuris&mode=unhide&uid=' + parseInt(rec.data.uid),
				scope: this,
				success: function() {
					this.getStore().load();
				}
			});
		}
	},

	onAdd: function(btn, ev) {
		var url = '/typo3/alt_doc.php?returnUrl=' + location.href + '&edit[tx_naworkuri_uri][328]=new';
		location.href = url;
	},

	onEdit: function(btn, ev, rec) {
		// window.location.href='alt_doc.php?returnUrl='+T3_THIS_LOCATION+'&edit[pages_language_overlay][132]=edit'; return false;
		var url = '/typo3/alt_doc.php?returnUrl=' + location.href + '&edit[tx_naworkuri_uri][' + rec.data.uid + ']=edit';
		location.href = url;
//		console.debug(url);
//		var win = new Ext.Window({
//			autoLoad: url
//
//		});
//		win.show();
	},

	onSticky: function(btn, ev, rec) {
		var state = parseInt(rec.data.sticky) == 1 ? true : false;
		var modified = parseInt(rec.modified.sticky) == 1 ? true : false;
		var conn;
		if(!state && !modified) {
			conn = new Ext.data.Connection();
			conn.request({
				url: this.initialConfig.backPath + 'ajax.php?ajaxID=tx_naworkuri::modpageuris&mode=sticky&uid=' + parseInt(rec.data.uid),
				scope: this,
				success: function() {
					this.getStore().load();
				}
			});
		} else if(!state && modified) {
			conn = new Ext.data.Connection();
			conn.request({
				url: this.initialConfig.backPath + 'ajax.php?ajaxID=tx_naworkuri::modpageuris&mode=unsticky&uid=' + parseInt(rec.data.uid),
				scope: this,
				success: function() {
					this.getStore().load();
				}
			});
		}
	},

	onShow: function(btn, ev, rec) {
		var url = location.protocol + "//" +  location.host + "/" + rec.data.url;
		console.debug(url);
		window.open(url, "popup", null);
	}
});

Ext.reg( 'naworkuri-pageuris', tx.naworkuri.PageUris );


