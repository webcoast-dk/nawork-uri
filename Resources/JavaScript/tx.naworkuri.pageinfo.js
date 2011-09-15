Ext.namespace('tx','tx.naworkuri');

tx.naworkuri.PageInfo = Ext.extend(Ext.Panel, {

    constructor: function(config) {
		config = Ext.apply({
			html            : "node info",
			autoHeight      : true,
			autoLoad        : config.backPath + 'ajax.php?ajaxID=tx_naworkuri::getpageinfo&page=' + config.page
		}, config);

		tx.naworkuri.PageInfo.superclass.constructor.call(this, config);
	}
});

Ext.reg( 'naworkuri-pageinfo', tx.naworkuri.PageInfo );


