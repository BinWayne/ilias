<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2005 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

require_once "classes/class.ilObjectGUI.php";
require_once("classes/class.ilFileSystemGUI.php");
require_once("classes/class.ilTabsGUI.php");

/**
* Class ilObjSCORMLearningModuleGUI
*
* @author Alex Killing <alex.killing@gmx.de>
* $Id$
*
* @extends ilObjectGUI
* @package ilias-core
*/
class ilObjSAHSLearningModuleGUI extends ilObjectGUI
{
	/**
	* Constructor
	*
	* @access	public
	*/
	function ilObjSAHSLearningModuleGUI($a_data,$a_id,$a_call_by_reference, $a_prepare_output = true)
	{
		global $lng;

		$lng->loadLanguageModule("content");
		$this->type = "sahs";
		$this->ilObjectGUI($a_data,$a_id,$a_call_by_reference,$a_prepare_output);
		$this->tabs_gui =& new ilTabsGUI();

	}

	/**
	* execute command
	*/
	function executeCommand()
	{
		$this->fs_gui =& new ilFileSystemGUI($this->object->getDataDirectory());
		$this->getTemplate();
		$this->setLocator();
		$this->setTabs();

		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		switch($next_class)
		{
			case 'ilmdeditorgui':

				include_once 'Services/MetaData/classes/class.ilMDEditorGUI.php';

				$md_gui =& new ilMDEditorGUI($this->object->getId(), 0, $this->object->getType());
				$md_gui->addObserver($this->object,'MDUpdateListener','General');

				$this->ctrl->forwardCommand($md_gui);
				break;

			case "ilfilesystemgui":
				//$ret =& $this->fs_gui->executeCommand();
				$ret =& $this->ctrl->forwardCommand($this->fs_gui);
				break;

			default:
				$cmd = $this->ctrl->getCmd("frameset");
				$ret =& $this->$cmd();
				break;
		}
		$this->tpl->show();
	}


	function viewObject()
	{
		//add template for view button
		$this->tpl->addBlockfile("BUTTONS", "buttons", "tpl.buttons.html");

		// view button
		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK","content/sahs_presentation.php?ref_id=".$this->object->getRefID());
		$this->tpl->setVariable("BTN_TARGET"," target=\"ilContObj".$this->object->getID()."\" ");
		$this->tpl->setVariable("BTN_TXT",$this->lng->txt("view"));
		$this->tpl->parseCurrentBlock();

		// view button
		$this->tpl->setCurrentBlock("btn_cell");
		$this->tpl->setVariable("BTN_LINK","content/sahs_edit.php?ref_id=".$this->object->getRefID());
		$this->tpl->setVariable("BTN_TARGET"," target=\"bottom\" ");
		$this->tpl->setVariable("BTN_TXT",$this->lng->txt("edit"));
		$this->tpl->parseCurrentBlock();
	}

	/**
	* module properties
	*/
	function properties()
	{
	}

	/**
	* save properties
	*/
	function saveProperties()
	{
	}


	/**
	* no manual SCORM creation, only import at the time
	*/
	function createObject()
	{
		$this->importObject();
	}

	/**
	* display dialogue for importing SCORM package
	*
	* @access	public
	*/
	function importObject()
	{
		// display import form
		// print_r($this->lng);
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.slm_import.html");
		$this->tpl->setVariable("FORMACTION", $this->getFormAction("save","adm_object.php?cmd=gateway&ref_id=".
			$_GET["ref_id"]."&new_type=sahs"));

		$this->tpl->setVariable("BTN_NAME", "save");

		$this->tpl->setVariable("TXT_SELECT_LMTYPE", $this->lng->txt("type"));
		$this->tpl->setVariable("TXT_TYPE_AICC", $this->lng->txt("lm_type_aicc"));
		$this->tpl->setVariable("TXT_TYPE_HACP", $this->lng->txt("lm_type_hacp"));
		$this->tpl->setVariable("TXT_TYPE_SCORM", $this->lng->txt("lm_type_scorm"));

		$this->tpl->setVariable("TXT_UPLOAD", $this->lng->txt("upload"));
		$this->tpl->setVariable("TXT_IMPORT_LM", $this->lng->txt("import_lm"));
		$this->tpl->setVariable("TXT_SELECT_FILE", $this->lng->txt("select_file"));
		$this->tpl->setVariable("TXT_VALIDATE_FILE", $this->lng->txt("cont_validate_file"));

		// get the value for the maximal uploadable filesize from the php.ini (if available)
		$umf=get_cfg_var("upload_max_filesize");
		// get the value for the maximal post data from the php.ini (if available)
		$pms=get_cfg_var("post_max_size");

		// use the smaller one as limit
		$max_filesize=min($umf, $pms);
		if (!$max_filesize) $max_filesize=max($umf, $pms);
		// gives out the limit as a littel notice :)
		$this->tpl->setVariable("TXT_FILE_INFO", $this->lng->txt("file_notice")." $max_filesize.");
	}

	/**
	* display status information or report errors messages
	* in case of error
	*
	* @access	public
	*/
	function uploadObject()
	{
		global $_FILES, $rbacsystem;

		// check if file was uploaded
		$source = $_FILES["scormfile"]["tmp_name"];
		if (($source == 'none') || (!$source))
		{
			$this->ilias->raiseError("No file selected!",$this->ilias->error_obj->MESSAGE);
		}
		// check create permission
		if (!$rbacsystem->checkAccess("create", $_GET["ref_id"], "sahs"))
		{
			$this->ilias->raiseError($this->lng->txt("no_create_permission"), $this->ilias->error_obj->WARNING);
		}
		// get_cfg_var("upload_max_filesize"); // get the may filesize form t he php.ini
		switch ($__FILES["scormfile"]["error"])
		{
			case UPLOAD_ERR_INI_SIZE:
				$this->ilias->raiseError($this->lng->txt("err_max_file_size_exceeds"),$this->ilias->error_obj->MESSAGE);
				break;

			case UPLOAD_ERR_FORM_SIZE:
				$this->ilias->raiseError($this->lng->txt("err_max_file_size_exceeds"),$this->ilias->error_obj->MESSAGE);
				break;

			case UPLOAD_ERR_PARTIAL:
				$this->ilias->raiseError($this->lng->txt("err_partial_file_upload"),$this->ilias->error_obj->MESSAGE);
				break;

			case UPLOAD_ERR_NO_FILE:
				$this->ilias->raiseError($this->lng->txt("err_no_file_uploaded"),$this->ilias->error_obj->MESSAGE);
				break;
		}

		$file = pathinfo($_FILES["scormfile"]["name"]);
		$name = substr($file["basename"], 0, strlen($file["basename"]) - strlen($file["extension"]) - 1);
		if ($name == "")
		{
			$name = $this->lng->txt("no_title");
		}

		// create and insert object in objecttree
		switch ($_POST["sub_type"])
		{
			case "scorm":
				include_once("content/classes/class.ilObjSCORMLearningModule.php");
				$newObj = new ilObjSCORMLearningModule();
				break;

			case "aicc":
				include_once("content/classes/class.ilObjAICCLearningModule.php");
				$newObj = new ilObjAICCLearningModule();
				break;

			case "hacp":
				include_once("content/classes/class.ilObjHACPLearningModule.php");
				$newObj = new ilObjHACPLearningModule();
				break;
		}

		//$newObj->setType("slm");
		//$dummy_meta =& new ilMetaData();
		//$dummy_meta->setObject($newObj);
		//$newObj->assignMetaData($dummy_meta);
		$newObj->setTitle($name);
		$newObj->setSubType($_POST["sub_type"]);
		$newObj->setDescription("");
		$newObj->create();
		$newObj->createReference();
		$newObj->putInTree($_GET["ref_id"]);
		$newObj->setPermissions($_GET["ref_id"]);
		$newObj->notify("new",$_GET["ref_id"],$_GET["parent_non_rbac_id"],$_GET["ref_id"],$newObj->getRefId());

		// create data directory, copy file to directory
		$newObj->createDataDirectory();

		// copy uploaded file to data directory
		$file_path = $newObj->getDataDirectory()."/".$_FILES["scormfile"]["name"];
		
		ilUtil::moveUploadedFile($_FILES["scormfile"]["tmp_name"],
			$_FILES["scormfile"]["name"], $file_path);

		//move_uploaded_file($_FILES["scormfile"]["tmp_name"], $file_path);

		ilUtil::unzip($file_path);
		ilUtil::renameExecutables($newObj->getDataDirectory());

		$newObj->readObject();

	}

	function upload()
	{
		$this->uploadObject();
	}

	/**
	* save new learning module to db
	*/
	function saveObject()
	{
		global $rbacadmin;

		$this->uploadObject();

		sendInfo( $this->lng->txt("lm_added"), true);
		ilUtil::redirect($this->getReturnLocation("save","adm_object.php?".$this->link_params));

	}

	/**
	* permission form
	*/
	function perm()
	{
		$this->setFormAction("permSave", "sahs_edit.php?cmd=permSave&ref_id=".$_GET["ref_id"].
			"&obj_id=".$_GET["obj_id"]);
		$this->setFormAction("addRole", "sahs_edit.php?ref_id=".$_GET["ref_id"].
			"&obj_id=".$_GET["obj_id"]."&cmd=addRole");
		$this->permObject();
	}

	/**
	* permission form
	*/
	function info()
	{
		$this->infoObject();
	}

	/**
	* save permissions
	*/
	function permSave()
	{
		$this->setReturnLocation("permSave",
			"sahs_edit.php?ref_id=".$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]."&cmd=perm");
		$this->permSaveObject();
	}

	/**
	* add role
	*/
	function addRole()
	{
		$this->setReturnLocation("addRole",
			"sahs_edit.php?ref_id=".$_GET["ref_id"]."&obj_id=".$_GET["obj_id"]."&cmd=perm");
		$this->addRoleObject();
	}

	/**
	* show owner of learning module
	*/
	function owner()
	{
		$this->ownerObject();
	}

	/**
	* choose meta data section
	* (called by administration)
	*/
/*
	function chooseMetaSectionObject($a_target = "")
	{
		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=".$this->object->getRefId();
		}

		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_gui->edit("ADM_CONTENT", "adm_content",
			$a_target, $_REQUEST["meta_section"]);
	}
*/
	/**
	* choose meta data section
	* (called by module)
	*/
/*
	function chooseMetaSection()
	{
		$this->chooseMetaSectionObject($this->ctrl->getLinkTarget($this));
	}
*/

	/**
	* add meta data object
	* (called by administration)
	*/
/*
	function addMetaObject($a_target = "")
	{
		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=".$this->object->getRefId();
		}

		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_name = $_POST["meta_name"] ? $_POST["meta_name"] : $_GET["meta_name"];
		$meta_index = $_POST["meta_index"] ? $_POST["meta_index"] : $_GET["meta_index"];
		if ($meta_index == "")
			$meta_index = 0;
		$meta_path = $_POST["meta_path"] ? $_POST["meta_path"] : $_GET["meta_path"];
		$meta_section = $_POST["meta_section"] ? $_POST["meta_section"] : $_GET["meta_section"];
		if ($meta_name != "")
		{
			$meta_gui->meta_obj->add($meta_name, $meta_path, $meta_index);
		}
		else
		{
			sendInfo($this->lng->txt("meta_choose_element"), true);
		}
		$meta_gui->edit("ADM_CONTENT", "adm_content", $a_target, $meta_section);
	}
*/

	/**
	* add meta data object
	* (called by module)
	*/
/*
	function addMeta()
	{
		$this->addMetaObject($this->ctrl->getLinkTarget($this));
	}
*/

	/**
	* delete meta data object
	* (called by administration)
	*/
/*
	function deleteMetaObject($a_target = "")
	{
		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=".$this->object->getRefId();
		}

		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_index = $_POST["meta_index"] ? $_POST["meta_index"] : $_GET["meta_index"];
		$meta_gui->meta_obj->delete($_GET["meta_name"], $_GET["meta_path"], $meta_index);
		$meta_gui->edit("ADM_CONTENT", "adm_content", $a_target, $_GET["meta_section"]);
	}
*/

	/**
	* delete meta data object
	* (called by module)
	*/
/*
	function deleteMeta()
	{
		$this->deleteMetaObject($this->ctrl->getLinkTarget($this));
	}
*/

	/**
	* edit meta data
	* (called by administration)
	*/
/*
	function editMetaObject($a_target = "")
	{
		if ($a_target == "")
		{
			$a_target = "adm_object.php?ref_id=".$this->object->getRefId();
		}

		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_gui->edit("ADM_CONTENT", "adm_content", $a_target, $_GET["meta_section"]);
	}
*/

	/**
	* edit meta data
	* (called by module)
	*/
/*
	function editMeta()
	{
		$this->editMetaObject($this->ctrl->getLinkTarget($this));
	}
*/

	/**
	* save meta data
	* (called by administration)
	*/
/*
	function saveMetaObject($a_target = "")
	{
		if ($a_target == "")
		{
			$a_target = "adm_object.php?cmd=editMeta&ref_id=".$this->object->getRefId();
		}

		include_once "classes/class.ilMetaDataGUI.php";
		$meta_gui =& new ilMetaDataGUI();
		$meta_gui->setObject($this->object);
		$meta_gui->save($_POST["meta_section"]);
		ilUtil::redirect(ilUtil::appendUrlParameterString($a_target,
			"meta_section=" . $_POST["meta_section"]));
	}
*/

	/**
	* save meta data
	* (called by module)
	*/
/*
	function saveMeta()
	{
		$this->saveMetaObject($this->ctrl->getLinkTarget($this, "editMeta"));
	}
*/


	/**
	* output main header (title and locator)
	*/
	function getTemplate()
	{
		global $lng;

		$this->tpl->addBlockFile("CONTENT", "content", "tpl.adm_content.html");
		//$this->tpl->setVariable("HEADER", $a_header_title);
		$this->tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");
		//$this->tpl->setVariable("TXT_LOCATOR",$this->lng->txt("locator"));
	}


	/**
	* output main frameset of media pool
	* left frame: explorer tree of folders
	* right frame: media pool content
	*/
	function frameset()
	{
		$this->tpl = new ilTemplate("tpl.sahs_edit_frameset.html", false, false, "content");
		$this->tpl->setVariable("REF_ID",$this->ref_id);
		$this->tpl->show();
	}

	/**
	* set locator
	*/
	function setLocator($a_tree = "", $a_id = "", $scriptname="adm_object.php")
	{
		global $ilias_locator, $tree;
		if (!defined("ILIAS_MODULE"))
		{
			parent::setLocator();
		}
		else
		{
			$a_tree =& $tree;
			$a_id = $_GET["ref_id"];

			$this->tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html");

			$path = $a_tree->getPathFull($a_id);

			// this is a stupid workaround for a bug in PEAR:IT
			$modifier = 1;

			if (!empty($_GET["obj_id"]))
			{
				$modifier = 0;
			}

			// ### AA 03.11.10 added new locator GUI class ###
			$i = 1;

			if ($this->object->getType() != "grp" && ($_GET["cmd"] == "delete" || $_GET["cmd"] == "edit"))
			{
				unset($path[count($path) - 1]);
			}

			foreach ($path as $key => $row)
			{

				if ($key < count($path) - $modifier)
				{
					$this->tpl->touchBlock("locator_separator");
				}

				$this->tpl->setCurrentBlock("locator_item");
				if ($row["child"] != $a_tree->getRootId())
				{
					$this->tpl->setVariable("ITEM", $row["title"]);
				}
				else
				{
					$this->tpl->setVariable("ITEM", $this->lng->txt("repository"));
				}
				if($row["type"] == "sahs")
				{
					$this->tpl->setVariable("LINK_ITEM", "sahs_edit.php?ref_id=".$row["child"]."&type=".$row["type"]);
				}
				else
				{
					$this->tpl->setVariable("LINK_ITEM", "../repository.php?cmd=frameset&ref_id=".$row["child"]);
					$t_frame = ilFrameTargetInfo::_getFrame("MainContent");
					$this->tpl->setVariable("LINK_TARGET", " target=\"$t_frame\" ");
				}
				//$this->tpl->setVariable("LINK_TARGET", " target=\"bottom\" ");

				$this->tpl->parseCurrentBlock();

				$this->tpl->setCurrentBlock("locator");

				// ### AA 03.11.10 added new locator GUI class ###
				// navigate locator
				if ($row["child"] != $a_tree->getRootId())
				{
					$ilias_locator->navigate($i++,$row["title"],"../repository.php?ref_id=".$row["child"],"bottom");
				}
				else
				{
					$ilias_locator->navigate($i++,$this->lng->txt("repository"),"../repository.php?ref_id=".$row["child"],"bottom");
				}
			}

			$this->tpl->setVariable("TXT_LOCATOR",$debug.$this->lng->txt("locator"));
			$this->tpl->parseCurrentBlock();
		}

	}


	/**
	* output tabs
	*/
	function setTabs()
	{
		$this->tpl->setCurrentBlock("header_image");
		$this->tpl->setVariable("IMG_HEADER", ilUtil::getImagePath("icon_lm_b.gif"));
		$this->tpl->parseCurrentBlock();

		$this->getTabs($this->tabs_gui);
		$this->tpl->setVariable("TABS", $this->tabs_gui->getHTML());
		$this->tpl->setVariable("HEADER", $this->object->getTitle());
	}

	/**
	* adds tabs to tab gui object
	*
	* @param	object		$tabs_gui		ilTabsGUI object
	*/
	function getTabs(&$tabs_gui)
	{
		global $rbacsystem;
		
		if ($this->ctrl->getCmd() == "delete")
		{
			return;
		}

		// properties
		$tabs_gui->addTarget("properties",
			$this->ctrl->getLinkTarget($this, "properties"), "properties",
			get_class($this));

		// file system gui tabs
		if (is_object($this->fs_gui))
		{
			$this->fs_gui->getTabs($tabs_gui);
		}

		// tracking data
		$tabs_gui->addTarget("cont_tracking_data",
			$this->ctrl->getLinkTarget($this, "showTrackingItems"), "showTrackingItems",
			get_class($this));

		// edit meta
/*
		$tabs_gui->addTarget("meta_data",
			$this->ctrl->getLinkTarget($this, "editMeta"), "editMeta",
			get_class($this));
*/
		$tabs_gui->addTarget("meta_data",
			 $this->ctrl->getLinkTargetByClass('ilmdeditorgui',''),
			 "", "ilmdeditorgui");

		// perm
		if ($rbacsystem->checkAccess('edit_permission',$this->object->getRefId()))
		{
			$tabs_gui->addTarget("perm_settings",
				$this->ctrl->getLinkTarget($this, "perm"), array("perm", "info"),
				get_class($this));
		}

		// owner
/*
		$tabs_gui->addTarget("owner",
			$this->ctrl->getLinkTarget($this, "owner"), "owner",
			get_class($this));
*/
	}



} // END class.ilObjSAHSLearningModule
?>
