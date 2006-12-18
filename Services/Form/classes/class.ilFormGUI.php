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
* This class represents a form user interface
*
* @author Alex Killing <alex.killing@gmx.de> 
* @version $Id$
*
*/
class ilFormGUI
{

	/**
	* Constructor
	*
	* @param
	*/
	function ilFormGUI()
	{
	}

	/**
	* Set FormAction.
	*
	* @param	string	$a_formaction	FormAction
	*/
	function setFormAction($a_formaction)
	{
		$this->formaction = $a_formaction;
	}

	/**
	* Get FormAction.
	*
	* @return	string	FormAction
	*/
	function getFormAction()
	{
		return $this->formaction;
	}

	/**
	* Get HTML.
	*/
	function getHTML()
	{
		$tpl = new ilTemplate("tpl.form.html", true, true, "Services/Form");
		$tpl->setVariable("FORM_CONTENT", $this->getContent());
		$tpl->setVariable("FORM_ACTION", $this->getFormAction());
		return $tpl->get();
	}

	/**
	* Get Content.
	*/
	function getContent()
	{
		return "";
	}

}
