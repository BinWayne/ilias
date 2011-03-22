<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Table/classes/class.ilTable2GUI.php';
include_once './Services/Tracking/classes/class.ilLPCollections.php';
include_once './Services/Tracking/classes/class.ilLPObjSettings.php';

/**
 * Description of class
 *
 * @author Stefan Meyer <meyer@leifos.com>
 * @ingroup ServicesTracking
 */
class ilLPCollectionSettingsTableGUI extends ilTable2GUI
{

	private $mode;
	private $node_id;
	private $collections;

	/**
	 * Constructor
	 * @param ilObject $a_parent_obj
	 * @param string $a_parent_cmd
	 */
	public function  __construct($node_id,$a_parent_obj, $a_parent_cmd = "")
	{
		$this->node_id = $node_id;
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->setId('lpobjs_'.$this->getNode());
	}

	/**
	 * Set learning progress mode
	 * @param int $a_mode
	 * @see ilLPObjSettings
	 */
	public function setMode($a_mode)
	{
		$this->mode = $a_mode;
	}

	/**
	 * Get mode
	 * @return int
	 */
	public function getMode()
	{
		return $this->mode;
	}

	/**
	 * Get node id of current learning progress item
	 * @return int $node_id
	 */
	public function getNode()
	{
		return $this->node_id;
	}

	/**
	 * Get collection object
	 * @return ilLPCollections
	 */
	protected function getCollection()
	{
		return $this->collections;
	}

	/**
	 * Read and parse items
	 */
	public function parse()
	{
		switch($this->getMode())
		{
			case LP_MODE_COLLECTION:
			case LP_MODE_MANUAL_BY_TUTOR:
				$this->parseCollection();
				break;

			case LP_MODE_SCORM:
				$this->parseScormCollection();
				break;
		}
		$this->initTable();
	}

	/**
	 * Fill template row
	 * @param array $a_set
	 */
	protected function  fillRow($a_set)
	{
		include_once './classes/class.ilLink.php';

		$this->tpl->setCurrentBlock('item_row');
		$this->tpl->setVariable('ITEM_ID', $a_set['id']);
		$this->tpl->setVariable('COLL_TITLE', $a_set['title']);
		$this->tpl->setVariable('COLL_DESC',$a_set['description']);

		if($this->getMode() == LP_MODE_SCORM)
		{
			$this->tpl->setVariable('TYPE_IMG', ilUtil::getImagePath('icon_sco_s.gif'));
			$this->tpl->setVariable('ALT_IMG', $this->lng->txt('obj_sco'));
		}
		else
		{
			include_once './Services/Tree/classes/class.ilPathGUI.php';
			$path = new ilPathGUI();

			$this->tpl->setVariable('TYPE_IMG', ilUtil::getImagePath('icon_' . $a_set['type'] . '_s.gif'));
			$this->tpl->setVariable('ALT_IMG', $this->lng->txt('obj_' . $a_set['type']));
			$this->tpl->setVariable('COLL_LINK', ilLink::_getLink($a_set['ref_id'], $a_set['type']));
			$this->tpl->setVariable('COLL_FRAME', ilFrameTargetInfo::_getFrame('MainContent', $a_set['type']));
			$this->tpl->setVariable('COLL_PATH',$this->lng->txt('path').': '.$path->getPath($this->getNode(),$a_set['ref_id']));


			$mode = ilLPObjSettings::_lookupMode($a_set['obj_id']);
			if($mode != LP_MODE_DEACTIVATED && $mode != LP_MODE_UNDEFINED)
			{
				$this->tpl->setVariable(
					"COLL_MODE",
					$this->lng->txt('trac_mode').": ". ilLPObjSettings::_mode2Text($mode)
				);
			}
			else
			{
				$this->tpl->setVariable(
					"COLL_MODE",
					 $this->lng->txt('trac_mode').":"
				);
				$this->tpl->setVariable(
					"COLL_MODE_DEACTIVATED",
					ilLPObjSettings::_mode2Text($mode)
				);
			}
			if($this->isAnonymized($a_set))
			{
				$this->tpl->setVariable("ANONYMIZED",$this->lng->txt('trac_anonymized_info_short'));
			}
		}

		// Assigned ?
		$this->tpl->setVariable(
			"ASSIGNED_IMG_OK",
			$this->getCollection()->isAssigned($a_set['id'])
				? ilUtil::getImagePath('icon_ok.gif')
				: ilUtil::getImagePath('icon_not_ok.gif')
		);
		$this->tpl->setVariable(
			"ASSIGNED_STATUS",
			$this->getCollection()->isAssigned($a_set['id'])
				? $this->lng->txt('trac_assigned')
				: $this->lng->txt('trac_not_assigned')
		);
		$this->tpl->parseCurrentBlock();


		// Parse grouped items
		foreach((array) $a_set['grouped'] as $item)
		{
			$this->fillRow($item);
		}


	}

	/**
	 * Read and parse collection items
	 * @return void
	 */
	protected function parseCollection()
	{
		$this->collections = new ilLPCollections(ilObject::_lookupObjId($this->getNode()));

		$items = ilLPCollections::_getPossibleItems($this->getNode());

		$data = array();
		$done = array();
		foreach($items as $item)
		{
			if(in_array($item, $done))
				continue;

			$tmp = $this->parseCollectionItem($item);
			$tmp['grouped'] = array();

			if($this->getMode() == LP_MODE_COLLECTION)
			{
				$grouped_items = ilLPCollections::lookupGroupedItems(
					ilObject::_lookupObjId($this->getNode()),
					$item
				);
				if(count($grouped_items = ilLPCollections::lookupGroupedItems(ilObject::_lookupObjId($this->getNode()), $item)) > 1)
				{
					foreach($grouped_items as $gr)
					{
						if($gr == $item)
						{
							continue;
						}
						$tmp['grouped'][] = $this->parseCollectionItem($gr);
						$done[] = $gr;
					}
				}
			}
			$data[] = $tmp;
		}
		$this->setData((array) $data);
	}

	/**
	 * parse scorm collection
	 */
	protected function parseScormCollection()
	{
		$this->collections = new ilLPCollections(ilObject::_lookupObjId($this->getNode()));

		$items = ilLPCollections::_getPossibleSAHSItems(ilObject::_lookupObjId($this->getNode()));

		$data = array();
		foreach($items as $obj_id => $item)
		{
			$tmp['id'] = $obj_id;
			$tmp['ref_id'] = 0;
			$tmp['title'] = $item['title'];


			$data[] = $tmp;
		}
		$this->setData($data);
		return;
	}

	/**
	 * Parse one item
	 * @param array $item
	 */
	protected function parseCollectionItem($item)
	{
		$tmp['ref_id'] = $item;
		$tmp['id'] = $item;
		$tmp['obj_id'] = ilObject::_lookupObjId($item);
		$tmp['type'] = ilObject::_lookupType($tmp['obj_id']);
		$tmp['title'] = ilObject::_lookupTitle($tmp['obj_id']);
		$tmp['description'] = ilObject::_lookupDescription($tmp['obj_id']);
		return $tmp;
	}

	/**
	 * Init table
	 */
	protected function initTable()
	{
		global $ilCtrl;

		$this->setFormAction($ilCtrl->getFormAction($this->getParentObject()));
		switch($this->getMode())
		{
			case LP_MODE_COLLECTION:
				$this->setRowTemplate('tpl.lp_collection_row.html', 'Services/Tracking');
				$this->setTitle($this->lng->txt('trac_lp_determination'));
				$this->setDescription($this->lng->txt('trac_lp_determination_info_crs'));
				break;

			case LP_MODE_MANUAL_BY_TUTOR:
				$this->setRowTemplate('tpl.lp_collection_row.html', 'Services/Tracking');
				$this->setTitle($this->lng->txt('trac_lp_determination_tutor'));
				$this->setDescription($this->lng->txt('trac_lp_determination_info_crs_tutor'));
				break;

			case LP_MODE_SCORM:
				$this->setRowTemplate('tpl.lp_collection_scorm_row.html', 'Services/Tracking');
				$this->setTitle($this->lng->txt('trac_lp_determination'));
				$this->setDescription($this->lng->txt('trac_lp_determination_info_sco'));
				break;
		}

		$this->addColumn('','','1px');
		$this->addColumn($this->lng->txt('title'), 'title','80%');
		$this->addColumn($this->lng->txt('active'), '');

		$this->enable('select_all');
		$this->setSelectAllCheckbox('item_ids');

		$this->addMultiCommand('assign', $this->lng->txt('trac_collection_assign'));
		$this->addMultiCommand('deassign', $this->lng->txt('trac_collection_deassign'));

		if($this->getMode() == LP_MODE_COLLECTION)
		{
			$this->addMultiCommand('groupMaterials', $this->lng->txt('trac_group_materials'));
			if(ilLPCollections::hasGroupedItems(ilObject::_lookupObjId($this->getNode())))
			{
				$this->addMultiCommand('releaseMaterials', $this->lng->txt('trac_release_materials'));
			}
		}
	}

	/**
	 * Check if item is anonymized
	 * @param array item
	 * @return <type>
	 */
	protected function isAnonymized($a_item)
	{
		switch($a_item['type'])
		{
			case 'tst':
				include_once './Modules/Test/classes/class.ilObjTest.php';

				if(ilObjTest::_lookupAnonymity($a_item['obj_id']))
				{
					return true;
				}
				return false;

			default:
				return false;
		}
	}

}
?>