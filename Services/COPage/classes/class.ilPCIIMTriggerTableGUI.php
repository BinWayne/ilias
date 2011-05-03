<?php

/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("Services/Table/classes/class.ilTable2GUI.php");
include_once("Services/MediaObjects/classes/class.ilImageMapTableGUI.php");

/**
* TableGUI class for pc image map editor
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ingroup ServicesCOPage
*/
class ilPCIIMTriggerTableGUI extends ilImageMapTableGUI
{
	
	/**
	* Constructor
	*/
	function __construct($a_parent_obj, $a_parent_cmd, $a_pc_media_object,
		$a_parent_node_name)
	{
		global $ilCtrl, $lng, $ilAccess, $lng;

		$this->parent_node_name = $a_parent_node_name;
		$this->pc_media_object = $a_pc_media_object;
		$this->mob = $this->pc_media_object->getMediaObject();
		$this->ov_files = $this->mob->getFilesOfDirectory("overlays");
		$this->ov_options = array("" => $lng->txt("please_select"));
		foreach ($this->ov_files as $of)
		{
			$this->ov_options[$of] = $of;
		}
		$this->popups = $this->pc_media_object->getPopups();
		$this->pop_options = array("" => $lng->txt("please_select"));
		foreach ($this->popups as $k => $p)
		{
			$this->pop_options[$p["title"]] = $p["title"];
		}
		parent::__construct($a_parent_obj, $a_parent_cmd, $a_pc_media_object->getMediaObject());
		$this->setRowTemplate("tpl.iim_trigger_row.html", "Services/COPage");
	}
	
	/**
	 * Init columns
	 */
	function initColumns()
	{
		$this->addColumn("", "", "1");	// checkbox
		$this->addColumn($this->lng->txt("title"), "title", "");
		$this->addColumn($this->lng->txt("type"), "", "");
		$this->addColumn($this->lng->txt("cont_shape")."/".$this->lng->txt("cont_coords"), "", "");
		$this->addColumn($this->lng->txt("cont_overlay_image"), "", "");
		$this->addColumn($this->lng->txt("cont_content_popup"), "", "");
		$this->addColumn($this->lng->txt("actions"), "", "");
	}

	/**
	 * Init actions
	 */
	function initActions()
	{
		global $lng;
		
		// action commands
		$this->addMultiCommand("confirmDeleteTrigger", $lng->txt("delete"));
		
		$data = $this->getData();
		if (count($data) > 0)
		{
			$this->addCommandButton("updateTrigger", $lng->txt("cont_update_titles_and_actions"));
		}
	}


	/**
	* Get items of current folder
	*/
	function getItems()
	{
		$triggers = $this->pc_media_object->getTriggers();
		
		$triggers = ilUtil::sortArray($triggers, "Title", "asc", false, true);
		$this->setData($triggers);
	}
	
	/**
	* Standard Version of Fill Row. Most likely to
	* be overwritten by derived class.
	*/
	protected function fillRow($a_set)
	{
		global $lng, $ilCtrl, $ilAccess;

		$i = $a_set["Nr"];
		$this->tpl->setVariable("CHECKBOX",
			ilUtil::formCheckBox("", "tr[]", $i));
		$this->tpl->setVariable("VAR_NAME", "title[".$i."]");
		$this->tpl->setVariable("VAL_NAME", $a_set["Title"]);
		$this->tpl->setVariable("VAL_SHAPE", $a_set["Shape"]);
		$this->tpl->setVariable("VAL_COORDS",
			implode(explode(",", $a_set["Coords"]), ", "));
		switch ($a_set["Link"]["LinkType"])
		{
			case "ExtLink":
				$this->tpl->setVariable("VAL_LINK", $a_set["Link"]["Href"]);
				break;

			case "IntLink":
				$link_str = $this->parent_obj->getMapAreaLinkString($a_set["Link"]["Target"],
					$a_set["Link"]["Type"], $a_set["Link"]["TargetFrame"]);
				$this->tpl->setVariable("VAL_LINK", $link_str);
				break;
		}
//var_dump($a_set);

		$this->tpl->setVariable("VAR_POS", "ovpos[".$i."]");
		$this->tpl->setVariable("VAL_POS", $a_set["PosX"].",".$a_set["PosY"]);
		$this->tpl->setVariable("OVERLAY_IMAGE",
			ilUtil::formSelect($a_set["OverAction"], "ov[".$i."]", $this->ov_options, false, true));
		$this->tpl->setVariable("CONTENT_POPUP",
			ilUtil::formSelect($a_set["ClickAction"], "pop[".$i."]", $this->pop_options, false, true));
	}

}
?>