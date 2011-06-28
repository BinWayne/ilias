<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Skill/classes/class.ilBasicSkillGUI.php");

/**
* Basic skill template GUI class
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
* @ilCtrl_isCalledBy ilBasicSkillTemplateGUI: ilObjSkillManagementGUI
*
* @ingroup ServicesSkill
*/
class ilBasicSkillTemplateGUI extends ilBasicSkillGUI
{

	/**
	 * Constructor
	 */
	function __construct($a_node_id = 0)
	{
		global $ilCtrl;
		
		$ilCtrl->saveParameter($this, array("obj_id", "level_id"));
		
		parent::ilSkillTreeNodeGUI($a_node_id);
	}

	/**
	 * Get Node Type
	 */
	function getType()
	{
		return "sktp";
	}

	/**
	 * output tabs
	 */
	function setTabs()
	{
		global $ilTabs, $ilCtrl, $tpl, $lng;

		// properties
		$ilTabs->addTarget("properties",
			 $ilCtrl->getLinkTarget($this,'showProperties'),
			 "showProperties", get_class($this));
			 
		$tpl->setTitleIcon(ilUtil::getImagePath("icon_sktp_b.gif"));
		$tpl->setTitle(
			$lng->txt("skmg_basic_skill_template").": ".$this->node_object->getTitle());
	}

	/**
	 * Set header for level
	 *
	 * @param
	 * @return
	 */
	function setLevelHead()
	{
		global $ilTabs, $ilCtrl, $tpl, $lng;

		// tabs
		$ilTabs->clearTargets();
		$ilTabs->setBackTarget($lng->txt("skmg_skill_levels"),
			$ilCtrl->getLinkTarget($this, "edit"));

		if ($_GET["level_id"] > 0)
		{
			$ilTabs->addTab("level_settings",
				$lng->txt("settings"),
				$ilCtrl->getLinkTarget($this, "editLevel"));

			$ilTabs->addTab("level_trigger",
				$lng->txt("skmg_trigger"),
				$ilCtrl->getLinkTarget($this, "editLevelTrigger"));

			$ilTabs->addTab("level_certificate",
				$lng->txt("certificate"),
				$ilCtrl->getLinkTargetByClass("ilcertificategui", "certificateEditor"));
		}

		// title
		if ($_GET["level_id"] > 0)
		{
			$tpl->setTitle($lng->txt("skmg_skill_level").": ".
				ilBasicSkill::lookupLevelTitle((int) $_GET["level_id"]));
		}
		else
		{
			$tpl->setTitle($lng->txt("skmg_skill_level"));			
		}

		include_once("./Services/Skill/classes/class.ilSkillTree.php");
		$tree = new ilSkillTree();
		$path = $tree->getPathFull($this->node_object->getId());
		$desc = "";
		foreach ($path as $p)
		{
			if (in_array($p["type"], array("scat", "skll")))
			{
				$desc.= $sep.$p["title"];
				$sep = " > ";
			}
		}
		$tpl->setDescription($desc);
	}

	/**
	 * Set header for skill
	 *
	 * @param
	 * @return
	 */
	function setSkillHead()
	{
		global $ilTabs, $ilCtrl, $tpl, $lng;

		$ilTabs->clearTargets();
		$ilTabs->setBackTarget($lng->txt("skmg_skill_templates"),
			$ilCtrl->getLinkTargetByClass("ilobjskillmanagementgui", "editSkillTemplates"));

		if (is_object($this->node_object))
		{
			$tpl->setTitle($lng->txt("skmg_skill_template").": ".
				$this->node_object->getTitle());
			$tpl->setTitleIcon(ilUtil::getImagePath("icon_sktp_b.gif"), $lng->txt("skmg_skill_template"));
		
			include_once("./Services/Skill/classes/class.ilSkillTree.php");
			$tree = new ilSkillTree();
			$path = $tree->getPathFull($this->node_object->getId());
			$desc = "";
			foreach ($path as $p)
			{
				if (in_array($p["type"], array("scat", "skll")))
				{
					$desc.= $sep.$p["title"];
					$sep = " > ";
				}
			}
			$tpl->setDescription($desc);
		}
		else
		{
			$tpl->setTitle($lng->txt("skmg_skill"));
			$tpl->setDescription("");
		}
	}

}
?>