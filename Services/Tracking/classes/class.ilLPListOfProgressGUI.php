<?php
  /*
   +-----------------------------------------------------------------------------+
   | ILIAS open source                                                           |
   +-----------------------------------------------------------------------------+
   | Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
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
   * Class ilLPListOfProgress
   *
   * @author Stefan Meyer <smeyer@databay.de>
   *
   * @version $Id$
   *
   * @ilCtrl_Calls ilLPListOfProgressGUI: ilLPFilterGUI
   *
   * @package ilias-tracking
   *
   */

include_once './Services/Tracking/classes/class.ilLearningProgressBaseGUI.php';
include_once './Services/Tracking/classes/class.ilLPStatusWrapper.php';

class ilLPListOfProgressGUI extends ilLearningProgressBaseGUI
{
	var $tracked_user = null;
	var $show_active = true;
	var $filter_gui = null;

	var $details_id = 0;
	var $details_type = '';

	function ilLPListOfProgressGUI($a_mode,$a_ref_id)
	{
		parent::ilLearningProgressBaseGUI($a_mode,$a_ref_id);
		$this->__initFilterGUI();
		$this->__initUser();

		// Set item id for details
		$this->__initDetails((int) $_GET['details_id']);
	}
		

	/**
	 * execute command
	 */
	function &executeCommand()
	{
		$this->ctrl->setReturn($this, "show");
		switch($this->ctrl->getNextClass())
		{
			case 'illpfiltergui':

				$this->ctrl->forwardCommand($this->filter_gui);
				break;


			default:
				$cmd = $this->__getDefaultCommand();
				$this->$cmd();

		}
		return true;
	}

	function show()
	{
		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.lp_list_progress.html','Services/Tracking');

		// Show user info, if not current user
		if($this->show_active)
		{
			$this->__showUserInfo();
		}
		$this->__showFilter();
		$this->__showProgress();
		
	}

	function details()
	{
		$this->tpl->addBlockFile('ADM_CONTENT','adm_content','tpl.lp_lm_details.html','Services/Tracking');
		switch($this->details_type)
		{
			case 'crs':
				$this->__showCourseDetails();
				break;

			case 'lm':
				$this->__showLMDetails();
				break;

			default:
				echo "Don't know";
		}
		
	}

	function __showLMDetails()
	{
		global $ilObjDataCache;

		include_once("classes/class.ilInfoScreenGUI.php");
		$info = new ilInfoScreenGUI($this);
		
		// Section lm details
		$info->addSection($this->lng->txt('details'));
		$info->addProperty($this->lng->txt('title'),$ilObjDataCache->lookupTitle($this->details_id));
		if(strlen($desc = $ilObjDataCache->lookupDescription($this->details_id)))
		{
			$info->addProperty($this->lng->txt('description'),$desc);
		}
		$info->addProperty($this->lng->txt('trac_mode'),ilLPObjSettings::_mode2Text(
							   $mode = ilLPObjSettings::_lookupMode($this->details_id)));

		if($mode == LP_MODE_VISITS)
		{
			$info->addProperty($this->lng->txt('trac_required_visits'),ilLPObjSettings::_lookupVisits($this->details_id));
		}
		
		include_once './Services/MetaData/classes/class.ilMDEducational.php';
		if($seconds = ilMDEducational::_getTypicalLearningTimeSeconds($this->details_id))
		{
			$info->addProperty($this->lng->txt('meta_typical_learning_time'),ilFormat::_secondsToString($seconds));
		}

		// Section learning_progress
		$info->addSection($this->lng->txt('trac_learning_progress'));

		include_once 'Services/Tracking/classes/class.ilLearningProgress.php';
		$progress = ilLearningProgress::_getProgress($this->tracked_user->getId(),$this->details_id);

		if($progress['access_time'])
		{
			$info->addProperty($this->lng->txt('last_access'),date('Y-m-d H:i:s',$progress['access_time']));
		}
		else
		{
			$info->addProperty($this->lng->txt('last_access'),$this->lng->txt('trac_not_accessed'));
		}
		$info->addProperty($this->lng->txt('trac_visits'),(int) $progress['visits']);
		$info->addProperty($this->lng->txt('trac_spent_time'),ilFormat::_secondsToString($progress['spent_time']));
		$info->addProperty($this->lng->txt('trac_status'),$this->lng->txt($this->__readStatus($this->details_id)));
		
		// Finally set template variable
		$this->tpl->setVariable("LM_INFO",$info->getHTML());
	}
		

	function __showUserInfo()
	{
		include_once("classes/class.ilInfoScreenGUI.php");
		
		$info = new ilInfoScreenGUI($this);

		$info->addSection($this->lng->txt("trac_user_data"));
		$info->addProperty($this->lng->txt('username'),$this->tracked_user->getLogin());
		$info->addProperty($this->lng->txt('name'),$this->tracked_user->getFullname());
		$info->addProperty($this->lng->txt('last_login'),ilFormat::formatDate($this->tracked_user->getLastLogin()));
		$info->addProperty($this->lng->txt('trac_total_online'),
						   ilFormat::_secondsToString(ilOnlineTracking::_getOnlineTime($this->tracked_user->getId())));

		// Finally set template variable
		$this->tpl->setVariable("USER_INFO",$info->getHTML());
		
	}

	function __showFilter()
	{
		$this->tpl->setVariable("FILTER",$this->filter_gui->getHTML());
	}

	function __showProgress()
	{
		$this->__initFilter();

		$tpl = new ilTemplate('tpl.lp_objects.html',true,true,'Services/Tracking');

		$this->filter->setRequiredPermission('read');
		if(!count($objs = $this->filter->getObjects()))
		{
			sendInfo($this->lng->txt('trac_filter_no_access'));
			return true;
		}
		$type = $this->filter->getFilterType();
		$tpl->setVariable("HEADER_IMG",ilUtil::getImagePath('icon_'.$type.'.gif'));
		$tpl->setVariable("HEADER_ALT",$this->lng->txt('objs_'.$type));
		$tpl->setVariable("BLOCK_HEADER_CONTENT",$this->lng->txt('objs_'.$type));

		$counter = 0;
		foreach($objs as $obj_id => $obj_data)
		{
			$tpl->touchBlock(ilUtil::switchColor($counter++,'row_type_1','row_type_2'));
			$tpl->setCurrentBlock("container_standard_row");
			$tpl->setVariable("ITEM_ID",$obj_id);

			$obj_tpl = new ilTemplate('tpl.lp_object.html',true,true,'Services/Tracking');
			$obj_tpl->setCurrentBlock("item_title");
			$obj_tpl->setVariable("TXT_TITLE",$obj_data['title']);
			$obj_tpl->parseCurrentBlock();

			if(strlen($obj_data['description']))
			{
				$obj_tpl->setCurrentBlock("item_description");
				$obj_tpl->setVariable("TXT_DESC",$obj_data['description']);
				$obj_tpl->parseCurrentBlock();
			}

			// Details link
			$obj_tpl->setCurrentBlock("item_command");
			$this->ctrl->setParameter($this,'details_id',$obj_id);
			$obj_tpl->setVariable("HREF_COMMAND",$this->ctrl->getLinkTarget($this,'details'));
			$obj_tpl->setVariable("TXT_COMMAND",$this->lng->txt('details'));
			$obj_tpl->parseCurrentBlock();


			// Hide link
			$obj_tpl->setCurrentBlock("item_command");
			$this->ctrl->setParameterByClass('illpfiltergui','hide',$obj_id);
			$obj_tpl->setVariable("HREF_COMMAND",$this->ctrl->getLinkTargetByClass('illpfiltergui','hide'));
			$obj_tpl->setVariable("TXT_COMMAND",$this->lng->txt('trac_hide'));
			$obj_tpl->parseCurrentBlock();

			// Path info
			$obj_tpl->setVariable("OCCURRENCES",$this->lng->txt('trac_occurrences'));
			foreach($obj_data['ref_ids'] as $ref_id)
			{
				$this->__insertPath($obj_tpl,$ref_id);
			}

			// Tracking activated for object
			// Users status

			$status = $this->__readStatus($obj_id);

			$obj_tpl->setCurrentBlock("item_property");
			$obj_tpl->setVariable("TXT_PROP",$this->lng->txt('trac_status'));
			$obj_tpl->setVariable("VAL_PROP",$this->lng->txt($status));
			$obj_tpl->parseCurrentBlock();
			
			$obj_tpl->setCurrentBlock("item_properties");
			$obj_tpl->parseCurrentBlock();
			
			$tpl->setVariable("BLOCK_ROW_CONTENT",$obj_tpl->get());
			$tpl->parseCurrentBlock();
		}	

		// Hide button
		$tpl->setVariable("DOWNRIGHT",ilUtil::getImagePath('arrow_downright.gif'));
		$tpl->setVariable("BTN_HIDE_SELECTED",$this->lng->txt('trac_hide'));
		$tpl->setVariable("FORMACTION",$this->ctrl->getFormActionByClass('illpfiltergui'));

		$this->tpl->setVariable("LP_OBJECTS",$tpl->get());

		return true;
	}


	function __initUser()
	{
		global $ilUser;

		if($_GET['usr_id'])
		{
			$this->tracked_user =& ilObjectFactory::getInstanceByObjId((int) $_GET['usr_id']);
		}
		else
		{
			$this->tracked_user =& $ilUser;
		}
		$this->show_active = $this->tracked_user->getId() == $ilUser->getId();

		return true;
	}

	function __initFilterGUI()
	{
		global $ilUser;

		include_once './Services/Tracking/classes/class.ilLPFilterGUI.php';

		$this->filter_gui = new ilLPFilterGUI($ilUser->getId());
	}

	function __initFilter()
	{
		global $ilUser;

		include_once './Services/Tracking/classes/class.ilLPFilter.php';

		$this->filter = new ilLPFilter($ilUser->getId());
	}

	function __initDetails($a_details_id)
	{
		global $ilObjDataCache;

		if($a_details_id)
		{
			$this->details_id = $a_details_id;
			$this->details_type = $ilObjDataCache->lookupType($this->details_id);
		}
	}

	function __readStatus($a_obj_id)
	{
		if(in_array($this->tracked_user->getId(),ilLPStatusWrapper::_getInProgress($a_obj_id)))
		{
			return $status = LP_STATUS_IN_PROGRESS;
		}
		elseif(in_array($this->tracked_user->getId(),ilLPStatusWrapper::_getCompleted($a_obj_id)))
		{
			return $status = LP_STATUS_COMPLETED;
		}
		else
		{
			return $status = LP_STATUS_NOT_ATTEMPTED;
		}
	}

}
?>