Ext.namespace('tx','tx.naworkuri');

tx.naworkuri.pagingToolbar = Ext.extend(Ext.PagingToolbar, {
	resultPerPageBox: new Ext.form.ComboBox({
		id: 'resultsPerPage',
		editable: false,
		store: [
		[10, '10'],
		[20, '20'],
		[50, '50'],
		[100, '100'],
		[0, 'all']
		],
		triggerAction: 'all',
		value: 10,
		width: 60,
		stateful: true,
		grid: this,
		stateEvents: [
		'collapse'
		]
	}),

	constructor: function(config) {
		this.grid = config.grid ? config.grid : NULL;
		this.addEvents('changePageSize');
		config = Ext.apply({
			stateful: true,
			stateEvents: [
			'changePageSize'
			]
		}, config);
		tx.naworkuri.pagingToolbar.superclass.constructor.call(this, config);
	},

	initComponent: function() {
		this.resultPerPageBox.addListener('select', function(selectBox, foo, bar) {
			var limit = selectBox.getValue();
			if(limit < 1) {
				this.pageSize = this.store.getTotalCount();
			} else {
				this.pageSize = limit;
			}
			this.fireEvent('changePageSize', limit);
		}, this);
		this.items = [
		' ', '-',' ',
		this.resultPerPageBox
		];
		tx.naworkuri.pagingToolbar.superclass.initComponent.call(this);
	},

	getState: function() {
		return {
			pageSize: this.pageSize
		}
	},

	applyState: function(state) {
		if(state.pageSize > 100) {
			this.resultPerPageBox.setValue(0);
		} else {
			this.resultPerPageBox.setValue(state.pageSize);
		}
		this.pageSize = state.pageSize;
	}
});