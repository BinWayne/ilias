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
   * Soap utitliy functions
   *
   * @author Stefan Meyer <smeyer@databay.de>
   * @version $Id$
   *
   * @package ilias
   */
include_once './webservice/soap/classes/class.ilSoapAdministration.php';

class ilSoapUtils extends ilSoapAdministration
{
	function ilSoapUtils()
	{
		parent::ilSoapAdministration();
	}

	function ignoreUserAbort()
	{
		ignore_user_abort();
	}

	function disableSOAPCheck()
	{
		$this->sauth->disableSOAPCheck();
	}

	function sendMail($sid,$to,$cc,$bcc,$sender,$subject,$message,$attach)
	{
		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}			

		// Include main header
		include_once './include/inc.header.php';
		

		include_once './classes/class.ilMimeMail.php';

		$mmail = new ilMimeMail();
		$mmail->autoCheck(false);
		$mmail->From($sender);
		$mmail->To(explode(',',$to));
		$mmail->Subject($subject);
		$mmail->Body($message);

		if($cc)
		{
			$mmail->Cc(explode(',',$cc));
		}

		if($bcc)
		{
			$mmail->Bcc(explode(',',$bcc));
		}
		if($attach)
		{
			$attachments = explode(',',$attach);
			foreach ($attachments as $attachment)
			{
				$mmail->Attach($attachment);
			}
		}

		$mmail->Send();
		$ilLog->write('SOAP: sendMail(): '.$to.', '.$cc.', '.$bcc);

		return true;
	}
	function saveQuestionResult($sid,$user_id,$test_id,$question_id,$pass,$solution)
	{
		if(!$this->__checkSession($sid))
		{
			return $this->__raiseError($this->sauth->getMessage(),$this->sauth->getMessageCode());
		}			

		// Include main header
		include_once './include/inc.header.php';
		$ilDB = $GLOBALS['ilDB'];
		for($i = 0; $i < count($solution); $i += 3)
		{
			$query = sprintf("INSERT INTO tst_solutions ".
				"SET user_fi = %s, ".
				"test_fi = %s, ".
				"question_fi = %s, ".
				"value1 = %s, ".
				"value2 = %s, ".
				"points = %s, ".
				"pass = %s",
				$ilDB->quote($user_id . ""),
				$ilDB->quote($test_id . ""),
				$ilDB->quote($question_id . ""),
				$ilDB->quote($solution[$i]),
				$ilDB->quote($solution[$i+1]),
				$ilDB->quote($solution[$i+2]),
				$ilDB->quote($pass . "")
			);

			$ilDB->query($query);
		}
		return true;
	}
}
?>