<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2009 ILIAS open source, University of Cologne            |
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
* Component definition reader (reads common tags in module.xml and service.xml files)
* Name is misleading and should be ilComponentDefReader instead.
*
* Reads reads module information of modules.xml files into db
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
*/
class ilObjDefReader extends ilSaxParser
{
	function ilObjDefReader($a_path, $a_name, $a_type)
	{
		$this->name = $a_name;
		$this->type = $a_type;
//echo "<br>-".$a_path."-".$this->name."-".$this->type."-";
		parent::ilSaxParser($a_path);
	}
	
	function setHandlers($a_xml_parser)
	{
		xml_set_object($a_xml_parser,$this);
		xml_set_element_handler($a_xml_parser,'handlerBeginTag','handlerEndTag');
		xml_set_character_data_handler($a_xml_parser,'handlerCharacterData');
	}

	/**
	* clear the tables
	*/
	static function clearTables()
	{
		global $ilDB;

		$st = $ilDB->prepareManip("DELETE FROM il_object_def");
		$ilDB->execute($st);
		
		$st = $ilDB->prepareManip("DELETE FROM il_object_subobj");
		$ilDB->execute($st);
		
		$st = $ilDB->prepareManip("DELETE FROM il_object_group");
		$ilDB->execute($st);

		$st = $ilDB->prepareManip("DELETE FROM il_pluginslot");
		$ilDB->execute($st);
		
		$st = $ilDB->prepareManip("DELETE FROM il_component");
		$ilDB->execute($st);
	}

	/**
	* Delete an object definition (this is currently needed for test cases)
	*/
	static function deleteObjectDefinition($a_id)
	{
		global $ilDB;

		$st = $ilDB->prepareManip("DELETE FROM il_object_def WHERE id = ?",
			array("text"));
		$ilDB->execute($st, array($a_id));
		
		$st = $ilDB->prepareManip("DELETE FROM il_object_subobj WHERE parent = ? OR subobj = ?",
			array("text", "text"));
		$ilDB->execute($st, array($a_id, $a_id));
	}
	
	/**
	* start tag handler
	*
	* @param	ressouce	internal xml_parser_handler
	* @param	string		element tag name
	* @param	array		element attributes
	* @access	private
	*/
	function handlerBeginTag($a_xml_parser,$a_name,$a_attribs)
	{
		global $ilDB;

		$this->current_tag = $a_name;
		
		switch ($a_name)
		{
			case 'object':
				$this->current_object = $a_attribs["id"];
				$st = $ilDB->prepareManip("INSERT INTO il_object_def (id, class_name, component,location,".
					"checkbox,inherit,translate,devmode,allow_link,allow_copy,rbac,default_pos,default_pres_pos,sideblock,grp,system) VALUES ".
					"(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
					array("text", "text", "text", "text", "integer", "integer", "text", "integer","integer","integer",
						"integer","integer","integer","integer", "text", "integer"));
//echo "<br>-insert-".$a_attribs["id"];
				$ilDB->execute($st, array(
					$a_attribs["id"],
					$a_attribs["class_name"],
					$this->current_component,
					$this->current_component."/".$a_attribs["dir"],
					(int) $a_attribs["checkbox"],
					(int) $a_attribs["inherit"],
					$a_attribs["translate"]."",
					(int) $a_attribs["devmode"],
					(int) $a_attribs["allow_link"],
					(int) $a_attribs["allow_copy"],
					(int) $a_attribs["rbac"],
					(int) $a_attribs["default_pos"],
					(int) $a_attribs["default_pres_pos"],
					(int) $a_attribs["sideblock"],
					$a_attribs["group"]."",
					(int) $a_attribs["system"]));
				break;
			
			case "subobj":
				$st = $ilDB->prepareManip("INSERT INTO il_object_subobj (parent, subobj, mmax) VALUES (?,?,?)",
					array("text", "text", "integer"));
				$t = $ilDB->execute($st, array($this->current_object, $a_attribs["id"], (int) $a_attribs["max"]));
				break;

			case "parent":
				$st = $ilDB->prepareManip("INSERT INTO il_object_subobj (parent, subobj, mmax) VALUES (?,?,?)",
					array("text", "text", "integer"));
				$ilDB->execute($st, array($a_attribs["id"], $this->current_object, (int) $a_attribs["max"]));
				break;

			case "objectgroup":
				$st = $ilDB->prepareManip("INSERT INTO il_object_group (id, name, default_pres_pos) VALUES (?,?,?)",
					array("text", "text", "integer"));
				$ilDB->execute($st, array($a_attribs["id"], $a_attribs["name"], $a_attribs["default_pres_pos"]));
				break;
				
			case "pluginslot":
				$this->current_object = $a_attribs["id"];
				$q = "INSERT INTO il_pluginslot (component, id, name) VALUES (".
					$ilDB->quote($this->current_component).",".
					$ilDB->quote($a_attribs["id"]).",".
					$ilDB->quote($a_attribs["name"]).")";
				$ilDB->query($q);
				break;
		}
	}
			
	/**
	* end tag handler
	* 
	* @param	ressouce	internal xml_parser_handler
	* @param	string		element tag name
	* @access	private
	*/
	function handlerEndTag($a_xml_parser,$a_name)
	{
	}

			
	/**
	* end tag handler
	*
	* @param	ressouce	internal xml_parser_handler
	* @param	string		data
	* @access	private
	*/
	function handlerCharacterData($a_xml_parser,$a_data)
	{
		// DELETE WHITESPACES AND NEWLINES OF CHARACTER DATA
		$a_data = preg_replace("/\n/","",$a_data);
		$a_data = preg_replace("/\t+/","",$a_data);

		if (!empty($a_data))
		{
			switch ($this->current_tag)
			{
				case '':
			}
		}
	}

}
