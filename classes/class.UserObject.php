<?php
/**
 * Class UserObject
 * @extends class.Object.php
 * @author Stefan Meyer <smeyer@databay.de> 
 * @version $Id$ 
 * @package ilias-core
 * 
*/
include_once("classes/class.Object.php");

class UserObject extends Object
{
	var $gender;

	function UserObject(&$a_ilias)
	{
		$this->Object($a_ilias);
		$this->gender = array(
			'm'    => 'Herr',
			'f'    => 'Frau');
	}
	function createObject()
	{
		global $tree;
		global $tplContent;

		$obj = getObject($_GET["obj_id"]);
		$rbacsystem = new RbacSystemH($this->ilias->db);

		if($rbacsystem->checkAccess('write',$_GET["obj_id"],$_GET["parent"]))
		{
			$tplContent = new Template("user_form.html",true,true);
			$tplContent->setVariable($this->ilias->ini["layout"]); 

			$tplContent->setVariable("STATUS","Add User");
			$tplContent->setVariable("CMD","save");
			$tplContent->setVariable("TYPE","user");
			$tplContent->setVariable("OBJ_ID",$_GET["obj_id"]);
			$tplContent->setVariable("TPOS",$_GET["parent"]);

			// set Path
			$tplContent->setVariable("TREEPATH",$this->getPath());

			// gender selection
			$tplContent->setCurrentBlock("gender");
			$opts = TUtil::formSelect($Fuserdata["Gender"],"Fuserdata[Gender]",$this->gender);
			$tplContent->setVariable("GENDER",$opts);
			$tplContent->parseCurrentBlock();

			// role selection
			$tplContent->setCurrentBlock("role");
			$role = TUtil::getRoles();
			$opts = TUtil::formSelect($Fuserdata["Role"],"Fuserdata[Role]",$role);
			$tplContent->setVariable("ROLE",$opts);
			$tplContent->parseCurrentBlock();

			$tplContent->setVariable("USR_ID",$_GET["obj_id"]);
			$tplContent->setVariable("USR_LOGIN",$Fuserdata["Login"]);
			$tplContent->setVariable("USR_PASSWD",$Fuserdata["Passwd"]);
			$tplContent->setVariable("USR_TITLE",$Fuserdata["Title"]);
			$tplContent->setVariable("USR_FIRSTNAME",$Fuserdata["FirstName"]);
			$tplContent->setVariable("USR_SURNAME",$Fuserdata["SurName"]);
			$tplContent->setVariable("USR_EMAIL",$Fuserdata["Email"]);
		}
		else
		{
			// NO ACCESS TO WRITE TO USER FOLDER
			$_SESSION["Error_Message"] = "No permission to write to user folder" ;
			header("Location: content.php?obj_id=$_GET[obj_id]&parent=$_GET[parent]");
			exit();
		}
	}
	function saveObject()
	{
		$Fuserdata = $_POST["Fuserdata"];
		$rbacsystem = new RbacSystemH($this->ilias->db);
		$rbacadmin = new RbacAdminH($this->ilias->db);

		if($rbacsystem->checkAccess('write',$_GET["obj_id"].$_GET["parent"]))
		{
			// create object
			$Fobject["title"] = User::buildFullName($Fuserdata["Title"],$Fuserdata["FirstName"],$Fuserdata["SurName"]);
			$Fobject["desc"] = "nix";
			$Fuserdata["Id"] = createNewObject("user",$Fobject);

			// insert user data
			$rbacadmin->addUser($Fuserdata);
			$rbacadmin->assignUser($Fuserdata["Role"],$Fuserdata["Id"]);
		}
		else
		{
			// NO ACCESS TO WRITE TO USER FOLDER
			$_SESSION["Error_Message"] = "No permission to write to user folder" ;
			header("Location: content.php?obj_id=$_GET[obj_id]&parent=$_GET[parent]");
			exit();
		}
		header("Location: content.php?obj_id=$_GET[obj_id]&parent=$_GET[parent]");
	}
	function deleteObject()
	{
		$rbacadmin = new RbacAdminH($this->ilias->db);
		$rbacsystem = new RbacSystemH($this->ilias->db);
		
		// CHECK ACCESS
		if($rbacsystem->checkAccess('write',$_GET["obj_id"],$_GET["parent"]))
		{
			$rbacadmin->deleteUser($_POST["id"]);
		}
		else
		{
			$_SESSION["Error_Message"] = "No permission to delete User";
			header("Location: content.php?obj_id=$_GET[obj_id]&parent=$_GET[parent]");
			exit();
		}
		header("Location: content_user.php?obj_id=$_GET[obj_id]&parent=$_GET[parent]");
	}
	function editObject()
	{
		global $tree;
		global $tplContent;

		$rbacsystem = new RbacSystemH($this->ilias->db);
		$parent_obj_id = $this->getParentObjectId();

		if($rbacsystem->checkAccess('write',$_GET["parent"],$parent_obj_id))
		{
			// Userobjekt erzeugen
			$user = new User($this->ilias->db,$_GET["obj_id"]);
			
			$tplContent = new Template("user_form.html",true,true);
			$tplContent->setVariable($this->ilias->ini["layout"]);
			$tplContent->setVariable("OBJ_ID",$_GET["obj_id"]);
			$tplContent->setVariable("TPOS",$_GET["parent"]);
			$tplContent->setVariable("CMD","update");
			$tplContent->setVariable("TYPE","user");

			// display path
			$tree = new Tree($_GET["parent"],1,1);
			$tree->getPath();
			$path = showPath($tree->Path,"content.php");
			$tplContent->setVariable("TREEPATH",$path);

			// gender selection
			$tplContent->setCurrentBlock("gender");
			$opts = TUtil::formSelect($Fuserdata["Gender"],"Fuserdata[Gender]",$this->gender);
			$tplContent->setVariable("GENDER",$opts);
			$tplContent->parseCurrentBlock();	

			// role selection
			$tplContent->setCurrentBlock("role");
			$role = TUtil::getRoles();
			$opts = TUtil::formSelect($Fuserdata["Role"],"Fuserdata[Role]",$role);
			$tplContent->setVariable("ROLE",$opts);
			$tplContent->parseCurrentBlock();
	
			$tplContent->setVariable("USR_ID",$_GET["obj_id"]);
			$tplContent->setVariable("USR_LOGIN",$user->data["login"]);
			$tplContent->setVariable("USR_PASSWD","******");
			$tplContent->setVariable("USR_TITLE",$user->data["Title"]);
			$tplContent->setVariable("USR_FIRSTNAME",$user->data["FirstName"]);
			$tplContent->setVariable("USR_SURNAME",$user->data["SurName"]);
			$tplContent->setVariable("USR_EMAIL",$user->data["Email"]);
		}
		else
		{
			$_SESSION["Error_Message"] = "No permission to edit user folder";
			header("Location: content.php?obj_id=$_GET[parent]&parent=$parent_obj_id");
			exit();
		}
	}
	function updateObject()
	{
		$Fuserdata = $_POST["Fuserdata"];

		$rbacadmin = new RbacAdminH($this->ilias->db);
		$rbacsystem = new RbacSystemH($this->ilias->db);

		$parent_obj_id = $this->getParentObjectId();
		if($rbacsystem->checkAccess('write',$_GET["parent"],$parent_obj_id))
		{
			$rbacadmin->updateUser($Fuserdata);
			$rbacadmin->assignUser($Fuserdata["Role"],$_GET["obj_id"]);
			// TODO: Passwort muss gesondert abgefragt werden
		}
		else
		{
			$_SESSION["Error_Message"] = "No permission to delete User";
			header("Location: content.php?obj_id=$_GET[obj_id]&parent=$_GET[parent]");
			exit();
		}
		header("Location: content_user.php?obj_id=$_GET[parent]&parent=$this->SYSTEM_FOLDER_ID");
	}

}
?>