/**
 * TXP Image Plugin
 */



(function() {

    // Load plugin specific language pack
    tinymce.PluginManager.requireLangPack('txpimage');
    
    tinymce.create('tinymce.plugins.TxpImagePlugin', {
	init : function(ed, url) {
	    // Register commands
	    ed.addCommand('mceTxpImage', function() {
		if (ed.dom.getAttrib(ed.selection.getNode(), 'class').indexOf('mceItem') != -1) {
		    return;
		}

		ed.windowManager.open({
		    file : url + '/image.htm',
		    width : 480 + parseInt(ed.getLang('advimage.delta_width', 0), 10),
		    height : 480 + parseInt(ed.getLang('advimage.delta_height', 0), 10),
		    inline : 1
		}, {
		    plugin_url : url
		});
	    });

	    // Register buttons
	    ed.addButton('image', {
		title : 'txpimage.desc',
		cmd : 'mceTxpImage'
	    });
	},

	getInfo : function() {
	    return {
		longname : 'Textpattern image',
		author : 'Patrick Woods',
		authorurl : 'http://www.hakjoon.com',
		infourl : 'http://hakjoon.com/code/haktinymce',
		version : 0.2
	    };
	}
    });

    // Register plugin
    tinymce.PluginManager.add('txpimage', tinymce.plugins.TxpImagePlugin);
})();