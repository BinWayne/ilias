
/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

ilTagging =
{
	ref_id: 0,
	sub_id: 0,
	panel: false,
	ajax_url: '',
	
	listTags: function (e, ref_id, sub_id, update_code)
	{
		// prevent the default action
		if (e && e.preventDefault)
		{
			e.preventDefault();
		}
		else if (window.event && window.event.returnValue)
		{
			window.eventReturnValue = false;
		}

		// hide overlays
		ilOverlay.hideAllOverlays(e, true);
		
		this.ref_id = ref_id;
		this.sub_id = sub_id;
		this.update_code = update_code;
		
		// add panel
		this.initPanel(e);
	},
	
	// init the notes editing panel
	initPanel: function(e)
	{
		if (!this.panel)
		{
			var n = document.getElementById('ilTagsPanel');
			if (!n)
			{
				var b = $("body");
				b.append("<div class='yui-skin-sam'><div id='ilTagsPanel' class='ilOverlay' style='overflow:auto;'>" +
					"&nbsp;</div>");
				var n = document.getElementById('ilTagsPanel');
			}
			
			ilOverlay.add("ilTagsPanel", {yuicfg: {}});
			ilOverlay.show(e, "ilTagsPanel");
			this.panel = true;

		}
		else
		{
			ilOverlay.show(e, "ilTagsPanel");
		}
		
		ilTagging.insertPanelHTML("");

		var obj = document.getElementById('ilTagsPanel');
		obj.style.position = 'fixed';
		obj.style.top = '0px';
		obj.style.bottom = '0px';
		obj.style.right = '0px';
		obj.style.left = '';
		obj.style.width = '500px';
		obj.style.height = '100%';
		
		this.sendAjaxGetRequest({cmd: "getHTML", tags_ref_id: this.ref_id, tags_sub_id: this.sub_id}, {mode: 'list_tags'});
	},

	cmdAjaxLink: function (e, url)
	{
		// prevent the default action
		if (e && e.preventDefault)
		{
			e.preventDefault();
		}
		else if (window.event && window.event.returnValue)
		{
			window.eventReturnValue = false;
		}

		this.sendAjaxGetRequestToUrl(url, {}, {mode: 'cmd'});
	},
	
	cmdAjaxForm: function (e, url)
	{
		// prevent the default action
		if (e && e.preventDefault)
		{
			e.preventDefault();
		}
		else if (window.event && window.event.returnValue)
		{
			window.eventReturnValue = false;
		}
		this.sendAjaxPostRequest("ilTagFormAjax", url, {mode: 'cmd'});
	},
	
	setAjaxUrl: function(url)
	{
		this.ajax_url = url;
	},
	
	getAjaxUrl: function()
	{
		return this.ajax_url;
	},
	
	sendAjaxGetRequest: function(par, args)
	{
		var url = this.getAjaxUrl();
		this.sendAjaxGetRequestToUrl(url, par, args)
	},
	
	sendAjaxGetRequestToUrl: function(url, par, args)
	{
		var cb =
		{
			success: this.handleAjaxSuccess,
			failure: this.handleAjaxFailure,
			argument: args
		};
		for (k in par)
		{
			url = url + "&" + k + "=" + par[k];
		}
		var request = YAHOO.util.Connect.asyncRequest('GET', url, cb);
	},

	// send request per ajax
	sendAjaxPostRequest: function(form_id, url, args)
	{
		args.reg_type = "post";
		var cb =
		{
			success: this.handleAjaxSuccess,
			failure: this.handleAjaxFailure,
			argument: args
		};
		var form_str = YAHOO.util.Connect.setForm(form_id);
		var request = YAHOO.util.Connect.asyncRequest('POST', url, cb);
		
		return false;
	},


	handleAjaxSuccess: function(o)
	{
		// perform page modification
		if(o.responseText !== undefined)
		{
			if (o.argument.mode == 'xxx')
			{
			}
			else
			{
				// default action: replace html
				ilTagging.insertPanelHTML(o.responseText);
				if (typeof ilTagging.update_code != "undefined" &&
					ilTagging.update_code != null && ilTagging.update_code != "")
				{
					if (o.argument.reg_type == "post")
					{
						eval(ilTagging.update_code);
					}
				}
			}
		}
	},

	// FailureHandler
	handleAjaxFailure: function(o)
	{
		console.log("ilTagging.js: Ajax Failure.");
	},

	insertPanelHTML: function(html)
	{
		$('div#ilTagsPanel').html(html);
	}
	

};
