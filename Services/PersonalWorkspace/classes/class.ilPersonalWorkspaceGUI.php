<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* GUI class for personal workspace
*
* @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
* @version $Id: class.ilPersonalDesktopGUI.php 26976 2010-12-16 13:24:38Z akill $
*
* @ilCtrl_Calls ilPersonalWorkspaceGUI: ilObjWorkspaceRootFolderGUI, ilObjWorkspaceFolderGUI
* @ilCtrl_Calls ilPersonalWorkspaceGUI: ilObjectCopyGUI, ilObjFileGUI, ilObjBlogGUI
*/
class ilPersonalWorkspaceGUI
{
	protected $tree; // [ilTree]
	protected $node_id; // [int]
	
	/**
	 * constructor
	 */
	public function __construct()
	{
		global $ilCtrl;

		$this->initTree();

		$ilCtrl->saveParameter($this, "wsp_id");

		$this->node_id = $_REQUEST["wsp_id"];
		if(!$this->node_id)
		{
			$this->node_id = $this->tree->getRootId();
		}
	}
	
	/**
	 * execute command
	 */
	public function executeCommand()
	{
		global $ilCtrl, $ilTabs, $lng, $objDefinition;

		$ilCtrl->setReturn($this, "render");
		$cmd = $ilCtrl->getCmd();

		// new type
		if($_REQUEST["new_type"])
		{
			$class_name = $objDefinition->getClassName($_REQUEST["new_type"]);
			$ilCtrl->setCmdClass("ilObj".$class_name."GUI");
			if($cmd != "save")
			{
				$ilCtrl->setCmd("create");
				$cmd = "create";
			}
		}

		// root node
		$next_class = $ilCtrl->getNextClass();		
		if(!$next_class)
		{
			$node = $this->tree->getNodeData($this->node_id);
			$next_class = "ilObj".$objDefinition->getClassName($node["type"])."GUI";
			$ilCtrl->setCmdClass($next_class);
		}

		// current node
		$class_path = $ilCtrl->lookupClassPath($next_class);
		include_once($class_path);
		$class_name = $ilCtrl->getClassForClasspath($class_path);
		if($cmd == "create" || $cmd == "save" || $cmd == "cancelCreation")
		{
			$gui = new $class_name(null, ilObject2GUI::WORKSPACE_NODE_ID, $this->node_id);
			$gui->setCreationMode();
		}
		else
		{
			$gui = new $class_name($this->node_id, ilObject2GUI::WORKSPACE_NODE_ID, false);
		}
		$ilCtrl->forwardCommand($gui);


		$this->renderLocator();
		$this->renderTitle();

		if(($cmd == "" || $cmd == "render" || $cmd == "view") && !$_REQUEST["new_type"])
		{
			$this->renderToolbar();
		}
	}

	/**
	 * Init personal tree
	 */
	protected function initTree()
	{
		global $ilUser;

		$user_id = $ilUser->getId();

		include_once "Services/PersonalWorkspace/classes/class.ilWorkspaceTree.php";
		$this->tree = new ilWorkspaceTree($user_id);
		if(!$this->tree->readRootId())
		{
			// create (workspace) root folder
			$root = ilObjectFactory::getClassByType("wsrt");
			$root = new $root(null);
			$root->create();

			$root_id = $this->tree->createReference($root->getId());
			$this->tree->addTree($user_id, $root_id);
			$this->tree->setRootId($root_id);
		}
	}

	protected function renderTitle()
	{
		global $tpl, $lng;
		
		$root = $this->tree->getNodeData($this->node_id);
		if($root["type"] == "wsrt")
		{
			$title = $lng->txt("wsp_personal_workspace");
			$icon = ilObject::_getIcon(ROOT_FOLDER_ID, "big");
		}
		else
		{
			$title = $root["title"];
			$icon = ilObject::_getIcon($root["obj_id"], "big");
		}
		$tpl->setTitle($title);
		$tpl->setTitleIcon($icon, $title);
	}
	
	/**
	 * Render workspace toolbar (folder navigation, add subobject)
	 */
	protected function renderToolbar()
	{
		global $lng, $ilCtrl, $objDefinition, $ilToolbar;

		include_once "Services/Form/classes/class.ilPropertyFormGUI.php";
		$ilToolbar->setFormAction($ilCtrl->getFormAction($this));
	

		// folder tree

		$options = array(""=>$lng->txt("wsp_root_folder"));
		$root = $this->tree->getNodeData($this->node_id);
		$nodes = $this->tree->getSubTree($root);
		if($nodes)
		{
			// first node == root
			array_shift($nodes);

			foreach($nodes as $node)
			{
				// open
				if($objDefinition->isContainer($node["type"]))
				{
					$options[$node["wsp_id"]] = str_repeat("-", $node["depth"]-1)." ".$node["title"];
				}
			}
		}

		if(sizeof($options) > 1)
		{
			$folders = new ilSelectInputGUI($lng->txt("wsp_folders"), "wsp_id");
			$folders->setOptions($options);
			$folders->addCustomAttribute("onChange=\"forms['ilToolbar'].submit();\"");
			$ilToolbar->addInputItem($folders, "wsp_id");

			$ilToolbar->addSeparator();
		}


		// (add) subtypes
		$subtypes = $objDefinition->getCreatableSubObjects($root["type"], ilObjectDefinition::MODE_WORKSPACE);
		if($subtypes)
		{
			// :TODO: permission checks?
			$options = array(""=>"-");
			foreach(array_keys($subtypes) as $type)
			{
				$class = $objDefinition->getClassName($type);
				$options[$type] = $lng->txt("wsp_add_".$type);
			}
		
			$types = new ilSelectInputGUI($lng->txt("wsp_resource"), "new_type");
			$types->setOptions($options);
			$types->addCustomAttribute("onChange=\"forms['ilToolbar'].submit();\"");
			$ilToolbar->addInputItem($types, "new_type");
		}
	}

	/**
	 * Build locator for current node
	 */
	protected function renderLocator()
	{
		global $lng, $ilCtrl, $ilLocator, $tpl, $objDefinition;

		$ilLocator->clearItems();
		
		$path = $this->tree->getPathFull($this->node_id);
		foreach($path as $node)
		{
			$obj_class = "ilObj".$objDefinition->getClassName($node["type"])."GUI";

			$ilCtrl->setParameter($this, "wsp_id", $node["wsp_id"]);

			switch($node["type"])
			{			
				case "wsrt":
					$ilLocator->addItem($lng->txt("wsp_personal_workspace"), $ilCtrl->getLinkTargetByClass($obj_class, "render"));
					break;

				case "blog":
				case $objDefinition->isContainer($node["type"]):
					$ilLocator->addItem($node["title"], $ilCtrl->getLinkTargetByClass($obj_class, "render"));
					break;

				default:
					$ilLocator->addItem($node["title"], $ilCtrl->getLinkTargetByClass($obj_class, "edit"));
					break;
			}
		}

		$tpl->setLocator();
		$ilCtrl->setParameter($this, "wsp_id", $this->node_id);
	}
}

?>