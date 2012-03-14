Ext.grid.ButtonColumn = function(config){
	this.addEvents({
		click: true
	});
	Ext.grid.ButtonColumn.superclass.constructor.call(this);

	Ext.apply(this, config, {
		init : function(grid){
			this.grid = grid;
			this.grid.on('render', function(){
				var view = this.grid.getView();
				view.mainBody.on('mousedown', this.onMouseDown, this);
			}, this);
		},

		onMouseDown : function(e, t){
			if(t.className && t.className.indexOf('x-grid3-cc-'+this.id) != -1){
				e.stopEvent();
				var index = this.grid.getView().findRowIndex(t);
				var record = this.grid.store.getAt(index);
				record.set(this.dataIndex, !record.data[this.dataIndex]);
				this.fireEvent('click', this, e, record);
			}
		},

		renderer : function(v, p, record){
			v = parseInt(v) > 0 ? true : false
			if(config.isLink) {
				return this.renderLinkButton(v, p, record);
			} else if(config.text && config.text.length > 0) {
				return this.renderTextButton(v, p, record);
			} else if(config.icon && config.icon.length > 0) {
				return this.renderImageButton(v, p, record);
			}
			return '';
		},

		renderTextButton: function(v, p, record) {
			if(config.enableToggle) {
				return '<span id="' + this.id + '" class="x-grid3-button-col' + (v ? '-on' : '') + ' x-grid3-cc-' + this.id + '">' + config.text + '</span>';
			}
			return '<span id="' + this.id + '" class="x-grid3-button-col x-grid3-cc-' + this.id + '">' + config.text + '</span>';
		},

		renderImageButton: function(v, p, record) {
			if(config.enableToggle && config.iconToggle && config.iconToggle.length > 0) {
				return '<img id="' + this.id + '" class="x-grid3-icon-col x-grid3-cc-' + this.id + '" src="' + (v ? config.iconToggle : config.icon) + '"/>';
			}
			return '<img id="' + this.id + '" class="x-grid3-icon-col x-grid3-cc-' + this.id + '" src="' + config.icon + '"/>';
		},

		renderLinkButton: function(v, p, record) {
			if(config.text && config.text.length > 0) {
				return '<a id="' + this.id + '" class="x-grid3-button-col x-grid3-cc-' + this.id + '" href="#" onclick="window.open(\'' + location.protocol + '//' + (record.domain ? record.domain : location.host) + '/' + record.data.url + '\'); return false;">' + config.text + '</a>';
			} else if(config.icon && config.icon.length > 0) {
				return '<a id="' + this.id + '" class="x-grid3-icon-col x-grid3-cc-' + this.id + '" href="#" onclick="window.open(\'' + location.protocol + '//' + (record.domain ? record.domain : location.host) + '/' + record.data.url + '\'); return false;" ><img src="' + config.icon + '" alt="" /></a>';
			} else {
				return '';
			}
		}
	});

	if(!this.id){
		this.id = Ext.id();
	}
	this.renderer = this.renderer.createDelegate(this);
};


Ext.extend(Ext.grid.ButtonColumn, Ext.util.Observable);