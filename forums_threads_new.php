<?php
/**
* forums_threads_new
*
* @author Wolfgang Merkens <wmerkens@databay.de>
* @version $Id$
*
* @package ilias
*/
require_once "./include/inc.header.php";
require_once "classes/class.Forum.php";

$frm = new Forum();

$frm->setWhereCondition("top_frm_fk = ".$_GET["obj_id"]);
$topicData = $frm->getOneTopic();	

$tpl->addBlockFile("CONTENT", "content", "tpl.forums_threads_new.html");
$tpl->addBlockFile("LOCATOR", "locator", "tpl.locator.html");

if (!$rbacsystem->checkAccess("write", $_GET["obj_id"], $_GET["parent"])) {
	$ilias->raiseError($lng->txt("permission_denied"),$ilias->error_obj->MESSAGE);
}

$tpl->touchBlock("locator_separator");
$tpl->setCurrentBlock("locator_item");
$tpl->setVariable("ITEM", $lng->txt("forums_overview"));
$tpl->setVariable("LINK_ITEM", "forums.php?obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]);
$tpl->parseCurrentBlock();

if (!$_GET["backurl"])
{
	$tpl->touchBlock("locator_separator");
	$tpl->setCurrentBlock("locator_item");
	$tpl->setVariable("ITEM", $lng->txt("forums_topics_overview").": ".$topicData["top_name"]);
	$tpl->setVariable("LINK_ITEM", "forums_threads_liste.php?obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]);
	$tpl->parseCurrentBlock();
}

$tpl->setCurrentBlock("locator_item");
$tpl->setVariable("ITEM", $topicData["top_name"].": ".$lng->txt("forums_new_thread"));
if (!$_GET["backurl"]) $tpl->setVariable("LINK_ITEM", "forums_threads_new.php?obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]);
else $tpl->setVariable("LINK_ITEM", "forums_threads_new.php?obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]."&backurl=".$_GET["backurl"]);
$tpl->parseCurrentBlock();

if ($_GET["cmd"] == "newthread")
{		
	$formData = $_POST["formData"];
	
	// Check Formular-Daten
	$checkEmptyFields = array(
		$lng->txt("subject")   => $formData["subject"],
		$lng->txt("message")   => $formData["message"]	
	);
	$errors = TUtil::checkFormEmpty($checkEmptyFields);
	if ($errors != "")
	{
		$tpl->setVariable("TXT_FORM_FEEDBACK", $lng->txt("form_empty_fields")."<br>".$errors);
	}
	else
	{		
		$newPost = $frm->generateThread($_GET["obj_id"], $_GET["parent"], $topicData["top_pk"], $_SESSION["AccountId"], $formData["subject"], $formData["message"]);
		
		// Visit-Counter
		$frm->setDbTable("frm_data");
		$frm->setWhereCondition("top_pk = ".$topicData["top_pk"]);
		$frm->updateVisits($topicData["top_pk"]);
			
		$frm->setWhereCondition("thr_top_fk = '".$topicData["top_pk"]."' AND thr_subject = '".$formData["subject"]."' AND thr_num_posts = 1");
		if (is_array($thrData = $frm->getOneThread())) {
			header("location: forums_threads_view.php?thr_pk=".$thrData["thr_pk"]."&obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]."&feedback=".urlencode($lng->txt("forums_thread_new_entry")));
			exit();
		} 
	}
}

$tpl->setCurrentBlock("new_thread");
$tpl->setVariable("TXT_MUSTBE", $lng->txt("mandatory_fields"));
$tpl->setVariable("TXT_SUBJECT", $lng->txt("forums_thread"));
$tpl->setVariable("TXT_MESSAGE", $lng->txt("forums_the_post"));
$tpl->setVariable("SUBMIT", $lng->txt("submit"));
$tpl->setVariable("RESET", $lng->txt("reset"));
$tpl->setVariable("FORMACTION", basename($_SERVER["PHP_SELF"])."?cmd=newthread&obj_id=".$_GET["obj_id"]."&parent=".$_GET["parent"]."&backurl=".$_GET["backurl"]);
$tpl->parseCurrentBlock("new_thread");


if ($_GET["message"])
{
    $tpl->addBlockFile("MESSAGE", "message2", "tpl.message.html");
	$tpl->setCurrentBlock("message2");
	$tpl->setVariable("MSG", urldecode( $_GET["message"]));
	$tpl->parseCurrentBlock();
}


$tpl->show();

?>