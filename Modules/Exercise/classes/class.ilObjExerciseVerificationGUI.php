<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once ('./Services/Object/classes/class.ilObject2GUI.php');

/**
* GUI class for test verification
*
* @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
* @version $Id: class.ilPersonalDesktopGUI.php 26976 2010-12-16 13:24:38Z akill $
*
* @ilCtrl_Calls ilObjExerciseVerificationGUI:
*
* @ingroup ModulesExercise
*/
class ilObjExerciseVerificationGUI extends ilObject2GUI
{
	public function getType()
	{
		return "excv";
	}

	/**
	 * List all tests in which current user participated
	 */
	public function create()
	{
		global $ilTabs;

		$this->lng->loadLanguageModule("excv");

		$ilTabs->setBackTarget($this->lng->txt("back"),
			$this->ctrl->getLinkTarget($this, "cancel"));

		include_once "Modules/Exercise/classes/class.ilExerciseVerificationTableGUI.php";
		$table = new ilExerciseVerificationTableGUI($this, "create");
		$this->tpl->setContent($table->getHTML());
	}

	/**
	 * create new instance and save it
	 */
	public function save()
	{
		global $ilUser;
		
		$exercise_id = $_REQUEST["exc_id"];
		if($exercise_id)
		{
			include_once "Modules/Exercise/classes/class.ilObjExercise.php";
			$exercise = new ilObjExercise($exercise_id, false);

			include_once "Modules/Exercise/classes/class.ilObjExerciseVerification.php";
			$newObj = ilObjExerciseVerification::createFromExercise($exercise, $ilUser->getId());
			if($newObj)
			{
				$newObj->create();

				$parent_id = $this->node_id;
				$this->node_id = null;
				$this->putObjectInTree($newObj, $parent_id);
				
				$this->afterSave($newObj);
			}
		}

		ilUtil::sendFailure($this->lng->txt("select_one"));
		$this->create();
	}

	/**
	 * Render content
	 */
	public function render()
	{
		$setting = ilDatePresentation::UseRelativeDates();
		ilDatePresentation::setUseRelativeDates(false);

		$tmp = array();
		$tmp[] = $this->object->getTitle();
		$tmp[] = $this->object->getDescription();
		$tmp[] = ilDatePresentation::formatDate($this->object->getProperty("issued_on"));		
		$tmp[] = $this->object->getProperty("success");
		$tmp[] = $this->object->getProperty("mark");
		$tmp[] = $this->object->getProperty("comment");
		$this->tpl->setContent(implode("<br>", $tmp));

		ilDatePresentation::setUseRelativeDates($setting);
	}
}

?>