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