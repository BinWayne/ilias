<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
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

/** 
* @author Stefan Meyer <smeyer@databay.de>
* @version $Id$
* 
* 
* @ingroup ServicesLDAP
*/

class ilLDAPRoleGroupMappingSettings
{
	private static $instances = array();
	private $server_id = null;
	private $db = null;
	private $mappings = null;
	
	/**
	 * Private constructor (Singleton for each server_id)
	 *
	 * @access private
	 * 
	 */
	private function __construct($a_server_id)
	{
		global $ilDB,$lng;
		
		$this->db = $ilDB;
		$this->lng = $lng;
		$this->server_id = $a_server_id;
		$this->read(); 	
	}
	
	/**
	 * Get instance of class
	 *
	 * @access public
	 * @param int server_id
	 * 
	 */
	public static function _getInstanceByServerId($a_server_id)
	{
	 	if(array_key_exists($a_server_id,self::$instances) and is_object(self::$instances[$a_server_id]))
	 	{
	 		return self::$instances[$a_server_id];
	 	}
		return self::$instances[$a_server_id] = new ilLDAPRoleGroupMappingSettings($a_server_id);
	}
	
	public static function _deleteByRole($a_role_id)
	{
		global $ilDB;
		
		$query = "DELETE FROM ldap_role_group_mapping ".
			"WHERE role = ".$ilDB->quote($a_role_id);
		$ilDB->query($query);
		
		return true;	
	}
	
	public static function _deleteByServerId($a_server_id)
	{
		global $ilDB;
		
		$query = "DELETE FROM ldap_role_group_mapping ".
			"WHERE server_id = ".$ilDB->quote($a_server_id);
			
		$ilDB->query($query);
		return true;
	}
	
	public static function _getAllActiveMappings()
	{
		global $ilDB,$rbacreview;
		
		$query = "SELECT rgm.* FROM ldap_role_group_mapping as rgm JOIN ldap_server_settings as lss ".
			"ON rgm.server_id = lss.server_id ".
			"WHERE lss.active = 1 ".
			"AND lss.role_sync_active = 1 ";
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$data['server_id']		= $row->server_id;
			$data['url']			= $row->url;
			$data['mapping_id']		= $row->mapping_id;
			$data['dn']				= $row->dn;
			$data['member']			= $row->member_attribute;
			$data['isdn']			= $row->member_isdn;
			$data['info']			= $row->mapping_info;
			// read assigned object
			$data['object_id'] 		= $rbacreview->getObjectOfRole($row->role);
			
			
			$active[$row->role][] = $data;
		}
		return $active ? $active : array();
	}
	
	public function getServerId()
	{
		return $this->server_id;
	}
	
	/**
	 * Get already configured mappings
	 *
	 * @access public
	 * 
	 */
	public function getMappings()
	{
		return $this->mappings ? $this->mappings : array();
	}
	
	public 
	
	public function loadFromPost($a_mappings)
	{
		global $rbacreview;
		
		$this->mappings = array();
		foreach($a_mappings as $mapping_id => $data)
		{
			if($mapping_id == 0)
			{
				if(!$data['dn'] and !$data['member'] and !$data['memberisdn'] and !$data['role'])
				{
					continue;
				}
			}
			$this->mappings[$mapping_id]['dn'] = ilUtil::stripSlashes($data['dn']);
			$this->mappings[$mapping_id]['url'] = ilUtil::stripSlashes($data['url']);
			$this->mappings[$mapping_id]['member_attribute'] = ilUtil::stripSlashes($data['member']);
			$this->mappings[$mapping_id]['member_isdn'] = ilUtil::stripSlashes($data['memberisdn']);
			$this->mappings[$mapping_id]['role_name'] = ilUtil::stripSlashes($data['role']);
			$this->mappings[$mapping_id]['role'] = $rbacreview->roleExists(ilUtil::stripSlashes($data['role']));
			$this->mappings[$mapping_id]['info'] = ilUtil::stripSlashes($data['info']);
		}
	}
	
	/**
	 * Validate mappings
	 *
	 * @access public
	 * 
	 */
	public function validate()
	{
		global $ilErr,$rbacreview;
		
		$ilErr->setMessage('');
		$found_missing = false;
	 	foreach($this->mappings as $mapping_id => $data)
	 	{
			// Check if all required fields are available
			if(!strlen($data['dn']) || !strlen($data['member_attribute']) || !strlen($data['role_name']))
			{
				if(!$found_missing)
				{
					$found_missing = true;
					$ilErr->appendMessage($this->lng->txt('fill_out_all_required_fields'));
				}
			}
			// Check role valid
			if(strlen($data['role_name']) and !$rbacreview->roleExists($data['role_name']))
			{
				$ilErr->appendMessage($this->lng->txt('ldap_role_not_exists').' '.$data['role_name']);
			}
	 	}
	 	return strlen($ilErr->getMessage()) ? false : true;
	}
	
	/**
	 * Save mappings
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function save()
	{
	 	foreach($this->mappings as $mapping_id => $data)
	 	{
	 		if(!$mapping_id)
	 		{
			 	$query = "INSERT INTO ldap_role_group_mapping ".
		 			"SET server_id = ".$this->db->quote($this->getServerId()).", ".
		 			"url = ".$this->db->quote($data['url']).", ".
	 				"dn = ".$this->db->quote($data['dn']).", ".
	 				"member_attribute = ".$this->db->quote($data['member_attribute']).", ".
	 				"member_isdn = ".$this->db->quote($data['member_isdn']).", ".
	 				"role = ".$this->db->quote($data['role']).", ".
	 				"mapping_info = ".$this->db->quote($data['info']); 
	 				
	 		
	 			$this->db->query($query);
	 		}
	 		else
	 		{
			 	$query = "UPDATE ldap_role_group_mapping ".
		 			"SET server_id = ".$this->db->quote($this->getServerId()).", ".
		 			"url = ".$this->db->quote($data['url']).", ".
	 				"dn =".$this->db->quote($data['dn']).", ".
	 				"member_attribute = ".$this->db->quote($data['member_attribute']).", ".
	 				"member_isdn = ".$this->db->quote($data['member_isdn']).", ".
	 				"role = ".$this->db->quote($data['role']).", ".
	 				"mapping_info = ".$this->db->quote($data['info'])." ".
	 				"WHERE mapping_id = ".$this->db->quote($mapping_id);

	 			$this->db->query($query);
	 		}
	 	}
	 	$this->read();
	}
	
	
	/**
	 * Delete a mapping
	 *
	 * @access public
	 * @param int mapping_id
	 * 
	 */
	public function delete($a_mapping_id)
	{
	 	$query = "DELETE FROM ldap_role_group_mapping ".
	 		"WHERE server_id = ".$this->db->quote($this->getServerId())." ".
	 		"AND mapping_id = ".$this->db->quote($a_mapping_id);
	 	$this->db->query($query);
			
		$this->read();
	}
	
	
	/**
	 * Create an info string for a role group mapping
	 *
	 * @access public
	 * @param int mapping_id
	 */
	public function getMappingInfoString($a_mapping_id)
	{
		$role = $this->mappings[$a_mapping_id]['role_name'];
		$dn_parts = explode(',',$this->mappings[$a_mapping_id]['dn']);
		
		return (array_key_exists(0,$dn_parts) ? $dn_parts[0] : "''");
	}
	
	
	/**
	 * Read mappings
	 *
	 * @access private
	 * 
	 */
	private function read()
	{
		global $ilObjDataCache,$rbacreview,$tree;
		
		$this->mappings = array();
	 	$query = "SELECT * FROM ldap_role_group_mapping LEFT JOIN object_data ".
	 		"ON role = obj_id ".
	 		"WHERE server_id =".$this->db->quote($this->getServerId()).' '.
	 		"ORDER BY title,dn";
			
		$res = $this->db->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->mappings[$row->mapping_id]['dn'] 					= $row->dn;
			$this->mappings[$row->mapping_id]['url']					= $row->url;
			$this->mappings[$row->mapping_id]['member_attribute'] 		= $row->member_attribute;
			$this->mappings[$row->mapping_id]['member_isdn'] 			= $row->member_isdn;
			$this->mappings[$row->mapping_id]['role']					= $row->role;
			$this->mappings[$row->mapping_id]['info']					= $row->mapping_info;
			if($ilObjDataCache->lookupType($row->role) == 'role')
			{
				$this->mappings[$row->mapping_id]['role_name']			= $ilObjDataCache->lookupTitle($row->role);
			}
			else
			{
				$this->mappings[$row->mapping_id]['role_name']			= $row->role;
			}
		
		}
	}
	
}

?>