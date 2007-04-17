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

include_once("./Services/Block/classes/class.ilBlockGUI.php");
include_once("./Services/Block/classes/class.ilExternalFeedBlockGUIGen.php");
include_once("./Services/Feeds/classes/class.ilExternalFeed.php");

/**
* BlockGUI class for external feed block. This is the one that is used
* within the repository. On the personal desktop ilPDExternalFeedBlockGUI
* is used.
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ilCtrl_IsCalledBy ilExternalFeedBlockGUI: ilColumnGUI
* @ingroup ServicesFeeds
*/
class ilExternalFeedBlockGUI extends ilExternalFeedBlockGUIGen
{
	static $block_type = "feed";
	
	/**
	* Constructor
	*/
	function ilExternalFeedBlockGUI()
	{
		global $ilCtrl, $lng;
		
		parent::__construct();
		parent::ilBlockGUI();
		
		$this->setImage(ilUtil::getImagePath("icon_feed_s.gif"));

		$lng->loadLanguageModule("feed");
		$this->setLimit(5);
		$this->setRowTemplate("tpl.block_external_feed_row.html", "Services/Feeds");
	}
		
	/**
	* Get block type
	*
	* @return	string	Block type.
	*/
	static function getBlockType()
	{
		return self::$block_type;
	}

	/**
	* Get block type
	*
	* @return	string	Block type.
	*/
	static function isRepositoryObject()
	{
		return true;
	}
	
	/**
	* Get Screen Mode for current command.
	*/
	static function getScreenMode()
	{
		global $ilCtrl;
		
		switch($ilCtrl->getCmd())
		{
			case "create":
			case "edit":
			case "saveFeedBlock":
			case "updateFeedBlock":
			case "editFeedBlock":
			case "showFeedItem":
				return IL_SCREEN_CENTER;
				break;
				
			default:
				return IL_SCREEN_SIDE;
				break;
		}
	}

	/**
	* Do most of the initialisation.
	*/
	function setBlock($a_block)
	{
		global $ilCtrl;

		// init block
		$this->feed_block = $a_block;
		$this->setTitle($this->feed_block->getTitle());
		$this->setBlockId($this->feed_block->getId());
		
		// get feed object
		include_once("./Services/Feeds/classes/class.ilExternalFeed.php");
		$this->feed = new ilExternalFeed();
		$this->feed->setUrl($this->feed_block->getFeedUrl());
		
		// init details
		$this->setAvailableDetailLevels(2);
		
		$ilCtrl->setParameter($this, "block_id", $this->feed_block->getId());
	}

	/**
	* execute command
	*/
	function &executeCommand()
	{
		global $ilCtrl;

		$next_class = $ilCtrl->getNextClass();
		$cmd = $ilCtrl->getCmd("getHTML");

		switch ($next_class)
		{
			default:
				return $this->$cmd();
		}
	}

	/**
	* Fill data section
	*/
	function fillDataSection()
	{
		if ($this->getCurrentDetailLevel() > 1 && count($this->getData()) > 0)
		{
			parent::fillDataSection();
		}
		else
		{
			$this->setDataSection($this->getOverview());
		}
	}

	/**
	* Get block HTML code.
	*/
	function getHTML()
	{
		global $ilCtrl, $lng, $ilUser, $ilAccess, $ilSetting;
		
		if ($this->getCurrentDetailLevel() == 0)
		{
			return "";
		}

		$feed_set = new ilSetting("feed");
		
		if ($feed_set->get("disable_rep_feeds"))
		{
			return "";
		}
		
		$this->feed->fetch();
		//$this->setTitle($this->feed->getChannelTitle());
		$this->setData($this->feed->getItems());

		if ($ilAccess->checkAccess("write", "", $this->getRefId()))
		{
			$ilCtrl->setParameterByClass("ilobjexternalfeedgui",
				"ref_id", $this->getRefId());
			$ilCtrl->setParameter($this, "external_feed_block_id", $this->getBlockId());
			$this->addBlockCommand(
				$ilCtrl->getLinkTargetByClass(array("ilrepositorygui", "ilobjexternalfeedgui",
					"ilexternalfeedblockgui"),
					"editFeedBlock"),
				$lng->txt("edit"));
			$ilCtrl->clearParametersByClass("ilobjexternalfeedgui");
		}

		return parent::getHTML();
	}

	/**
	* Fill feed item row
	*/
	function fillRow($item)
	{
		global $ilUser, $ilCtrl, $lng, $ilAccess;

		if ($this->isRepositoryObject() && !$ilAccess->checkAccess("read", "", $this->getRefId()))
		{
			$this->tpl->setVariable("TXT_TITLE", $item->getTitle());
		}
		else
		{
			$ilCtrl->setParameter($this, "feed_item_id", $item->getId());
			$this->tpl->setCurrentBlock("feed_link");
			$this->tpl->setVariable("VAL_TITLE", $item->getTitle());
			$this->tpl->setVariable("HREF_SHOW",
				$ilCtrl->getLinkTarget($this, "showFeedItem"));
			$ilCtrl->setParameter($this, "feed_item_id", "");
			$this->tpl->parseCurrentBlock();
		}
	}

	/**
	* Get overview.
	*/
	function getOverview()
	{
		global $ilUser, $lng, $ilCtrl;
		
		$this->setEnableNumInfo(false);
		return '<div class="small">'.((int) count($this->getData()))." ".$lng->txt("feed_feed_items")."</div>";
	}

	/**
	* Show Feed Item
	*/
	function showFeedItem()
	{
		global $lng, $ilCtrl;
		
		include_once("./Services/News/classes/class.ilNewsItem.php");

		$this->feed->fetch();
		foreach($this->feed->getItems() as $item)
		{
			if ($item->getId() == $_GET["feed_item_id"])
			{
				$c_item = $item;
				break;
			}
		}
		
		$tpl = new ilTemplate("tpl.show_feed_item.html", true, true, "Services/Feeds");
		
		if (is_object($c_item))
		{
//var_dump($c_item->getMagpieItem());
//echo $c_item->getLink();
			if (trim($c_item->getSummary()) != "")		// summary
			{
				$tpl->setCurrentBlock("content");
				$tpl->setVariable("VAL_CONTENT", $c_item->getSummary());
				$tpl->parseCurrentBlock();
			}
			if (trim($c_item->getDate()) != "" || trim($c_item->getAuthor()) != "")		// date
			{
				$tpl->setCurrentBlock("date_author");
				if (trim($c_item->getAuthor()) != "")
				{
					$tpl->setVariable("VAL_AUTHOR", $c_item->getAuthor()." - ");
				}
				$tpl->setVariable("VAL_DATE", $c_item->getDate());
				$tpl->parseCurrentBlock();
			}

			if (trim($c_item->getLink()) != "")		// link
			{
				$tpl->setCurrentBlock("plink");
				$tpl->setVariable("HREF_LINK", $c_item->getLink());
				$tpl->setVariable("TXT_LINK", $lng->txt("feed_open_source_page"));
				$tpl->parseCurrentBlock();
			}
			$tpl->setVariable("VAL_TITLE", $c_item->getTitle());			// title
		}
		
		include_once("./Services/PersonalDesktop/classes/class.ilPDContentBlockGUI.php");
		$content_block = new ilPDContentBlockGUI();
		$content_block->setContent($tpl->get());
		$content_block->setTitle($this->feed->getChannelTitle());
		$content_block->setImage(ilUtil::getImagePath("icon_feed.gif"));
		$content_block->addHeaderCommand($ilCtrl->getParentReturn($this),
			$lng->txt("close"), true);

		return $content_block->getHTML();
	}
	
	/**
	* Create Form for Block.
	*/
	function create()
	{
		return $this->createFeedBlock();
	}

	/**
	* FORM FeedBlock: Init form. (We need to overwrite, because Generator
	* does not know FeedUrl Inputs yet.
	*
	* @param	int	$a_mode	Form Edit Mode (IL_FORM_EDIT | IL_FORM_CREATE)
	*/
	public function initFormFeedBlock($a_mode)
	{
		global $lng;
		
		$lng->loadLanguageModule("block");
		
		include("Services/Form/classes/class.ilPropertyFormGUI.php");
		
		$this->form_gui = new ilPropertyFormGUI();
		
		// Property Title
		$text_input = new ilTextInputGUI($lng->txt("block_feed_block_title"), "block_title");
		$text_input->setInfo("");
		$text_input->setRequired(true);
		$text_input->setMaxLength(200);
		$this->form_gui->addItem($text_input);
		
		// Property FeedUrl
		$text_input = new ilFeedUrlInputGUI($lng->txt("block_feed_block_feed_url"), "block_feed_url");
		$text_input->setInfo($lng->txt("block_feed_block_feed_url_info"));
		$text_input->setRequired(true);
		$text_input->setMaxLength(250);
		$this->form_gui->addItem($text_input);
		
		
		// save and cancel commands
		if (in_array($a_mode, array(IL_FORM_CREATE,IL_FORM_RE_CREATE)))
		{
			$this->form_gui->addCommandButton("saveFeedBlock", $lng->txt("save"));
			$this->form_gui->addCommandButton("cancelSaveFeedBlock", $lng->txt("cancel"));
		}
		else
		{
			$this->form_gui->addCommandButton("updateFeedBlock", $lng->txt("save"));
			$this->form_gui->addCommandButton("cancelUpdateFeedBlock", $lng->txt("cancel"));
		}
		
		$this->form_gui->setTitle($lng->txt("block_feed_block_head"));
		$this->form_gui->setFormAction($this->ctrl->getFormAction($this));
		
		$this->prepareFormFeedBlock($this->form_gui);

	}

	/**
	* FORM FeedBlock: Prepare Saving of FeedBlock.
	*
	* @param	object	$a_feed_block	FeedBlock object.
	*/
	public function prepareSaveFeedBlock(&$a_feed_block)
	{
		global $ilCtrl;
		
		$ref_id = $this->getGuiObject()->save($a_feed_block);
		$a_feed_block->setType($this->getBlockType());
		//$a_feed_block->setContextObjId($ilCtrl->getContextObjId());
		//$a_feed_block->setContextObjType($ilCtrl->getContextObjType());
	}
	
	/**
	* FORM FeedBlock: Exit save. (Can be overwritten in derived classes)
	*
	*/
	public function exitSaveFeedBlock()
	{
		global $ilCtrl;

		$this->getGuiObject()->exitSave();
	}

	/**
	* FORM FeedBlock: Exit save. (Can be overwritten in derived classes)
	*
	*/
	public function cancelUpdateFeedBlock()
	{
		global $ilCtrl;

		$this->getGuiObject()->cancelUpdate();
	}

	/**
	* FORM FeedBlock: Exit save. (Can be overwritten in derived classes)
	*
	*/
	public function exitUpdateFeedBlock()
	{
		global $ilCtrl;
		
		$this->getGuiObject()->update($this->external_feed_block);
	}
}

?>
