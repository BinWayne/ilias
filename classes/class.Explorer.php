<?php
/**
 * Class Explorer 
 * class for explorer view in admin frame
 * @author Stefan Meyer <smeyer@databay.de>
 * @version $Id$
 * 
 * @extends PEAR
 * @package ilias-core
 * @todo maybe only container should be visible, because the number of objects could be to big for recursion
 * implement a sort function
 */
class Explorer extends PEAR
{
	/**
	* ilias object
	* @var object Ilias
	* @access public
	*/
	var $ilias;

	/**
	* output
	* @var string
	* @access public
	*/
	var $output;

	/**
	* contains format options
	* @var array
	* @access public
	*/
	var $format_options;
	
	/**
	* tree
	* @var object Tree
	* @access public
	*/
	var $tree;
	
	/**
	* Constructor
	* @access public
	*/
	function Explorer()
	{
		global $ilias;
		
		$this->PEAR();
		$this->ilias = $ilias;
		$this->output = "";
		
		$this->tree = new Tree(1,0);
	}
	
	/**
	* Creates output for explorer view in admin menue
	* recursive method
	* @param	integer	parent_node_id where to start from (default=0, 'root')
	* @param	string	depth level where to start (default=1)
	* @access	public
	* @return	string
	*/
	function setOutput($a_parent,$a_depth = "")
	{
		global $rbacadmin, $rbacsystem,$expanded;
		static $counter = 0;

		if (empty($a_depth))
		{
		 	$a_depth = 1;
		}
		if($objects =  $this->tree->getChilds($a_parent,"title"))
		{
			$tab = ++$a_depth - 2;
			
			// Maybe call a lexical sort function for the child objects
			foreach($objects as $key => $object)
			{
				if($rbacsystem->checkAccess('visible',$object["id"],$object["parent"]))
				{
					if($object["id"] != 1)
					{
						$data = $this->tree->getParentNodeData($object["id"],$object["parent"]);
						$parent_index = $this->getIndex($data);
					}
					$this->format_options["$counter"]["parent"] = $object["parent"];
					$this->format_options["$counter"]["obj_id"] = $object["id"];
					$this->format_options["$counter"]["title"] = $object["title"];
					$this->format_options["$counter"]["type"] = $object["type"];
					$this->format_options["$counter"]["depth"] = $tab;
					$this->format_options["$counter"]["container"] = false;
					$this->format_options["$counter"]["visible"]	  = true;

					// Create prefix array
					for($i=0;$i<$tab;++$i)
					{
						 $this->format_options["$counter"]["tab"][] = 'blank';
					}

					// only if parent is expanded and visible, object is visible
					if($object["id"] != 1 and (!in_array($object["parent"],$expanded) 
					   or !$this->format_options["$parent_index"]["visible"]))
					{
						$this->format_options["$counter"]["visible"] = false;
					}
						
					// if object exists parent is container
					if($object["id"] != 1)
					{
						$this->format_options["$parent_index"]["container"] = true;
						if(in_array($object["parent"],$expanded))
						{
							$this->format_options["$parent_index"]["tab"][($tab-2)] = 'minus';
						}
						else
							$this->format_options["$parent_index"]["tab"][($tab-2)] = 'plus';
					}
					++$counter;
					// Recursive
					$this->setOutput($object["id"],$a_depth);
				} //if
			} //foreach
		} //if
	} //function

	/**
	* Creates output
	* recursive method
	* @access public
	* @return string
	*/
	function getOutput()
	{
		$this->format_options[0]["tab"] = array();
		
		$depth = $this->tree->getMaximumDepth();
		
		for($i=0;$i<$depth;++$i)
		{
			$this->createLines($i);
		}
		foreach($this->format_options as $key => $options)
		{
			if($options["visible"] or $key == 0 )
			{
				$this->formatObject($options["obj_id"],$options);
			}
		}
		return implode('',$this->output);
	}
	
	
	/**
	* Creates output
	* recursive method
	* @param int
	* @param int
	* @access public
	* @return string
	*/
	function formatObject($a_obj_id,$a_option)
	{
		$tmp = '';
		$tmp  .= "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\">\n";
		$tmp  .= "<tr>\n";

		foreach($a_option["tab"] as $picture)
		{
			if($picture == 'plus')
			{
				$target = $this->createTarget('+',$a_obj_id);

				// create expand href
				$tmp .= "<td nowrap align=\"left\"><a href=\"".$target."\"><img src=\"./images/browser/".
					$picture.".gif\" border=\"0\"></a></td>";
			}
			if($picture == 'minus')
			{
				$target = $this->createTarget('-',$a_obj_id);

				// create href
				$tmp .= "<td nowrap align=\"left\"><a href=\"".$target."\"><img src=\"./images/browser/".
					$picture.".gif\" border=\"0\"></a></td>";
			}
			if($picture == 'blank' or $picture == 'winkel' 
			   or $picture == 'hoch' or $picture == 'quer' or $picture == 'ecke')
			{
				$tmp .= "<td nowrap align=\"left\"><img src=\"./images/browser/".
					$picture.".gif\" border=\"0\"></td>";
			}
		}
		$tmp  .= "<td nowrap align=\"left\"><img src=\"./images/icon_".$a_option["type"].".gif\" border=\"0\"></td>\n";
		$tmp  .= "<td nowrap align=\"left\"><a href=\"content.php?obj_id=".$a_obj_id.
			"&parent=".$a_option["parent"]."\" target=\"content\">".$a_option["title"]."</a></td>\n";
		$tmp  .= "</tr>\n";
		$tmp  .= "</table>\n";
		$this->output[] = $tmp;
	}
	
/**
 * Creates Get Parameter
 * @access private
 * @param string
 * @param int
 * @return string
 */
	function createTarget($a_type,$a_obj_id)
	{
		global $expanded;

		$tmp_expanded = $expanded;

		if($a_type == '+')
		{
			return $_SERVER["REQUEST_URI"]."|".$a_obj_id;
		}
		if($a_type == '-')
		{
			$tmp = "?expand=";
			$tmp_expanded = array_flip($tmp_expanded);
			unset($tmp_expanded["$a_obj_id"]);
			$tmp_expanded = array_flip($tmp_expanded);
			
			return $tmp.implode('|',$tmp_expanded);
		}
	}
/**
 * Creates lines for explorer view
 * @access private
 * @param int 
 */
	function createLines($a_depth)
	{
		for($i=0;$i<count($this->format_options);++$i)
		{
			if($this->format_options[$i]["depth"] == $a_depth+1
			   and !$this->format_options[$i]["container"]
				and $this->format_options[$i]["depth"] != 1)
			{
				$this->format_options[$i]["tab"]["$a_depth"] = "quer";
			}
			if($this->format_options[$i]["depth"] == $a_depth+2)
			{
				if($this->is_in_array($i+1,$this->format_options[$i]["depth"]))
				{
					$this->format_options[$i]["tab"]["$a_depth"] = "winkel";
				}
				else
				{
					$this->format_options[$i]["tab"]["$a_depth"] = "ecke";
				}
			}
			if($this->format_options[$i]["depth"] > $a_depth+2)
			{
				if($this->is_in_array($i+1,$a_depth+2))
				{
					$this->format_options[$i]["tab"]["$a_depth"] = "hoch";
				}
			}
		}
	}
	
/**
 *
 * @access private
 * @param int
 * @param int
 * @return bool
 */
	function is_in_array($a_start,$a_depth)
	{
		for($i=$a_start;$i<count($this->format_options);++$i)
		{
			if($this->format_options[$i]["depth"] < $a_depth)
			{
				break;
			}
			if($this->format_options[$i]["depth"] == $a_depth)
			{
				return true;
			}
		}
		return false;
	}
/**
 * get index of format_options array from specific obj_id,parent_id
 * @param array object data
 * @return int index
 * @access private
 **/
	function getIndex($a_data)
	{
		foreach($this->format_options as $key => $value)
		{
			if(($value["obj_id"] == $a_data["obj_id"]) 
			   && ($value["parent"] == $a_data["parent"]))
			{
				return $key;
			}
		}
		$this->ilias->raiseError("Error in tree",$this->ilias->error_object->FATAL);
	}
		
}
?>
