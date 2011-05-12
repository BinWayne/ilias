<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once "Services/Portfolio/classes/class.ilPortfolio.php";
include_once "Modules/Group/classes/class.ilGroupParticipants.php";
include_once "Modules/Course/classes/class.ilCourseParticipants.php";

/**
 * Access handler for portfolio
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @version $Id: class.ilPersonalDesktopGUI.php 26976 2010-12-16 13:24:38Z akill $
 * 
 * @ingroup ServicesPortfolio
 */
class ilPortfolioAccessHandler
{
	public function __construct()
	{
		global $lng;
		$lng->loadLanguageModule("wsp");
	}

	/**
	 * check access for an object
	 *
	 * @param	string		$a_permission
	 * @param	string		$a_cmd
	 * @param	int			$a_node_id
	 * @param	string		$a_type (optional)
	 * @return	bool
	 */
	public function checkAccess($a_permission, $a_cmd, $a_node_id, $a_type = "")
	{
		global $ilUser;

		return $this->checkAccessOfUser($ilUser->getId(),$a_permission, $a_cmd, $a_node_id, $a_type);
	}

	/**
	 * check access for an object
	 *
	 * @param	integer		$a_user_id
	 * @param	string		$a_permission
	 * @param	string		$a_cmd
	 * @param	int			$a_node_id
	 * @param	string		$a_type (optional)
	 * @return	bool
	 */
	public function checkAccessOfUser($a_user_id, $a_permission, $a_cmd, $a_node_id, $a_type = "")
	{
		global $rbacreview;

		// :TODO: create permission for parent node with type ?!
		
		$pf = new ilPortfolio($a_node_id);
		if(!$pf->getId())
		{
			return false;
		}
		
		// portfolio owner has all rights
		if($pf->getUserId() == $a_user_id)
		{
			return true;
		}

		// get all objects with explicit permission
		$objects = $this->getPermissions($a_node_id);
		if($objects)
		{
			// check if given user is member of object or has role
			foreach($objects as $obj_id)
			{
				switch(ilObject::_lookupType($obj_id))
				{
					case "grp":
						// member of group?
						if(ilGroupParticipants::_getInstanceByObjId($obj_id)->isAssigned($a_user_id))
						{
							return true;
						}
						break;

					case "crs":
						// member of course?
						if(ilCourseParticipants::_getInstanceByObjId($obj_id)->isAssigned($a_user_id))
						{
							return true;
						}
						break;

					case "role":
						// has role?
						if($rbacreview->isAssigned($a_user_id, $obj_id))
						{
							return true;
						}
						break;

					case "usr":
						// direct assignment
						if($a_user_id == $obj_id)
						{
							return true;
						}
						break;
				}
			}
		}
		
		return false;
	}

	/**
	 * Set permissions after creating node/object
	 * 
	 * @param int $a_parent_node_id
	 * @param int $a_node_id
	 */
	public function setPermissions($a_parent_node_id, $a_node_id)
	{
		// nothing to do as owner has irrefutable rights to any portfolio object
	}

	/**
	 * Add permission to node for object
	 *
	 * @param int $a_node_id
	 * @param int $a_object_id
	 */
	public function addPermission($a_node_id, $a_object_id)
	{
		global $ilDB, $ilUser;

		// current owner must not be added
		if($a_object_id == $ilUser->getId())
		{
			return;
		}

		$ilDB->manipulate("INSERT INTO usr_portf_acl (node_id, object_id)".
			" VALUES (".$ilDB->quote($a_node_id, "integer").", ".
			$ilDB->quote($a_object_id, "integer").")");
	}

	/**
	 * Remove permission[s] (for object) to node
	 *
	 * @param int $a_node_id
	 * @param int $a_object_id 
	 */
	public function removePermission($a_node_id, $a_object_id = null)
	{
		global $ilDB;
		
		$query = "DELETE FROM usr_portf_acl".
			" WHERE node_id = ".$ilDB->quote($a_node_id, "integer");

		if($a_object_id)
		{
			$query .= " AND object_id = ".$ilDB->quote($a_object_id, "integer");
		}

		return $ilDB->manipulate($query);
	}

	/**
	 * Get all permissions to node
	 *
	 * @param int $a_node_id
	 * @return array
	 */
	public function getPermissions($a_node_id)
	{
		global $ilDB;

		$set = $ilDB->query("SELECT object_id FROM usr_portf_acl".
			" WHERE node_id = ".$ilDB->quote($a_node_id, "integer"));
		$res = array();
		while($row = $ilDB->fetchAssoc($set))
		{
			$res[] = $row["object_id"];
		}
		return $res;
	}
	
	public function hasRegisteredPermission($a_node_id)
	{
		global $ilDB;

		$set = $ilDB->query("SELECT object_id FROM usr_portf_acl".
			" WHERE node_id = ".$ilDB->quote($a_node_id, "integer").
			" AND object_id = ".$ilDB->quote(ilWorkspaceAccessGUI::PERMISSION_REGISTERED, "integer"));
		return (bool)$ilDB->numRows($set);
	}
	
	public function hasGlobalPermission($a_node_id)
	{
		global $ilDB;

		$set = $ilDB->query("SELECT object_id FROM usr_portf_acl".
			" WHERE node_id = ".$ilDB->quote($a_node_id, "integer").
			" AND object_id = ".$ilDB->quote(ilWorkspaceAccessGUI::PERMISSION_ALL, "integer"));
		return (bool)$ilDB->numRows($set);
	}
}

?>