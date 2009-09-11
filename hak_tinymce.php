<?php

// This is a PLUGIN TEMPLATE.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'hak_tinymce';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '0.7.4';
$plugin['author'] = 'Patrick Woods';
$plugin['author_uri'] = 'http://www.hakjoon.com/';
$plugin['description'] = 'A TinyMCE based WYSIWYG editor';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public       : only on the public side of the website (default)
// 1 = public+admin : on both the public and admin side
// 2 = library      : only when include_plugin() or require_plugin() is called
// 3 = admin        : only on the admin side
$plugin['type'] = '1';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '0';

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
if (@txpinterface == 'admin') {
    add_privs('article','1,2,3,4,5,6');
	add_privs('hak_tinymce_prefs', '1,2');
	add_privs('hak_tinymce_js','1,2,3,4,5,6');
	add_privs('hak_tinymce_compressor_js','1,2,3,4,5,6');
	add_privs('hak_txpimage','1,2,3,4,5,6');
	add_privs('hak_txpcatselect','1,2,3,4,5,6');
	
	register_callback("hak_tinymce::article", "article");
	register_callback("hak_tinymce::js_prep", "hak_tinymce_js");
	register_callback("hak_tinymce::compressor_js_prep", "hak_tinymce_compressor_js");
	register_callback("hak_txpimage", "hak_txpimage");
	register_callback("hak_txpcatselect", "hak_txpcatselect");
	
	register_tab('extensions', 'hak_tinymce_prefs', 'hak_tinymce');
	register_callback('hak_tinymce_prefs', 'hak_tinymce_prefs');
    register_callback('hak_tinymce::inject_toggle', 'article_ui', 'sidehelp');
}

class hak_tinymce {

    public static function inject_toggle($event, $step, $default, $context_data='') {
        extract(self::getPrefs());
    
        if ($show_toggle  && ($enable_body || $enable_excerpt)) {
            $msg = '<h3 class="plain lever"><a href="#hak_tinymce">'.self::mce_gTxt('hak_toggle_editor').'</a></h3>'.
                '<div id="hak_tinymce" class="toggle" style="display:none">'.
                '<div><input type="checkbox" value="body" id="hak_bodyToggle" class="checkbox" style="width:auto"" />'.
                '<label for="hak_bodyToggle">'.ucwords(gTxt('article')).'</label></div>'.
                '<div><input type="checkbox" value="excerpt" id="hak_excerptToggle" class="checkbox" style="width:auto"" />'.
                '<label for="hak_excerptToggle">'.ucwords(gTxt('excerpt')).'</label></div>'.
                '</div>';
        }
        
        return $msg.$default;
    }
    
    public static function article($event, $step) {

        if (hak_tinymce_check_install()) {
            $hak_tinymce = self::getPrefs();
            extract(get_prefs());
            
            /* if editing an article get ID and fetch use textile settings */
            if ($step == 'edit') {
                $hak_ID = (!empty($GLOBALS['ID'])) ? $GLOBALS['ID'] : gps('ID');
                $hak_ID = assert_int($hak_ID);
                $rs = safe_row("textile_body, textile_excerpt","textpattern","ID=$hak_ID");
                extract($rs);
            } else {
                extract(gpsa(array('textile_body','textile_excerpt')));
            }
            
            /* if this is a new article we use the global use textile setting.  From txp_article */
            if ($step == 'create') {
                $textile_body = $use_textile;
                $textile_excerpt = $use_textile;
            }
            
            if (self::is_edit_screen()) {
                $hak_tinymce["script_path"] = ($hak_tinymce["use_compressor"]) ? hak_compressor_path($hak_tinymce["tinymce_path"]) : $hak_tinymce["tinymce_path"];
                $msg = "<script language='javascript' type='text/javascript' src='".$hak_tinymce["script_path"]."'></script>";
                if ($hak_tinymce["use_compressor"]) {
                    $msg .= "<script language='javascript' type='text/javascript' src='index.php?event=hak_tinymce_compressor_js'></script>";
                }
                $msg .= "<script language='javascript' type='text/javascript' src='index.php?event=hak_tinymce_js&hak_textile_body=".$textile_body."&hak_textile_excerpt=".$textile_excerpt."'></script>";
                if (!($step=='edit' && $hak_tinymce["hide_on_textile_edit"] && ($textile_body != 0 && $textile_excerpt != 0))) {
                    echo $msg;
                }
            }
        }
    }

    public static function js_prep() {
        header('Content-type: application/x-javascript');
        echo self::js();
        exit(0);
    }

    public static function compressor_js_prep() {
        header('Content-type: application/x-javascript');
        echo hak_tinymce_compressor_js();
        exit(0);
    }


    private function js() {

        extract(self::getPrefs());
        $hu = hu;
        $js = <<<EOF
            
            var hak_tinymce = (function () {
                    
                    var settings = {
                    body:{
                        document_base_url:"$hu",
                        $body_init
                        mode: "none",
                        elements:"body"
                    },
                    excerpt: {
                        document_base_url:"$hu",
                        $excerpt_init
                        mode:"none",
                        elements: "excerpt"
                    }
                    };
                    tinyMCE.init(settings.body);
                    tinyMCE.init(settings.excerpt);
                    var textileMap = [2,0,1];
        
                    var addControl = function (opts) {
                        tinyMCE.settings = settings[opts.id];
                        tinyMCE.execCommand('mceAddControl', false, opts.id);
                        opts.checkbox.checked = true;
                        var select = $('#markup-' + opts.id);
                        originalMarkupSelect[opts.id] = select.val();
                        select.val(0);
                    };

                    var removeControl = function (opts) {
                        tinyMCE.settings = settings[opts.id];
                        tinyMCE.execCommand('mceRemoveControl', false, opts.id);
                        opts.checkbox.checked = false;
                        var select = $('#markup-' + opts.id);
                        select.val(originalMarkupSelect[opts.id]);
                    };
                    
                    var originalMarkupSelect = {};

                    return {
                    toggleEditor: function() {
                            var id = $(this).val();
                            var mceControl = tinyMCE.get(id);
                            if (!!mceControl) {
                                removeControl({
                                    id:id,
                                    checkbox:this
                                            });
                            } else {
                                addControl({
                                    id:id, 
                                    checkbox:this
                                            });
                            }
                        }
                    }
                    
                })();

        $(document).ready(function () {
                $("#hak_tinymce input.checkbox").click(hak_tinymce.toggleEditor);
            });

EOF;
		return $js;
    }
    
    //--support functions 
    private function is_edit_screen() {
        extract( gpsa(array('from_view', 'view')));
        return (empty($from_view) || $view == 'text');
    }

    public static function getPrefs() {
        global $mcePrefs;
        
        if (!$mcePrefs) {
            $r = safe_rows_start('pref_name, pref_value', 'txp_hak_tinymce','1=1');
            if ($r) {
                while ($a = nextRow($r)) {
                    $out[$a['pref_name']] = $a['pref_value']; 
                }
                $mcePrefs = $out;
                return $mcePrefs;
            }
        }
        return $mcePrefs;
    }
    
    private function mce_gTxt($what) {
        global $language;
	
        $en_us = array(
                       'hak_show_toggle' => 'Show editor toggle:',
                       'hak_hide_on_textile_edit' => 'Hide editor toggle when editing articles created with textile or convert linebreaks: ',
                       'hak_tinymce_body_init' => 'Initialization for article body editor:',
                       'hak_tinymce_excerpt_init' => 'Initialization for article excerpt editor:',
                       'hak_tinymce_callbacks' => 'Callback functions:',
                       'hak_tinymce_compressor_init' => 'Initialization for Gzip compressor editor:',
                       'hak_tinymce_path' => 'Path to tiny_mce script (relative to your textpattern directory or document root):',
                       'file_not_found' => 'File not found in specified location.',
                       'compressor_not_found' => 'Compressor files not found with TinyMCE files.',
                       'line_end' => 'All lines should end with commas.',
                       'compressor_line_end' => 'The last line should not end with a comma.',
                       'install_message' => 'hak_tinymce is not yet properly initialized.  Use the button below to create the preferences table.',
                       'hak_toggle_editor' => 'Toggle Editor',
                       'uninstall' => 'Uninstall',
                       'uninstall_message' => 'Using the button below will remove the hak_tinymce preferences table.  You will still have to remove the actual TinyMCE installation.',
                       'uninstall_confirm' => 'Are you sure you want to delete the preferences table?',
                       'insert_thumb' => 'Insert Thumbnail',
                       'insert_image' => 'Insert Full Size Image',
                       'hak_hide_textile_select' => 'Hide "Use textile" Dropdowns:',
                       'enable_body' => 'Enable editor for article body:',
                       'enable_excerpt' => 'Enable editor for article excerpt:',
                       'auto_disable' => 'The toggle is automatically hidden if you disable the editor for the article body and the article excerpt below.',
                       'documentation' => '[Documentation]',
                       'use_compressor' => 'Use the Gzip compressor:'
                       );
        
        $lang = array(
                      'en-us' => $en_us
                      );
		
		$language = (isset($lang[$language])) ? $language : 'en-us';
		$msg = (isset($lang[$language][$what])) ? $lang[$language][$what] : $what;
		return $msg;
    }

} //--- End Class

//---------------------------------


//---------------------------------
function hak_tinymce_oldjs() {
	
	extract (gpsa(array('hak_textile_body','hak_textile_excerpt')));
	extract(hak_get_mceprefs());
	
	$js = "var hak_show_toggle = ".$show_toggle.";\n\n";
	$js .= "var hak_textile_body = '".$hak_textile_body."';";
	$js .= "var hak_textile_excerpt = '".$hak_textile_excerpt."';";
	$js .= "var hak_article_toggle = '<input type=\"checkbox\" name=\"body_mcetoggle\" id=\"body_mcetoggle\" onclick=\"hak_toggleEditor(\'Body\')\" /><label for=\"body_mcetoggle\">".gTxt('article')."</label>';\n";
	$js .= "var hak_excerpt_toggle = '<input type=\"checkbox\" name=\"excerpt_mcetoggle\" id=\"excerpt_mcetoggle\" onclick=\"hak_toggleEditor(\'Excerpt\')\" /><label for=\"excerpt_mcetoggle\">".gTxt('excerpt')."</label>';\n";
	$js .= "var hak_toggle_editor = '".hak_tinymce_gTxt('hak_toggle_editor')."';\n";
	$js .= "var hak_hide_textile_select = ".$hide_textile_select.";\n";
	$js .= "var document_base_url = '".hu."';\n";
	$js .= "var hak_enable_body = ".$enable_body.";\n";
	$js .= "var hak_enable_excerpt = ".$enable_excerpt.";\n";
	$js .= <<<EOF
	
	// we create an associative array with our settings so we can assign them when we trigger the controls
	var hak_mceSettings = [];
	hak_mceSettings["body"] = {
		document_base_url: document_base_url,
		$body_init
		mode: "none",
		elements: "Body"
	};
	
	hak_mceSettings["excerpt"] = {
		document_base_url: document_base_url,
		$excerpt_init
		mode: "none",
		elements: "Excerpt"
	};
	
	var hak_mceTextileMap = new Array(2,0,1);	

	function hak_textileCheck(obj,element) {
		var mceControl = tinyMCE.getInstanceById(element);
		var toggle = document.getElementById(element.toLowerCase() + "_mcetoggle");
		if (mceControl && obj.options.selectedIndex != 2) {
			tinyMCE.execCommand('mceRemoveControl',false,element);
			toggle.checked = false;
		}
	}
	
	function hak_getByName(name,tagName) {
		var aTags = document.getElementsByTagName(tagName);
	
		for (var i = 0;i < aTags.length;i++) {
			if (aTags[i].name == name) {
				return aTags[i];
			}
		}
		return false;
	}
	
	function hak_addControl(element) {
		var elementLC = element.toLowerCase();
		tinyMCE.settings = hak_mceSettings[elementLC]; // this tells tinyMCE which config to use
		var mceControl = tinyMCE.getInstanceById(element);
		if (!mceControl) {
			tinyMCE.execCommand('mceAddControl',false,element);
			hak_getByName("textile_" + elementLC, "select").selectedIndex = 2;
			var hak_toggle = document.getElementById(elementLC + "_mcetoggle");
			if (hak_toggle) {
				hak_toggle.checked = true;
			}
		}
	}
	
	function hak_removeControl(element) {
		var elementLC = element.toLowerCase();
		var mceControl = tinyMCE.getInstanceById(element);
		if (mceControl) {
			tinyMCE.execCommand('mceRemoveControl',false,element);
			console.log(hak_mceTextileMap[eval("hak_textile_" + elementLC)]);
			hak_getByName("textile_" + elementLC, "select").selectedIndex = hak_mceTextileMap[eval("hak_textile_" + elementLC)];
			var hak_toggle = document.getElementById(elementLC + "_mcetoggle");
			if (hak_toggle) {
				hak_toggle.checked = false;
			}
		}
	}
	
	function hak_toggleEditor(element) {
		var mceControl = tinyMCE.getInstanceById(element);
		if (mceControl) {
			hak_removeControl(element);
		} else {
			hak_addControl(element);
		}
	}
	

	function hak_tinyMCE() {
		if (document.article.from_view.value == "text") {
			var articleArea = hak_getByName("Body","textarea");
			var excerptArea = hak_getByName("Excerpt","textarea");
			if (hak_show_toggle  && (hak_enable_body || hak_enable_excerpt)) {
				var node = document.getElementById("advanced").parentNode
				var togglestr = '<h3 class="plain lever"><a href="#hak_tinymce">'+ hak_toggle_editor +'</a></h3>';
				togglestr += '<div id="hak_tinymce" style="display:none">';
				togglestr += '<p>';
				if (articleArea && hak_enable_body) {
					togglestr += hak_article_toggle;
					togglestr += '<br />';
				}
				if (excerptArea && hak_enable_excerpt) {
					togglestr += hak_excerpt_toggle;
				}
				togglestr += '</p>';
				togglestr += '</div>';
				//node.innerHTML = togglestr + node.innerHTML;
			}
			
			if (articleArea && hak_enable_body) {
				var textileSelectBody = hak_getByName("textile_body","select");
				if (hak_hide_textile_select) {
					textileSelectBody.parentNode.style.display = 'none';
				}
				if (textileSelectBody) {
					textileSelectBody.onchange = function() {
						hak_textileCheck(this,'Body');
					}
					if (textileSelectBody.options[textileSelectBody.selectedIndex].value == 0) {
						hak_addControl('Body');
					}
				}
			}
			
			if (excerptArea && hak_enable_excerpt) {
				var textileSelectExcerpt = hak_getByName("textile_excerpt","select");
				if (hak_hide_textile_select) {
					textileSelectExcerpt.parentNode.style.display = 'none';
				}
				if (textileSelectExcerpt) {
					textileSelectExcerpt.onchange = function() {
						hak_textileCheck(this, 'Excerpt');
					}
					if (textileSelectExcerpt.options[textileSelectExcerpt.selectedIndex].value == 0) {
						hak_addControl('Excerpt');
					}
				}
			}
			
		//-- end if from_view == text
		} else {
			var articleForm = hak_getByName("article","form");
			var bodyTextile = document.createElement("input");
				bodyTextile.type="hidden";
				bodyTextile.name = "textile_body";
				bodyTextile.value = hak_textile_body;
			articleForm.appendChild(bodyTextile);
				
			var excerptTextile = document.createElement("input");
				excerptTextile.type="hidden";
				excerptTextile.name = "textile_excerpt";
				excerptTextile.value = hak_textile_excerpt;
			articleForm.appendChild(excerptTextile)
		}
	}
	
	addEvent(window,'load',function() {
			hak_tinyMCE();
		}
	);
	
	// These are any user specified callback functions
	$callbacks
	
	// initialize the two instances that won't really be used but is needed.
	tinyMCE.init(hak_mceSettings["body"]);
	tinyMCE.init(hak_mceSettings["excerpt"]);
		
EOF;
 return $js;
}
//--------------------------------------------
function hak_tinymce_compressor_js() {
	extract(hak_get_mceprefs());
	
	$js = "tinyMCE_GZ.init({ \n";
	$js.= rtrim($compressor_init, ",");
	$js .="});";
  return $js;
}
//--------------------------------------------
function hak_tinymce_check_install() {
	// Check if the hak_tinymce table already exists
	if (getThings("Show tables like '".PFX."txp_hak_tinymce'")) {
		// if it does check if we need to upgrade
		$pluginversion = safe_field('version','txp_plugin',"name = 'hak_tinymce'");
		$prefs = hak_tinymce::getPrefs();
		$version = (array_key_exists('version', $prefs)) ? $prefs['version'] : "0.0" ;
		
		if (!empty($version) && $version != $pluginversion) {  // if the versions don't match send off to upgrade.
			hak_tinymce_upgrade($version);
		}
		return true;
	}
	return false;
}
//--------------------------------------------
function hak_tinymce_install() {
		
		//figure out what MySQL version we are using (from _update.php)
		$mysqlversion = mysql_get_server_info();
		$tabletype = ( intval($mysqlversion[0]) >= 5 || preg_match('#^4\.(0\.[2-9]|(1[89]))|(1\.[2-9])#',$mysqlversion)) 
						? " ENGINE=MyISAM "
						: " TYPE=MyISAM ";
		if ( isset($txpcfg['dbcharset']) && (intval($mysqlversion[0]) >= 5 || preg_match('#^4\.[1-9]#',$mysqlversion))) 
		{
			$tabletype .= " CHARACTER SET = ". $txpcfg['dbcharset'] ." ";
		}
		
		// Create the hak_tinymce table
		$hak_tinymce_prefs_table = safe_query("CREATE TABLE `".PFX."txp_hak_tinymce` (
		  `pref_name` VARCHAR(255) NOT NULL, 
		  `pref_value` TEXT NOT NULL,
		  PRIMARY KEY (`pref_name`)
		) $tabletype");
		
		// if the table creation succeeds populate with values
		if ($hak_tinymce_prefs_table) {
			
			extract(get_prefs());
			
			$hak_mceSettings_default = '';
			$hak_mceSettings_default .= "theme : \"advanced\",\n";
			$hak_mceSettings_default .= "language : \"en\",\n";
			$hak_mceSettings_default .= "convert_fonts_to_spans : \"true\",\n";
			$hak_mceSettings_default .= "relative_urls : \"false\",\n";
			$hak_mceSettings_default .= "remove_script_host : \"false\",\n";
			$hak_mceSettings_default .= "plugins : \"searchreplace,txpimage\",\n";
			$hak_mceSettings_default .= "theme_advanced_buttons1 : \"bold,italic,underline,strikethrough,forecolor,backcolor,removeformat,numlist,bullist,outdent,indent,justifyleft,justifycenter,justifyright,justifyfull\",\n";
			$hak_mceSettings_default .= "theme_advanced_buttons2 : \"link,unlink,separator,image,separator,search,replace,separator,cut,copy,paste,separator,code,separator,formatselect\",\n";
			$hak_mceSettings_default .= "theme_advanced_buttons3 : \"\",\n";
			$hak_mceSettings_default .= "theme_advanced_toolbar_location : \"top\",\n";
			$hak_mceSettings_default .= "theme_advanced_toolbar_align : \"left\",";
			
			$hak_mceSettings_compressor = "theme : \"advanced\",\n";
			$hak_mceSettings_compressor .= "plugins : \"searchreplace,txpimage\",\n";
			$hak_mceSettings_compressor .= "disk_cache : \"true\",\n";
			$hak_mceSettings_compressor .= "languages : \"en\",\n";
			$hak_mceSettings_compressor .= "debug : \"false\"";
			
			// set pref array values properly checking if it had been setup before.
			$hak_tinymce_prefs["show_toggle"] = (isset($hak_tinymce_show_toggle)) ? $hak_tinymce_show_toggle : "1";
			$hak_tinymce_prefs["hide_on_textile_edit"] = (isset($hak_tinymce_hide_on_textile_edit)) ? $hak_tinymce_hide_on_textile_edit : "1";
			$hak_tinymce_prefs["body_init"] = (isset($hak_tinymce_init_form) && $hak_tinymce_init_form != "hak_tinymce_default") ? fetch_form($hak_tinymce_init_form) : $hak_mceSettings_default;
			$hak_tinymce_prefs["body_init"] .= "\nheight:\"420\",";
			$hak_tinymce_prefs["excerpt_init"] = $hak_mceSettings_default."\nheight:\"150\",";
			$hak_tinymce_prefs["callbacks"] = '';
			$hak_tinymce_prefs["tinymce_path"] = 'tiny_mce/tiny_mce.js';
			$hak_tinymce_prefs["hide_textile_select"] = '0';
			$hak_tinymce_prefs["enable_body"] = '1';
			$hak_tinymce_prefs["enable_excerpt"] = '1';
			
			
			// insert them into the new table
			foreach ($hak_tinymce_prefs as $key => $value) {
				safe_insert("txp_hak_tinymce","pref_name='".$key."', pref_value='".$value."'");
			}
			// Run any necessary upgrades 
			hak_tinymce_upgrade('0.0');
			// delete old prefs
			safe_delete("txp_prefs","name='hak_tinymce_init_form'");
			safe_delete("txp_prefs","name='hak_tinymce_show_toggle'");
			safe_delete("txp_prefs","name='hak_tinymce_hide_on_textile_edit'");
		}
		return true;
}
//-------------------------------------------
function hak_tinymce_upgrade($installedversion) {
	if ($installedversion != '0.7') {

		$hak_mceSettings_compressor = "theme : \"simple,advanced\",\n";
		$hak_mceSettings_compressor .= "plugins : \"searchreplace,-txpimage\",\n";
		$hak_mceSettings_compressor .= "disk_cache : true,\n";
		$hak_mceSettings_compressor .= "languages : \"en\",\n";
		$hak_mceSettings_compressor .= "debug : false";
		
		$hak_tinymce_prefs["use_compressor"] = '0';
		$hak_tinymce_prefs["compressor_init"] = $hak_mceSettings_compressor;
		
		if (!safe_field("pref_name", 'txp_hak_tinymce', "pref_name = 'version'")) {
			safe_insert("txp_hak_tinymce","pref_name='version', pref_value='0.7'");
		} else {
			safe_update('txp_hak_tinymce', "pref_value = '0.7'", "pref_name = 'version'");
		}
		
		foreach ($hak_tinymce_prefs as $key => $value) {
			if (!safe_field("pref_name", 'txp_hak_tinymce', "pref_name = '".$key."'")) {
				safe_insert("txp_hak_tinymce","pref_name='".$key."', pref_value='".$value."'");
			} else {
				safe_update('txp_hak_tinymce', "pref_value = '".$value."'", "pref_name = '".$key."'");
			}
		}
	} // -- End 0.7 upgrade
}

//-------------------------------------------
function hak_tinymce_prefs($event, $step) {
	pagetop('hak_tinymce '.gTxt('preferences'), ($step == 'update' ? gTxt('preferences_saved') : ''));
	
	if ($step == 'install') {
		// Install the preferences table.
		hak_tinymce_install();
	}
	
	if ($step == 'uninstall') {
		//remove table
		safe_query("DROP TABLE ".PFX."txp_hak_tinymce");
	}
	
	if ($step == 'update')
	{
		extract(doSlash(gpsa(array(
			'hak_show_toggle', 
			'hak_hide_on_textile_edit', 
			'hak_tinymce_path', 
			'hak_tinymce_body_init', 
			'hak_tinymce_excerpt_init', 
			'hak_tinymce_callbacks',
			'hak_hide_textile_select',
			'hak_enable_body',
			'hak_enable_excerpt',
			'hak_use_compressor',
			'hak_tinymce_compressor_init'
		))));

		safe_update('txp_hak_tinymce', "pref_value = '$hak_show_toggle'", "pref_name = 'show_toggle'");
		safe_update('txp_hak_tinymce', "pref_value = '$hak_hide_on_textile_edit'", "pref_name = 'hide_on_textile_edit'");
		safe_update('txp_hak_tinymce', "pref_value = '$hak_tinymce_path'", "pref_name = 'tinymce_path'");
		safe_update('txp_hak_tinymce', "pref_value = '$hak_tinymce_body_init'", "pref_name = 'body_init'");
		safe_update('txp_hak_tinymce', "pref_value = '$hak_tinymce_excerpt_init'", "pref_name = 'excerpt_init'");
		safe_update('txp_hak_tinymce', "pref_value = '$hak_tinymce_callbacks'", "pref_name = 'callbacks'");
		safe_update('txp_hak_tinymce', "pref_value = '$hak_hide_textile_select'", "pref_name = 'hide_textile_select'");
		safe_update('txp_hak_tinymce', "pref_value = '$hak_enable_body'", "pref_name = 'enable_body'");
		safe_update('txp_hak_tinymce', "pref_value = '$hak_enable_excerpt'", "pref_name = 'enable_excerpt'");
		safe_update('txp_hak_tinymce', "pref_value = '$hak_use_compressor'", "pref_name = 'use_compressor'");
		safe_update('txp_hak_tinymce', "pref_value = '$hak_tinymce_compressor_init'", "pref_name = 'compressor_init'");
 	}

	if (hak_tinymce_check_install()) {
		extract(hak_get_mceprefs());
		echo n.t.'<div style="margin: auto; width:40%;">'.
			n.t.t.hed('hak_tinymce '.gTxt('Preferences'), '1').
			n.n.form(
				n.eInput('hak_tinymce_prefs').
				n.sInput('update').
				n.fInput('submit', 'update', 'Update', 'smallerbox').
				n.graf(hak_tinymce_gTxt('hak_show_toggle').br.
					n.yesnoRadio('hak_show_toggle',$show_toggle).br.
					n.tag(tag(hak_tinymce_gTxt('auto_disable'),"em"),"small")
					).
				n.graf(hak_tinymce_gTxt('hak_hide_on_textile_edit').br.
					n.yesnoRadio('hak_hide_on_textile_edit',$hide_on_textile_edit)
					).
					n.graf(hak_tinymce_gTxt('hak_hide_textile_select').br.
						n.yesnoRadio('hak_hide_textile_select',$hide_textile_select)
						).
				n.graf(hak_tinymce_gTxt('hak_tinymce_path').br.
					n.finput('text','hak_tinymce_path',$tinymce_path,'','','',60,'','hak_tinymce_path').
					hak_file_exists($tinymce_path)
					).
				n.graf(hak_tinymce_gTxt('enable_body').br.
					n.yesnoRadio('hak_enable_body',$enable_body)
					).
				n.graf(hak_tinymce_gTxt('hak_tinymce_body_init').br.
				tag(tag("(".hak_tinymce_gTxt('line_end').")","em"),"small").n.href(hak_tinymce_gTxt('documentation'),"http://tinymce.moxiecode.com/documentation.php").br.
					n.text_area('hak_tinymce_body_init',200, 400, $body_init)
					).
					n.graf(hak_tinymce_gTxt('enable_excerpt').br.
					n.yesnoRadio('hak_enable_excerpt',$enable_excerpt)
					).
				n.graf(hak_tinymce_gTxt('hak_tinymce_excerpt_init').br.
				tag(tag("(".hak_tinymce_gTxt('line_end').")","em"),"small").n.href(hak_tinymce_gTxt('documentation'),"http://tinymce.moxiecode.com/documentation.php").br.
					n.text_area('hak_tinymce_excerpt_init',200,400,$excerpt_init)
					).
				n.graf(hak_tinymce_gTxt('hak_tinymce_callbacks').br.
					n.text_area('hak_tinymce_callbacks',200,400,$callbacks)
					).
				n.graf(hak_tinymce_gTxt('use_compressor').br.
					n.yesnoRadio('hak_use_compressor',$use_compressor).
					hak_file_exists(hak_compressor_path($tinymce_path), "compressor_not_found")
					).
				n.graf(hak_tinymce_gTxt('hak_tinymce_compressor_init').br.
					tag(tag("(".hak_tinymce_gTxt('compressor_line_end').")","em"),"small").
					n.href(hak_tinymce_gTxt('documentation'),"http://wiki.moxiecode.com/index.php/TinyMCE:Compressor/PHP").br.
					n.text_area('hak_tinymce_compressor_init',200,400,$compressor_init)
						).
				n.n.fInput('submit', 'update', 'Update', 'smallerbox')
				).
			'</div>';
			echo n.t.'<div style="margin: 60px auto 0; width:40%;">'.
				n.hed(hak_tinymce_gTxt('uninstall'), '1').
				n.t.t.graf(hak_tinymce_gTxt('uninstall_message')).
				n.n.form(
					n.eInput('hak_tinymce_prefs').
					n.sInput('uninstall').
					n.n.fInput('submit', 'uninstall', 'Uninstall ', 'smallerbox'),"","confirm('".hak_tinymce_gTxt('uninstall_confirm')."')"
					).
				'</div>';
	} else {
		echo n.t.'<div style="margin: auto; width:40%;">'.
			n.t.t.hed('hak_tinymce '.gTxt('Preferences'), '1').
			n.graf(hak_tinymce_gTxt('install_message')).
			n.n.form(
				n.eInput('hak_tinymce_prefs').
				n.sInput('install').
				n.n.fInput('submit', 'install', 'Install ', 'smallerbox')
				).
			'</div>';
	}
}
//----------------------------------------
function hak_file_exists($file, $message = "file_not_found") {
	global $path_to_site;
	$out = '';
	if (!file_exists($file) && !file_exists($path_to_site.$file)) {
		$out = br.n.tag(hak_tinymce_gTxt($message),"span",' style="color:red"');
	}
	return $out;
}
//----------------------------------------
function hak_compressor_path($file) {
	$path = str_replace('tiny_mce.js','tiny_mce_gzip.js',$file);
	return $path;
}


//--- Functions for the image browser ----
function hak_txpimage() {
	global $img_dir,$path_to_site,$txpcfg;
	$category = gps("c");
	$category = (!empty($category)) ? "and category='".$category."'" : "";
	$rs = safe_rows_start("*", "txp_image","1=1 ".$category." order by category,name");
	$src = gps("src");
	
	if ($rs) {
		$out = array();
		while ($a = nextRow($rs)) {
			extract($a);
			$selected ='';
			$thumbclick ='';
			$image["path"] = $img_dir.'/'.$id.$ext;
			$image["width"] = $w;
			$image["height"] = $h;
			$image["alt"] = (empty($alt)) ? "" : rawurlencode($alt);
			$image["caption"] = (empty($caption)) ? "" : rawurlencode($caption);
			$onclick = 'onclick=\'insertImage(this,"'.$image["path"].'","'.$image["width"].'","'.$image["height"].'","'.$image["alt"].'","'.$image["caption"].'");return'.n.'false;\'';
			
			$preview = $image;
			$thumb = $image;
			
			if($thumbnail) {
				$thumb["path"] = $img_dir.'/'.$id.'t'.$ext;
				$props = @getimagesize($path_to_site.'/'.$thumb["path"]);
				$thumb["width"] = ($props[0]) ? $props[0] : "";
				$thumb["height"] = ($props[1]) ? $props[1] : "";
				$thumb["alt"] = $image["alt"];
				$thumb["caption"] = $image["caption"];
				$preview = $thumb;
				$thumbclick = 'onclick=\'insertImage(this,"'.$thumb["path"].'","'.$thumb["width"].'","'.$thumb["height"].'","'.$thumb["alt"].'","'.$image["caption"].'");return'.n.'false;\'';
				$thumbclick = 	'<a href="#" '.$thumbclick.'><img src="images/pictures.png" width="18" height="18" title="'.hak_tinymce_gTxt('insert_thumb').'" alt="'.hak_tinymce_gTxt('insert_thumb').'" /></a>';
			}
			
			//$desiredheight = $preview["height"];
			if ($preview["width"] > $preview["height"]) {
				$new["width"] = 100;
				if (!empty($preview["width"])) {
                    $new["height"] = (100 / $preview["width"]) * $preview["height"];
                } else {
                    $new["height"] = "";
                }
				$margin = (100 - $new["height"]) / 2;
				$margin = intval($margin)."px 0";
			} else {
				$new["height"] = 100;
				if (!empty($preview["height"])) {
                    $new["width"] = (100 / $preview["height"] ) * $preview["width"];
                } else {
                    $new["width"] = "";
                }
				$margin = (100 - $new["width"]) / 2;
				$margin = "0 ".intval($margin)."px";
			}
			
			$out[] = '<div'.$selected.'><div style="padding:'.$margin.'"><img src="'.hu.$preview["path"].'" height="'.$new["height"].'" width="'.$new["width"].'" onclick="window.open(\''.hu.$image["path"].'\',\'mypopup\', \'menubar=0,status=0,height='.$image["height"].',width='.$image["width"].'\')"/></div>'.
			'<a href="#" '.$onclick.'><img src="images/picture.png" width="18" height="18" alt="'.hak_tinymce_gTxt('insert_image').'" title="'.hak_tinymce_gTxt('insert_image').'" /></a>'.
			$thumbclick.
			'</div>';
		}
		echo implode($out,"\n");
		exit(0);
	}
}

function hak_txpcatselect() {
	$rs = getTree("root",'image');
	if ($rs) {
		echo tag(gTxt('category'),"legend").
				treeSelectInput("category",$rs,"");
	}
	exit(0);
}


# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---
<style type="text/css">dt {font-weight: bold; margin-top:5px;}</style>

	<h1>hak_tinymce &#8211; WYSIWYG article editor</h1>


	<p>This plugin adds a TinyMCE based WYSIWYG editor to Textpattern.</p>

	<h2>Installation</h2>
	<ol>
		<li>Upload the included TinyMCE distribution to somewhere in your document root.   The default location is in your /textpattern/ directory.</li>
		<li>Install the plugin included in hak_tinymce.txt and activate it. <a href="http://www.textpattern.net/wiki/index.php?title=Intermediate_Weblog_Model#Adding_Plugins_to_Your_Textpattern_Installation">Installing plugins</a></li>
		<li> Go to <em>Extensions -> hak_tinyme</em> and run the installation.</li>
		<li>If you placed TinyMCE somewhere other then in /textpattern/ you can set the location now</li>
	</ol>

	<h2>Behavior</h2>
	<ul>
	<li>The editor will not come on by default on blank articles if &#8220;Use Textile&#8221; is selected in the Preferences.  If you want to use the editor all the time change the default to &#8220;Leave text untouched&#8221; </li>
		<li>If textile is turned on it will be disabled if you toggle the editor on. Conversely if you turn Textile back on it will turn off the editor.</li>
	</ul>


	<h2>Configuration</h2>

	<p>A hak_tinymce tab is available under <em>extensions</em> with the following options.</p>

<dl>
	<dt>Show editor toggle.</dt>
	<dd>Determines whether to show the <em>Toggle Editor</em> link. Default is yes. The toggle is automatically hidden if you disable the editor for the article body and the article excerpt below.</dd>
	<dt>Hide editor toggle when editing articles created with textile or convert linebreaks. </dt>
	<dd>Determines if the <em>Toggle Editor</em> link should be available when editing articles that where created using textile or convert linebreaks. Default is yes.</dd>
	<dt>Hide "Use textile" Dropdowns</dt>
	<dd>Determines if the "Use Textile" Dropdowns should be hidden.  Default is yes.</dd>
	<dt>Path to tiny_mce script</dt>
	<dd>The path to the TinyMCE script to use.  Should be either relative to /textpattern/ or to your document root.</dd>
	<dt>Enable for article body:</dt>
	<dd>Determines if the editor can be activated for the Article Body.</dd>
	<dt>Initialization for article body editor:</dt>
	<dd>The initialization block to use for the article body editor. Configuration documentation can be found on the <a href="http://tinymce.moxiecode.com/documentation.php">tinyMCE site</a>.</dd>
	<dt>Enable for article excerpt:</dt>
	<dd>Determines if the editor can be activated for the Article excerpt.</dd>
	<dt>Initialization for article excerpt editor:</dt>
	<dd>The initialization block to use for the article excerpt editor. Configuration documentation can be found on the <a href="http://tinymce.moxiecode.com/documentation.php">tinyMCE site</a>.</dd>
	<dt>Callback functions</dt>
	<dd>Allows you to add functions that can be used by TinyMCE callbacks.</dd>
</dl>

	<h2>Uninstall</h2>
	<p>You also have the option to uninstall the preferences table that is created during installation.  The TinyMCE installation needs to be removed manually.</p>

	<h3>Default initialization string</h3>
	<p>This new version uses a mostly stock initialization string with a few exceptions.</p>

	<ul>
		<li><strong>convert_fonts_to_spans</strong> is set to <em>true</em> because we all should try  to use font tags.  This can be overridden in the init blocks.</li>
		<li>The <em>TXPImage</em> plugin replaces the standard image insert dialog.  This can be overridden in the init blocks.</li>
		<li><strong>document_base_url</strong> is automatically set to the value of <em>Site URL</em>.  This can be overridden, but should not be necessary.</li>
		<li><strong>mode</strong> is set to <em>none</em> so that the toggles work.  This cannot be overridden or else the toggles will not work properly</li>
	</ul>

	<h2>Inserting images with <em>TXPImage</em></h2>

	<p><em>TXPImage</em> is a custom image browsing plugin that integrates into the TXP backend. It allows you to browse your image categories and insert either the thumbnail or full size images for each image.  It is hopefully easy to use.</p>

	<p><img src="data:image/jpeg;base64,R0lGODlhGAAZAPcAAPb3+PT195et0Ziu0e/z94OexneVwpKqzufu9oOdxq6+1uzx94Sfxubu9uzx9Iq/P4Ofxqa20qS205quzpjHTnSzOIehyoSfx5eu0fP3+PH0+PD0+JKpzWWWO+jw9X2Yw+rv85293O3x9oujxIigxuvu8+3y6u7y68DM38ydTsjZ55CnynqWwubv7Iit0pyx0Jm20rbE2nqZtHiVwuju8niXu5C22nqXxPDz92msOFmKMJuxyY+7Xa6+2K3H34efuerw6F2nLu/x84GcxfP19+nw96B3MO3v8Yukye/z+LG/2H+aw3eWupzKUn2dtH6dtO7z9+3w9Jiuz+Xq8neVu8PO37jG2+zu8JTDRJet0Iyx1uru8mKpMHatV4OexHmWw3mZu+zx6efv9eTs6ZSpzPL2+HyXxHuXw5OpyoCcxmqcQbDI3HeUwU2WTW+hRl+QNbrP4aa503yYxfD094zBR57KSebt94WexvD194SexJq83vH1+O/092ulZOrr6uTo3X6aw4ipy5vIR6fPWYKcxoKdyH6axMzb6VSFK+7v76Cz0X2ZxL/L3neTwqa40pOqzoyjtX6bxcDAwP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAAAAAAALAAAAAAYABkAAAj/ACcJHEiwoMGDCBMSlMSwocOHECUJlKgwIUWKFQ9ezIhwo8AqEbJgOCCgpBRGGicOdJQIgMuXDl6knIRxAIA4kJ44AUPIghkkMQp6nPQoA5o/J0wAiUKkBIgVQlUKHFAGQpgdP5jUMPBFTqMlCgYOHbAnUosRfgKoXUsjj1ipkw4kSePhTIBDPOpg6bKmyIW3NAdywCFjzAw8KgQNalIBhhhAgDEqElJowQ0+cB5QoMMlEAIGkQei6MFiChUoPvrkCNLGxefQBMkcMbAghJ4URmxoaWAI9kArEtiIcKOmwxsdiOws8k2QxBUNG+YQmL7lDvOBSiYMKfChQIIEXsJKEcXIcSHc8tfRX4zIHiJ6hQEBADs%3D" alt="Insert Thumbnail" align="middle" /> This icon is used to insert the thumbnail for the image.</p>

	<p><img src="data:image/jpeg;base64,R0lGODlhGAAZAPcAAJiu0PT195mv0JOrz5Srz5Oqz+/z95mv0aa3056655qvz+fu9ubu9oaxbpa04/Hz916Ri5q35ZbIYoa8a/Lz9Ozx93epnu7z98rU42mVounw95PBipiuz3ac1nanS+jw9oa2t6fSc4aytZSz45Gy0Jy45u/099OeU6XQgYa6YJyx0pKqz368V6C86H27UXKcwmaZP+bt98TTrnWBXYqr3vL193+CVcbR4nuvd2OSkJrLZo7EYn6yoHm5TsLZuZfJaenv8o2t4Jl6NKbQgfD198bR44Cj2om8ZPD0+O3y9+zz99GeU12PiOvv8u3x92mYpIC9UYaKQIrBWKnRg8nIm4Om3O/z+Ofv9aDMko/EXe3v8fP09HmpTYyz0JK10e3x9oGlx2idma6FPnyh2cjT5I3BcOvs7LHA2oq6gvD093+0qGKVO3me15i25HOlSZfJZJyw0Ovy93+9UtGubXqqaoeo3ebt9n+1opLBh4a/VfH0+Jyx0ZDFXKG00nyhxGSXPYWNQ/Dz96DLkp/NfIbAV8HcvsDAwP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAAAAAAALAAAAAAYABkAAAj/AA8JHEiwoMGDCBMSNMSwocOHEA0JlKgwIUWKFQ9ezIhwo0AMCBQMGDmygMkCHG4M9HgIgZktAWLKjKkEyJ6VEwcqoDCiixosU1BsCPOiQ5wDOA9hJBDAAQhBIXRI+IHmCRsNSHMqHUigRps7Q97wkUKoDIQxHwQkXUokAo9BWfJAcTGBiZErarUuNVFCBJ4dcnqwwJGhyoK8WxMfInAhgRcLDY6koJPDT53DawcOSNIiAYkZgKLYABOEBgPELAdU4OLBjZATS8TA+LPmdGaBK5wU2i2DyhwfuwvZAXD7UIEvwZMHj0Fc78A+Wh7oQZImkIHrBqw0UVGczBkBAMKLFRcPp0hxjgu1ok+veL3eiPAhuk8YEAA7" alt="Insert Full Image" align="middle" /> This icon is used to insert the full size image.</p>

	<h2>Known issues</h2>

	<ul>
		<li>This update <strong>requires</strong> version 4.0.2. or higher.</li>
		<li>Safari and Opera support is still experimental.</li>
	</ul>


	<h2>Credits</h2>

	<p>This is an update of <a href="http://textpattern.org/plugins/320/mictinymce">mic_tinymce</a>, originally developed by <a href="http://micampe.it/">Michele Campeotto</a>.</p>

	<p>A lot of the admin code was made possible by examining <a href="http://www.upm-plugins.com">Mary&#8217;s plugins</a>.</p>

	<p>TinyMCE is created and maintained by <a href="http://tinymce.moxiecode.com/">Moxiecode</a> and released under the LGPL.</p>
# --- END PLUGIN HELP ---
-->
<?php
}
?>