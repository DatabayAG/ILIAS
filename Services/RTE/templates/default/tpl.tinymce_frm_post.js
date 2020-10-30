<!-- BEGIN tinymce -->
<!-- BEGIN obj_id -->
	var obj_id = '{OBJ_ID}';
	var obj_type = '{OBJ_TYPE}';
	window.obj_id = obj_id;
	window.obj_type = obj_type;
<!-- END obj_id -->
	var client_id = '{CLIENT_ID}';
	var session_id = '{SESSION_ID}';

	window.client_id = client_id;
	window.session_id = session_id;

	function ilTinyMceInitCallback(ed) {
		// Add hook for onContextMenu so that Insert Image can be removed
		<!-- BEGIN remove_img_context_menu_item -->
		ed.plugins.contextmenu.onContextMenu.add(function(sender, menu) {
			// create a new object
			var otherItems = {};
			var lastItem = null;
			for (var itemName in menu.items) {
				var item = menu.items[itemName];
				if (/^mce_/.test(itemName)) {
					if (item.settings) {
						if (item.settings.cmd == "mceImage" || item.settings.cmd == "mceAdvImage") {
							// skip these items
							var lastItem = item;
							continue;
						}  else if (lastItem && item.settings.separator && (lastItem.settings.cmd == "mceImage" || lastItem.settings.cmd == "mceAdvImage")) {
							lastItem = null;
							continue;
						}
					}
				}
				// add all other items to this new object, so it is effectively a clone
				// of menu.items but without the offending entries
				otherItems[itemName] = item;
			}
			// replace menu.items with our new object
			menu.items = otherItems;
		});
		<!-- END remove_img_context_menu_item -->
	}

	tinyMCE.init({
		mode : "textareas",
		editor_deselector : "noRTEditor",
		theme : "{THEME}",
		language : "{LANG}",
		//plugins : "safari,{ADDITIONAL_PLUGINS}",
		plugins : "{ADDITIONAL_PLUGINS}",
		fix_list_elements : true,
		theme_advanced_blockformats : "{BLOCKFORMATS}",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_path_location : "bottom",
		theme_advanced_buttons1 : "{BUTTONS_1}",
		theme_advanced_buttons2 : "{BUTTONS_2}",
		theme_advanced_buttons3 : "{BUTTONS_3}",
		toolbar: 'latex | undo redo ',
		valid_elements : "{VALID_ELEMENTS}",
<!-- BEGIN formelements -->
		extended_valid_elements : "form[name|id|action|method|enctype|accept-charset|onsubmit|onreset|target],input[id|name|type|value|size|maxlength|checked|accept|s rc|width|height|disabled|readonly|tabindex|accessk ey|onfocus|onblur|onchange|onselect],textarea[id|name|rows|cols|disabled|readonly|tabindex|acces skey|onfocus|onblur|onchange|onselect],option[name|id|value],select[id|name|type|value|size|maxlength|checked|accept|s rc|width|height|disabled|readonly|tabindex|accessk ey|onfocus|onblur|onchange|onselect|length|options |selectedIndex]",
<!-- END formelements -->
		entities : "60,lt,62,gt,38,amp",
		content_css : "{STYLESHEET_LOCATION}",
		plugin_insertdate_dateFormat : "%d.%m.%Y",
		plugin_insertdate_timeFormat : "%H:%M:%S",
		theme_advanced_resize_horizontal : true,
		theme_advanced_resizing : true,
		theme_advanced_fonts : "Arial=sans-serif;Courier=monospace;Times Roman=serif",
		<!-- BEGIN forced_root_block -->forced_root_block : '{FORCED_ROOT_BLOCK}',<!-- END forced_root_block -->
		font_size_style_values : "8pt,10pt,12pt,14pt,18pt,24pt,36pt",
		setup: function (ed) {
			//ed.onInit.add(ilTinyMceInitCallback);
		}
	});
<!-- END tinymce -->

