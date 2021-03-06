il.LearningModule = {
	
	save_url: '',
	init_frame: {},
	last_frame_url: {},
	all_targets: ["center_bottom", "right", "right_top", "right_bottom"],
	
	setSaveUrl: function (url) {
		il.LearningModule.save_url = url;
	},
	
	showContentFrame: function (e, target) {
		return il.LearningModule.loadContentFrame(e.target.href, target);
	},
	
	initContentFrame: function (href, target) {
		il.LearningModule.init_frame[target] = href;
	},
	
	setLastFrameUrl: function (href, target) {
		il.LearningModule.last_frame_url[target] = href;
	},
	
	openInitFrames: function () {
		var i, t;
		for (i = 0; i < il.LearningModule.all_targets.length; i++) {
			t = il.LearningModule.all_targets[i];
			if (il.LearningModule.init_frame[t]) {
				il.LearningModule.loadContentFrame(il.LearningModule.init_frame[t], t);
			} else if (il.LearningModule.last_frame_url[t]) {
				il.LearningModule.loadContentFrame(il.LearningModule.last_frame_url[t], t);
			}
		}
		il.LearningModule.refreshLayout();
	},
	
	refreshLayout: function () {
		var e;
		
		// fix right content area 
		if ($("#right_top_area > iframe").attr("src") &&
			$("#right_bottom_area > iframe").attr("src")) {
			$("#right_cont_area").addClass("ilRightContAreaSplit");
//console.log("splitting");
		} else {
			$("#right_cont_area").removeClass("ilRightContAreaSplit");
//console.log("unsplitting");
		}
		if (!$("#right_top_area > iframe").attr("src") &&
			!$("#right_bottom_area > iframe").attr("src")) {
			$("#right_cont_area").remove();
		}
		
		// adapt main content
		e = $("#left_nav");
		if (e.length != 0) {
			$("#fixed_content").addClass("ilLeftNavSpace");
		} else {
			$("#fixed_content").removeClass("ilLeftNavSpace");
		}
		e = $("#right_area, #right_cont_area");
		if (e.length != 0) {
			$("#fixed_content").addClass("ilRightAreaSpace");
		} else {
			$("#fixed_content").removeClass("ilRightAreaSpace");
		}
	},
	
	loadContentFrame: function (href, t) {
		var area, el_id = t + "_area";

		// exception we should get rid off
		if (t == "center_bottom") {
			el_id = "bot_center_area";
		}
		
		if (t != "right_top" && t != "right_bottom") { 
			area = $("#" + el_id);
			if (area.length == 0) {
				$('body').append('<div id="' + el_id + '"><div id="' + el_id + '_drag"></div><img class="ilAreaClose" src="/templates/default/images/empty.png" /><iframe /></div>');
			}
		} else {
			//check right area existence
			area = $("#right_cont_area");
			if (area.length == 0) {
				$('body').append('<div id="right_cont_area"><div id="right_area_drag"></div></div>');
			}
			// append right top and right bottom areas
			area = $("#right_top_area");
			if (area.length == 0) {
				$('#right_cont_area').append('<div id="right_top_area"><div id="right_top_drag"></div><img class="ilAreaClose" src="/templates/default/images/empty.png" /><iframe /></div>');
			}
			area = $("#right_bottom_area");
			if (area.length == 0) {
				$('#right_cont_area').append('<div id="right_bottom_area"><div id="right_bottom_drag"></div><img class="ilAreaClose" src="/templates/default/images/empty.png" /><iframe /></div>');
			}
		}
		
		$("#" + el_id + " img.ilAreaClose").click(function () {
			il.LearningModule.closeContentFrame(t);
			});
		$("#" + el_id + " > iframe").attr("src", href);
		$("#" + el_id).css("display", "block");
		il.UICore.initLayoutDrag();
		
		il.LearningModule.refreshLayout();
		il.UICore.refreshLayout();
		if (il.LearningModule.save_url != '') {
			il.Util.sendAjaxGetRequestToUrl(il.LearningModule.save_url + "&target=" + t + "&url=" + encodeURIComponent(href),
				{}, {}, il.LearningModule.handleSuccess);
		}
		
		return false;
	},
	
	handleSuccess: function (o) {
		//
	},
	
	closeContentFrame: function (t) {
		var el_id = t + "_area";
		// exception we should get rid off
		if (t == "center_bottom") {
			el_id = "bot_center_area";
		}

		//alert("close!");
		$("#" + el_id).remove();
		il.LearningModule.refreshLayout();
		il.UICore.refreshLayout();
		if (il.LearningModule.save_url != '') {
			il.Util.sendAjaxGetRequestToUrl(il.LearningModule.save_url + "&target=" + t + "&url=",
				{}, {}, il.LearningModule.handleSuccess);
		}
	}
	
	
}

$(function() {
	$('body').focus();
	il.LearningModule.refreshLayout();
	$(document).keydown(function(e) {
	if (e.target.tagName != "TEXTAREA" &&
		e.target.tagName != "INPUT") {
		// right
		if (e.keyCode == 39) {
			var a = $('.ilc_page_rnavlink_RightNavigationLink').first().attr('href');
			if (a) {
				top.location.href = a;
			}
			return false;
		}
		// left
		if (e.keyCode == 37) {
			var a = $('.ilc_page_lnavlink_LeftNavigationLink').first().attr('href');
			if (a) {
				top.location.href = a;
			}
			return false;
		}
		return true;
	}
})});
