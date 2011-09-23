<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Workspace deep link handler GUI
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @version $Id$
 * 
 * @ilCtrl_Calls ilSharedResourceGUI: ilObjBlogGUI, ilObjFileGUI, ilObjTestVerificationGUI
 * @ilCtrl_Calls ilSharedResourceGUI: ilObjExerciseVerificationGUI, ilObjLinkResourceGUI
 * @ilCtrl_Calls ilSharedResourceGUI: ilObjPortfolioGUI
 *
 * @ingroup ServicesPersonalWorkspace
 */
class ilSharedResourceGUI
{
	protected $node_id;
	protected $portfolio_id;
	protected $access_handler;	

	function __construct()
	{
		global $ilCtrl;
		
		$ilCtrl->saveParameter($this, "wsp_id");
		$ilCtrl->saveParameter($this, "prt_id");
		$this->node_id = $_GET["wsp_id"];			
		$this->portfolio_id = $_GET["prt_id"];			
	}
	
	function executeCommand()
	{
		global $ilCtrl, $tpl;
		
		$next_class = $ilCtrl->getNextClass($this);
		$cmd = $ilCtrl->getCmd();
		
		$tpl->getStandardTemplate();
		
		switch($next_class)
		{
			case "ilobjbloggui":
				include_once "Modules/Blog/classes/class.ilObjBlogGUI.php";
				$bgui = new ilObjBlogGUI($this->node_id, ilObject2GUI::WORKSPACE_NODE_ID);				
				$ilCtrl->forwardCommand($bgui);			
				break;
			
			case "ilobjfilegui":
				include_once "Modules/File/classes/class.ilObjFileGUI.php";
				$fgui = new ilObjFileGUI($this->node_id, ilObject2GUI::WORKSPACE_NODE_ID);
				$ilCtrl->forwardCommand($fgui);
				break;		
			
			case "ilobjtestverificationgui":
				include_once "Modules/Test/classes/class.ilObjTestVerificationGUI.php";
				$tgui = new ilObjTestVerificationGUI($this->node_id, ilObject2GUI::WORKSPACE_NODE_ID);
				$ilCtrl->forwardCommand($tgui);
				break;		
			
			case "ilobjexerciseverificationgui":
				include_once "Modules/Exercise/classes/class.ilObjExerciseVerificationGUI.php";
				$egui = new ilObjExerciseVerificationGUI($this->node_id, ilObject2GUI::WORKSPACE_NODE_ID);
				$ilCtrl->forwardCommand($egui);
				break;		
			
			case "ilobjlinkresourcegui":
				include_once "Modules/WebResource/classes/class.ilObjLinkResourceGUI.php";
				$lgui = new ilObjLinkResourceGUI($this->node_id, ilObject2GUI::WORKSPACE_NODE_ID);
				$ilCtrl->forwardCommand($lgui);
				break;		
			
			default:
				if(!$cmd)
				{
					$cmd = "process";
				}
				$this->$cmd();
		}
		
		$tpl->show();
	}
	
	protected function process()
	{
		global $ilUser, $ilCtrl;
		
		if(!$this->node_id && !$this->portfolio_id)
		{
			exit("invalid call");
		}
			
		// if already logged in, we need to re-check for public password
		if($this->node_id)
		{
			if(!self::hasAccess($this->node_id))
			{
				exit("no permission");
			}
			$this->redirectToResource($this->node_id);	     
		}	
		else
		{
			if(!self::hasAccess($this->portfolio_id, true))
			{
				exit("no permission");
			}
			$this->redirectToResource($this->portfolio_id, true);	     
		}						
	}
	
	public static function hasAccess($a_node_id, $a_is_portfolio = false)
	{
		global $ilCtrl, $ilUser;				
	
		// if we have current user - check with normal access handler
		if($ilUser->getId() != ANONYMOUS_USER_ID)
		{			
			if(!$a_is_portfolio)
			{
				include_once "Services/PersonalWorkspace/classes/class.ilWorkspaceAccessHandler.php";
				include_once "Services/PersonalWorkspace/classes/class.ilWorkspaceTree.php";			
				$tree = new ilWorkspaceTree($ilUser->getId());
				$access_handler = new ilWorkspaceAccessHandler($tree);
			}
			else
			{
				include_once "Services/Portfolio/classes/class.ilPortfolioAccessHandler.php";
				$access_handler = new ilPortfolioAccessHandler();
			}			
			if($access_handler->checkAccess("read", "", $a_node_id))
			{
				return true;
			}
		}
		
		// not logged in yet or no read access
		include_once "Services/PersonalWorkspace/classes/class.ilWorkspaceAccessGUI.php";
			
		if(!$a_is_portfolio)
		{
			include_once "Services/PersonalWorkspace/classes/class.ilWorkspaceAccessHandler.php";	
			$shared = ilWorkspaceAccessHandler::getPermissions($a_node_id);
		}
		else
		{
			include_once "Services/Portfolio/classes/class.ilPortfolioAccessHandler.php";
			$shared = ilPortfolioAccessHandler::getPermissions($a_node_id);
			
		}

		// object is "public"
		if(in_array(ilWorkspaceAccessGUI::PERMISSION_ALL, $shared))
		{
			return true;
		}

		// password protected
		if(in_array(ilWorkspaceAccessGUI::PERMISSION_ALL_PASSWORD, $shared))
		{
			if(!$a_is_portfolio)
			{
				ilUtil::redirect("ilias.php?baseClass=ilSharedResourceGUI&cmd=passwordForm&wsp_id=".$a_node_id);
			}
			else
			{
				ilUtil::redirect("ilias.php?baseClass=ilSharedResourceGUI&cmd=passwordForm&prt_id=".$a_node_id);
			}
		}		
		
		return false;
	}
	
	protected function redirectToResource($a_node_id, $a_is_portfolio = false)
	{
		global $ilCtrl, $objDefinition, $ilUser;
				
		if(!$a_is_portfolio)
		{
			$object_data = $this->getObjectDataFromNode($a_node_id);
			if(!$object_data["obj_id"])
			{
				exit("invalid object");
			}
		}
		else
		{			
			if(!ilObject::_lookupType($a_node_id, false))
			{
				exit("invalid object");
			}
			$object_data["obj_id"] = $a_node_id;
			$object_data["type"] = "prtf";
		}
		
		/* currently unwanted feature
		// if user owns target object, go to workspace directly
		// e.g. deep-linking notices from personal desktop
		if($ilUser->getId() == ilObject::_lookupOwner($object_data["obj_id"]))
		{
			if(!$a_is_portfolio)
			{
				// blog posting
				if($_GET["gtp"])
				{
					$gtp = "&gtp=".(int)$_GET["gtp"];
				}
				ilUtil::redirect("ilias.php?baseClass=ilpersonaldesktopgui&cmd=jumptoworkspace&wsp_id=".$a_node_id.$gtp);
			}
			else
			{
				ilUtil::redirect("ilias.php?baseClass=ilpersonaldesktopgui&cmd=jumptoportfolio&prt_id=".$a_node_id);
			}
		}		 
		*/
		
		$class = $objDefinition->getClassName($object_data["type"]);
		$gui = "ilobj".$class."gui";
		
		switch($object_data["type"])
		{
			case "blog":
				$ilCtrl->setParameterByClass($gui, "wsp_id", $a_node_id);
				$ilCtrl->setParameterByClass($gui, "gtp", $_GET["gtp"]);
				$ilCtrl->redirectByClass($gui, "preview");
				
			case "tstv":
			case "excv":
				$ilCtrl->setParameterByClass($gui, "wsp_id", $a_node_id);
				$ilCtrl->redirectByClass($gui, "deliver");
				
			case "file":
			case "webr":
				$ilCtrl->setParameterByClass($gui, "wsp_id", $a_node_id);
				$ilCtrl->redirectByClass($gui);
				
			case "prtf":
				ilUtil::redirect("ilias.php?baseClass=ilpersonaldesktopgui&cmd=jumptoportfolio&prt_id=".$a_node_id);
				
			default:
				exit("invalid object type");						
		}		
	}
	
	protected function getObjectDataFromNode($a_node_id)
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT obj.obj_id, obj.type, obj.title".
			" FROM object_reference_ws ref".
			" JOIN tree_workspace tree ON (tree.child = ref.wsp_id)".
			" JOIN object_data obj ON (ref.obj_id = obj.obj_id)".
			" WHERE ref.wsp_id = ".$ilDB->quote($a_node_id, "integer"));
		return $ilDB->fetchAssoc($set);
	}
	
	protected function passwordForm($form = null)
	{
		global $tpl, $lng;
		
		$lng->loadLanguageModule("wsp");
		
		$tpl->setTitle($lng->txt("wsp_password_protected_resource"));
		$tpl->setDescription($lng->txt("wsp_password_protected_resource_info"));
		
		if(!$form)
		{
			$form = $this->initPasswordForm();
		}
	
		$tpl->setContent($form->getHTML());		
	}
	
	protected function initPasswordForm()
	{
		global $ilCtrl, $lng;
		
		if($this->node_id)
		{
			$object_data = $this->getObjectDataFromNode($this->node_id);
		}
		else
		{
			$object_data["title"] = ilObject::_lookupTitle($this->portfolio_id);
		}
		
		include_once "Services/Form/classes/class.ilPropertyFormGUI.php";
		$form = new ilPropertyFormGUI();
		$form->setFormAction($ilCtrl->getFormAction($this));
		$form->setTitle($lng->txt("wsp_password_for").": ".$object_data["title"]);
		
		$password = new ilTextInputGUI($lng->txt("password"), "password");
		$password->setRequired(true);
		$form->addItem($password);
		
		$form->addCommandButton('checkPassword', $lng->txt("submit"));
		
		return $form;
	}
	
	protected function checkPassword()
	{
		global $ilDB, $lng;
		 
		$form = $this->initPasswordForm();
		if($form->checkInput())
		{
			$input = md5($form->getInput("password"));			
			if($this->node_id)
			{
				include_once "Services/PersonalWorkspace/classes/class.ilWorkspaceAccessHandler.php";
				$password = ilWorkspaceAccessHandler::getSharedNodePassword($this->node_id);
			}
			else
			{
				include_once "Services/Portfolio/classes/class.ilPortfolioAccessHandler.php";
				$password = ilPortfolioAccessHandler::getSharedNodePassword($this->portfolio_id);
			}
			if($input == $password)
			{
				if($this->node_id)
				{
					ilWorkspaceAccessHandler::keepSharedSessionPassword($this->node_id, $input);		
					$this->redirectToResource($this->node_id);
				}
				else
				{
					ilPortfolioAccessHandler::keepSharedSessionPassword($this->portfolio_id, $input);		
					$this->redirectToResource($this->portfolio_id, true);
				}				
			}
			else
			{
				$item = $form->getItemByPostVar("password");
				$item->setAlert($lng->txt("wsp_invalid_password"));
				ilUtil::sendFailure($lng->txt("form_input_not_valid"));
			}						
		}		
		
		$form->setValuesByPost();
		$this->passwordForm($form);
	}
}

?>