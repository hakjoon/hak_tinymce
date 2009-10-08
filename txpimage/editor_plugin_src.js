/**
 * $Id: editor_plugin_src.js 677 2008-03-07 13:52:41Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2008, Moxiecode Systems AB, All rights reserved.
 */

(function() {
	tinymce.create('tinymce.plugins.TxpImagePlugin', {
		init : function(ed, url) {
			// Register commands
			ed.addCommand('mceTxpImage', function() {
				// Internal image object like a flash placeholder
				if (ed.dom.getAttrib(ed.selection.getNode(), 'class').indexOf('mceItem') != -1)
					return;

				ed.windowManager.open({
					file : url + '/image.htm',
					width : 480 + parseInt(ed.getLang('txpimage.delta_width', 0)),
					height : 385 + parseInt(ed.getLang('txpimage.delta_height', 0)),
					inline : 1
				}, {
					plugin_url : url
				});
			});

			// Register buttons
			ed.addButton('image', {
				title : 'txpimage.image_desc',
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