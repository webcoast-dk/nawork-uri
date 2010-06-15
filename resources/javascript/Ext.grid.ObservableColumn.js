Ext.grid.ObservableColumn = Ext.extend(Ext.grid.Column, {
	constructor: function(config) {
		config = Ext.apply({
			init: function(grid) {
				this.grid = grid;
				this.grid.on('render', function() {
					var view = this.grid.getView();
					view.mainBody.on('mousedown', this.onMouseDown, this);
				})
			},
			onMouseDown: function(ev, el, obj) {
				if(el.className && el.className.indexOf('x-grid3-cc-'+this.id) != -1){
					ev.stopEvent();
					var index = this.grid.getView().findRowIndex(t);
					var record = this.grid.store.getAt(index);
					record.set(this.dataIndex, !record.data[this.dataIndex]);
					this.fireEvent('click', this, ev, record);
				}
			},
			onRender: function() {
				Ext.grid.ObservableColumn.superclass.onRender.apply(this, arguments);
				return '';
			}
		}, config);

		this.addEvents({
			click: true
		});

		if(!this.id) {
			this.id = Ext.id();
		}
		Ext.grid.ObservableColumn.superclass.constructor.call(this, config);
		this.renderer = this.renderer.createDelegate(this);
	}
});

