<?php
 /*
   +----------------------------------------------------------------------------+
   | ILIAS open source                                                          |
   +----------------------------------------------------------------------------+
   | Copyright (c) 1998-2001 ILIAS open source, University of Cologne           |
   |                                                                            |
   | This program is free software; you can redistribute it and/or              |
   | modify it under the terms of the GNU General Public License                |
   | as published by the Free Software Foundation; either version 2             |
   | of the License, or (at your option) any later version.                     |
   |                                                                            |
   | This program is distributed in the hope that it will be useful,            |
   | but WITHOUT ANY WARRANTY; without even the implied warranty of             |
   | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the              |
   | GNU General Public License for more details.                               |
   |                                                                            |
   | You should have received a copy of the GNU General Public License          |
   | along with this program; if not, write to the Free Software                |
   | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA. |
   +----------------------------------------------------------------------------+
*/

include_once "./Modules/SurveyQuestionPool/classes/class.SurveyQuestionGUI.php";

/**
* SingleChoice survey question GUI representation
*
* The SurveySingleChoiceQuestionGUI class encapsulates the GUI representation
* for single choice survey question types.
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id$
* @extends SurveyQuestionGUI
* @ingroup ModulesSurveyQuestionPool
*/
class SurveySingleChoiceQuestionGUI extends SurveyQuestionGUI 
{
	protected function initObject()
	{	
		include_once "./Modules/SurveyQuestionPool/classes/class.SurveySingleChoiceQuestion.php";
		$this->object = new SurveySingleChoiceQuestion();		
	}
	
	
	//	 
	// EDITOR
	//	
	
	public function setQuestionTabs()
	{
		$this->setQuestionTabsForClass("surveysinglechoicequestiongui");
	}
	
	protected function addFieldsToEditForm(ilPropertyFormGUI $a_form)
	{	
		// orientation
		$orientation = new ilRadioGroupInputGUI($this->lng->txt("orientation"), "orientation");
		$orientation->setRequired(false);		
		$orientation->addOption(new ilRadioOption($this->lng->txt('vertical'), 0));
		$orientation->addOption(new ilRadioOption($this->lng->txt('horizontal'), 1));
		$orientation->addOption(new ilRadioOption($this->lng->txt('combobox'), 2));
		$a_form->addItem($orientation);

		// Answers
		include_once "./Modules/SurveyQuestionPool/classes/class.ilCategoryWizardInputGUI.php";
		$answers = new ilCategoryWizardInputGUI($this->lng->txt("answers"), "answers");
		$answers->setRequired(false);
		$answers->setAllowMove(true);
		$answers->setShowWizard(true);
		$answers->setShowSavePhrase(true);
		$answers->setUseOtherAnswer(true);
		$answers->setShowNeutralCategory(true);
		$answers->setNeutralCategoryTitle($this->lng->txt('svy_neutral_answer'));		
		$answers->setDisabledScale(false);
		$a_form->addItem($answers);
		
		// values
		$orientation->setValue($this->object->getOrientation());
		if (!$this->object->getCategories()->getCategoryCount())
		{
			$this->object->getCategories()->addCategory("");
		}
		$answers->setValues($this->object->getCategories());
	}
	
	protected function importEditFormValues(ilPropertyFormGUI $a_form)
	{
		$this->object->setOrientation($a_form->getInput("orientation"));
		
		$this->object->categories->flushCategories();

		foreach ($_POST['answers']['answer'] as $key => $value) 
		{
			if (strlen($value)) $this->object->getCategories()->addCategory($value, $_POST['answers']['other'][$key], 0, null, $_POST['answers']['scale'][$key]);
		}
		if (strlen($_POST['answers']['neutral']))
		{
			$this->object->getCategories()->addCategory($_POST['answers']['neutral'], 0, 1, null, $_POST['answers_neutral_scale']);
		}
	}
	
	/**
	* Creates a HTML representation of the question
	*
	* @access private
	*/
	function getPrintView($question_title = 1, $show_questiontext = 1, $survey_id = null)
	{
		$template = new ilTemplate("tpl.il_svy_qpl_sc_printview.html", TRUE, TRUE, "Modules/SurveyQuestionPool");
		switch ($this->object->orientation)
		{
			case 0:
				// vertical orientation
				for ($i = 0; $i < $this->object->categories->getCategoryCount(); $i++) 
				{
					$cat = $this->object->categories->getCategory($i);
					if ($cat->other)
					{
						$template->setCurrentBlock("other_row");
						$template->setVariable("IMAGE_RADIO", ilUtil::getHtmlPath(ilUtil::getImagePath("radiobutton_unchecked.png")));
						$template->setVariable("ALT_RADIO", $this->lng->txt("unchecked"));
						$template->setVariable("TITLE_RADIO", $this->lng->txt("unchecked"));
						$template->setVariable("OTHER_LABEL", ilUtil::prepareFormOutput($cat->title));
						$template->setVariable("OTHER_ANSWER", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
						$template->parseCurrentBlock();
					}
					else
					{
						$template->setCurrentBlock("row");
						$template->setVariable("IMAGE_RADIO", ilUtil::getHtmlPath(ilUtil::getImagePath("radiobutton_unchecked.png")));
						$template->setVariable("ALT_RADIO", $this->lng->txt("unchecked"));
						$template->setVariable("TITLE_RADIO", $this->lng->txt("unchecked"));
						$template->setVariable("TEXT_SC", ilUtil::prepareFormOutput($cat->title));
						$template->parseCurrentBlock();
					}
				}
				break;
			case 1:
				// horizontal orientation
				for ($i = 0; $i < $this->object->categories->getCategoryCount(); $i++) 
				{
					$template->setCurrentBlock("radio_col");
					$template->setVariable("IMAGE_RADIO", ilUtil::getHtmlPath(ilUtil::getImagePath("radiobutton_unchecked.png")));
					$template->setVariable("ALT_RADIO", $this->lng->txt("unchecked"));
					$template->setVariable("TITLE_RADIO", $this->lng->txt("unchecked"));
					$template->parseCurrentBlock();
				}
				for ($i = 0; $i < $this->object->categories->getCategoryCount(); $i++) 
				{
					$cat = $this->object->categories->getCategory($i);
					if ($cat->other)
					{
						$template->setCurrentBlock("other_text_col");
						$template->setVariable("OTHER_LABEL", ilUtil::prepareFormOutput($cat->title));
						$template->setVariable("OTHER_ANSWER", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
						$template->parseCurrentBlock();
					}
					else
					{
						$template->setCurrentBlock("text_col");
						$template->setVariable("TEXT_SC", ilUtil::prepareFormOutput($cat->title));
						$template->parseCurrentBlock();
					}
				}
				break;
			case 2:
				// combobox output
				for ($i = 0; $i < $this->object->categories->getCategoryCount(); $i++) 
				{
					$cat = $this->object->categories->getCategory($i);
					$template->setCurrentBlock("comborow");
					$template->setVariable("TEXT_SC", ilUtil::prepareFormOutput($cat->title));
					$template->setVariable("VALUE_SC", ($cat->scale) ? ($cat->scale - 1) : $i);
					if (is_array($working_data))
					{
						if (strcmp($working_data[0]["value"], "") != 0)
						{
							if ($working_data[0]["value"] == $i)
							{
								$template->setVariable("SELECTED_SC", " selected=\"selected\"");
							}
						}
					}
					$template->parseCurrentBlock();
				}
				$template->setCurrentBlock("combooutput");
				$template->setVariable("QUESTION_ID", $this->object->getId());
				$template->setVariable("SELECT_OPTION", $this->lng->txt("select_option"));
				$template->setVariable("TEXT_SELECTION", $this->lng->txt("selection"));
				$template->parseCurrentBlock();
				break;
		}
		if ($question_title)
		{
			$template->setVariable("QUESTION_TITLE", $this->object->getTitle());
		}
		if ($show_questiontext)
		{
			$this->outQuestionText($template);
		}
		$template->parseCurrentBlock();
		return $template->get();
	}

	
	//
	// PHRASES (see SurveyMatrixQuestionGUI)
	//
		
	/**
	* Creates an output for the addition of phrases
	*/
	protected function wizardanswers($save_post_data = true) 
	{		
		if ($save_post_data) 
		{			
			$result = $this->saveForm();
		}		
		if ($result || !$save_post_data)
		{
			if ($save_post_data) 
			{
				$this->object->saveToDb();
			}
			
			$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_qpl_addphrase.html", "Modules/SurveyQuestionPool");

			// set the id to return to the selected question
			$this->tpl->setCurrentBlock("hidden");
			$this->tpl->setVariable("HIDDEN_NAME", "id");
			$this->tpl->setVariable("HIDDEN_VALUE", $this->object->getId());
			$this->tpl->parseCurrentBlock();

			include_once "./Modules/SurveyQuestionPool/classes/class.ilSurveyPhrases.php";
			$phrases =& ilSurveyPhrases::_getAvailablePhrases();
			$colors = array("tblrow1", "tblrow2");
			$counter = 0;
			foreach ($phrases as $phrase_id => $phrase_array)
			{
				$this->tpl->setCurrentBlock("phraserow");
				$this->tpl->setVariable("COLOR_CLASS", $colors[$counter++ % 2]);
				$this->tpl->setVariable("PHRASE_VALUE", $phrase_id);
				$this->tpl->setVariable("PHRASE_NAME", $phrase_array["title"]);
				$categories =& ilSurveyPhrases::_getCategoriesForPhrase($phrase_id);
				$this->tpl->setVariable("PHRASE_CONTENT", join($categories, ","));
				$this->tpl->parseCurrentBlock();
			}

			$this->tpl->setCurrentBlock("adm_content");
			$this->tpl->setVariable("TEXT_CANCEL", $this->lng->txt("cancel"));
			$this->tpl->setVariable("TEXT_PHRASE", $this->lng->txt("phrase"));
			$this->tpl->setVariable("TEXT_CONTENT", $this->lng->txt("categories"));
			$this->tpl->setVariable("TEXT_ADD_PHRASE", $this->lng->txt("add_phrase"));
			$this->tpl->setVariable("TEXT_INTRODUCTION",$this->lng->txt("add_phrase_introduction"));
			$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
			$this->tpl->parseCurrentBlock();
		}
	}

	/**
	* Cancels the form adding a phrase
	*
	* @access public
	*/
	function cancelViewPhrase() 
	{
		ilUtil::sendInfo($this->lng->txt('msg_cancel'), true);
		$this->ctrl->redirect($this, 'editQuestion');
	}

	/**
	* Adds a selected phrase
	*
	* @access public
	*/
	function addSelectedPhrase() 
	{
		if (strcmp($_POST["phrases"], "") == 0)
		{
			ilUtil::sendInfo($this->lng->txt("select_phrase_to_add"));
			$this->wizardanswers(false);
		}
		else
		{
			if (strcmp($this->object->getPhrase($_POST["phrases"]), "dp_standard_numbers") != 0)
			{
				$this->object->addPhrase($_POST["phrases"]);
				$this->object->saveToDb();
			}
			else
			{
				$this->addStandardNumbers();
				return;
			}
			ilUtil::sendSuccess($this->lng->txt('phrase_added'), true);
			$this->ctrl->redirect($this, 'editQuestion');
		}
	}

	/**
	* Creates an output for the addition of standard numbers
	*
	* @access public
	*/
	function addStandardNumbers() 
	{
		$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_qpl_addphrase_standard_numbers.html", "Modules/SurveyQuestionPool");

		// set the id to return to the selected question
		$this->tpl->setCurrentBlock("hidden");
		$this->tpl->setVariable("HIDDEN_NAME", "id");
		$this->tpl->setVariable("HIDDEN_VALUE", $this->object->getId());
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("adm_content");
		$this->tpl->setVariable("ADD_STANDARD_NUMBERS", $this->lng->txt("add_standard_numbers"));
		$this->tpl->setVariable("TEXT_ADD_LIMITS", $this->lng->txt("add_limits_for_standard_numbers"));
		$this->tpl->setVariable("TEXT_LOWER_LIMIT",$this->lng->txt("lower_limit"));
		$this->tpl->setVariable("TEXT_UPPER_LIMIT",$this->lng->txt("upper_limit"));
		$this->tpl->setVariable("VALUE_LOWER_LIMIT", $_POST["lower_limit"]);
		$this->tpl->setVariable("VALUE_UPPER_LIMIT", $_POST["upper_limit"]);
		$this->tpl->setVariable("BTN_ADD",$this->lng->txt("add_phrase"));
		$this->tpl->setVariable("BTN_CANCEL",$this->lng->txt("cancel"));
		$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));
		$this->tpl->parseCurrentBlock();
	}

	/**
	* Cancels the form adding standard numbers
	*
	* @access public
	*/
	function cancelStandardNumbers() 
	{
		ilUtil::sendInfo($this->lng->txt("msg_cancel"), true);
		$this->ctrl->redirect($this, "editQuestion");
	}

	/**
	* Insert standard numbers to the question
	*
	* @access public
	*/
	function insertStandardNumbers() 
	{
		if ((strcmp($_POST["lower_limit"], "") == 0) or (strcmp($_POST["upper_limit"], "") == 0))
		{
			ilUtil::sendInfo($this->lng->txt("missing_upper_or_lower_limit"));
			$this->addStandardNumbers();
		}
		else if ((int)$_POST["upper_limit"] <= (int)$_POST["lower_limit"])
		{
			ilUtil::sendInfo($this->lng->txt("upper_limit_must_be_greater"));
			$this->addStandardNumbers();
		}
		else
		{
			$this->object->addStandardNumbers($_POST["lower_limit"], $_POST["upper_limit"]);
			$this->object->saveToDb();
			ilUtil::sendSuccess($this->lng->txt('phrase_added'), true);
			$this->ctrl->redirect($this, "editQuestion");
		}
	}

	/**
	* Creates an output to save the current answers as a phrase
	*
	* @access public
	*/
	function savePhraseanswers($haserror = false) 
	{
		if (!$haserror) 
		{
			$result = $this->saveForm();
		}
		if ($result || $haserror)
		{
			if (!$haserror) 
			{
				$this->object->saveToDb();
			}

			$this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_qpl_savephrase.html", "Modules/SurveyQuestionPool");
			$this->tpl->setCurrentBlock("adm_content");
			$this->tpl->setVariable("SAVE_PHRASE_INTRODUCTION", $this->lng->txt("save_phrase_introduction"));
			$this->tpl->setVariable("TEXT_PHRASE_TITLE", $this->lng->txt("enter_phrase_title"));
			$this->tpl->setVariable("VALUE_PHRASE_TITLE", $_POST["phrase_title"]);
			$this->tpl->setVariable("BTN_CANCEL",$this->lng->txt("cancel"));
			$this->tpl->setVariable("BTN_CONFIRM",$this->lng->txt("confirm"));
			$this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this));

			include_once "./Modules/SurveyQuestionPool/classes/tables/class.ilSurveySavePhraseTableGUI.php";
			$table_gui = new ilSurveySavePhraseTableGUI($this, 'editQuestion');
			
			$data = array();
			if (!$haserror)
			{
				foreach ($_POST['answers']['answer'] as $key => $value)
				{
					array_push($data, array('answer' => $value, 'other' => (($_POST['answers']['other'][$key]) ? true : false), 'scale' => $_POST['answers']['scale'][$key], 'neutral' => false));
				}
				if (strlen($_POST['answers']['neutral']))
				{
					array_push($data, array('answer' => $_POST['answers']['neutral'], 'other' => false, 'scale' => $_POST['answers_neutral_scale'], 'neutral' => true));
				}
			}
			else
			{
				$data = $_SESSION['save_phrase_data'];
			}
			$table_gui->setData($data);
			$_SESSION['save_phrase_data'] = $data;
			$this->tpl->setVariable('TABLE', $table_gui->getHTML());	
			$this->tpl->parseCurrentBlock();
		}
	}

	/**
	* Cancels the form saving a phrase
	*
	* @access public
	*/
	function cancelSavePhrase() 
	{
		ilUtil::sendInfo($this->lng->txt("msg_cancel"), true);
		$this->ctrl->redirect($this, "editQuestion");
	}

	/**
	* Save a new phrase to the database
	*
	* @access public
	*/
	function confirmSavePhrase() 
	{
		if (!$_POST["phrase_title"])
		{
			ilUtil::sendInfo($this->lng->txt("qpl_savephrase_empty"));
			$this->savePhraseanswers(true);
			return;
		}

		if ($this->object->phraseExists($_POST["phrase_title"]))
		{
			ilUtil::sendInfo($this->lng->txt("qpl_savephrase_exists"));
			$this->savePhraseanswers(true);
			return;
		}

		$this->object->savePhrase($_POST["phrase_title"]);
		ilUtil::sendSuccess($this->lng->txt("phrase_saved"), true);
		$this->ctrl->redirect($this, "editQuestion");
	}
		
	
	//
	// EXECUTION
	//

	/**
	* Creates the question output form for the learner
	*
	* @access public
	*/
	function getWorkingForm($working_data = "", $question_title = 1, $show_questiontext = 1, $error_message = "", $survey_id = null)
	{
		$template = new ilTemplate("tpl.il_svy_out_sc.html", TRUE, TRUE, "Modules/SurveyQuestionPool");
		$template->setCurrentBlock("material");
		$template->setVariable("TEXT_MATERIAL", $this->getMaterialOutput());
		$template->parseCurrentBlock();
		switch ($this->object->orientation)
		{
			case 0:
				// vertical orientation
				for ($i = 0; $i < $this->object->categories->getCategoryCount(); $i++) 
				{
					$cat = $this->object->categories->getCategory($i);
					if ($cat->other)
					{
						$template->setCurrentBlock("other_row");
						if (strlen($cat->title))
						{
							$template->setVariable("OTHER_LABEL", $cat->title);
						}
						$template->setVariable("VALUE_SC", ($cat->scale) ? ($cat->scale - 1) : $i);
						$template->setVariable("QUESTION_ID", $this->object->getId());
						if (is_array($working_data))
						{
							foreach ($working_data as $value)
							{
								if (strlen($value["value"]))
								{
									if ($value["value"] == $cat->scale-1)
									{
										if (strlen($value['textanswer'])) $template->setVariable("OTHER_VALUE", ' value="' . ilUtil::prepareFormOutput($value['textanswer']) . '"');
										if (!$value['uncheck'])
										{
											$template->setVariable("CHECKED_SC", " checked=\"checked\"");
										}
									}
								}
							}
						}
						$template->parseCurrentBlock();
					}
					else
					{
						$template->setCurrentBlock("row");
						if ($cat->neutral) $template->setVariable('ROWCLASS', ' class="neutral"');
						$template->setVariable("TEXT_SC", ilUtil::prepareFormOutput($cat->title));
						$template->setVariable("VALUE_SC", ($cat->scale) ? ($cat->scale - 1) : $i);
						$template->setVariable("QUESTION_ID", $this->object->getId());
						if (is_array($working_data))
						{
							foreach ($working_data as $value)
							{
								if (strcmp($value["value"], "") != 0)
								{
									if ($value["value"] == $cat->scale-1)
									{
										if (!$value['uncheck'])
										{
											$template->setVariable("CHECKED_SC", " checked=\"checked\"");
										}
									}
								}
							}
						}
						$template->parseCurrentBlock();
					}
					$template->touchBlock('outer_row');
				}
				break;
			case 1:
				// horizontal orientation
				for ($i = 0; $i < $this->object->categories->getCategoryCount(); $i++) 
				{
					$cat = $this->object->categories->getCategory($i);
					$template->setCurrentBlock("radio_col");
					if ($cat->neutral) $template->setVariable('COLCLASS', ' neutral');
					$template->setVariable("VALUE_SC", ($cat->scale) ? ($cat->scale - 1) : $i);
					$template->setVariable("QUESTION_ID", $this->object->getId());
					if (is_array($working_data))
					{
						foreach ($working_data as $value)
						{
							if (strcmp($value["value"], "") != 0)
							{
								if ($value["value"] == $cat->scale-1)
								{
									if (!$value['uncheck'])
									{
										$template->setVariable("CHECKED_SC", " checked=\"checked\"");
									}
								}
							}
						}
					}
					$template->parseCurrentBlock();
				}
				for ($i = 0; $i < $this->object->categories->getCategoryCount(); $i++) 
				{
					$cat = $this->object->categories->getCategory($i);
					if ($cat->other)
					{
						$template->setCurrentBlock("text_other_col");
						$template->setVariable("VALUE_SC", ($cat->scale) ? ($cat->scale - 1) : $i);
						$template->setVariable("QUESTION_ID", $this->object->getId());
						if (strlen($cat->title))
						{
							$template->setVariable("OTHER_LABEL", $cat->title);
						}
						if (is_array($working_data))
						{
							foreach ($working_data as $value)
							{
								if (strlen($value["value"]))
								{
									if ($value["value"] == $cat->scale-1 && strlen($value['textanswer']))
									{
										$template->setVariable("OTHER_VALUE", ' value="' . ilUtil::prepareFormOutput($value['textanswer']) . '"');
									}
								}
							}
						}
						$template->parseCurrentBlock();
					}
					else
					{
						$template->setCurrentBlock("text_col");
						if ($cat->neutral) $template->setVariable('COLCLASS', ' neutral');
						$template->setVariable("VALUE_SC", ($cat->scale) ? ($cat->scale - 1) : $i);
						$template->setVariable("TEXT_SC", ilUtil::prepareFormOutput($cat->title));
						$template->setVariable("QUESTION_ID", $this->object->getId());
						$template->parseCurrentBlock();
					}
					$template->touchBlock('text_outer_col');
				}
				break;
			case 2:
				// combobox output
				for ($i = 0; $i < $this->object->categories->getCategoryCount(); $i++) 
				{
					$cat = $this->object->categories->getCategory($i);
					$template->setCurrentBlock("comborow");
					$template->setVariable("TEXT_SC", $cat->title);
					$template->setVariable("VALUE_SC", ($cat->scale) ? ($cat->scale - 1) : $i);
					if (is_array($working_data))
					{
						if (strcmp($working_data[0]["value"], "") != 0)
						{
							if ($working_data[0]["value"] == $cat->scale-1)
							{
								$template->setVariable("SELECTED_SC", " selected=\"selected\"");
							}
						}
					}
					$template->parseCurrentBlock();
				}
				$template->setCurrentBlock("combooutput");
				$template->setVariable("QUESTION_ID", $this->object->getId());
				$template->setVariable("SELECT_OPTION", $this->lng->txt("select_option"));
				$template->setVariable("TEXT_SELECTION", $this->lng->txt("selection"));
				$template->parseCurrentBlock();
				break;
		}
		if ($question_title)
		{
			$template->setVariable("QUESTION_TITLE", $this->object->getTitle());
		}
		$template->setCurrentBlock("question_data");
		if (strcmp($error_message, "") != 0)
		{
			$template->setVariable("ERROR_MESSAGE", "<p class=\"warning\">$error_message</p>");
		}
		if ($show_questiontext)
		{
			$this->outQuestionText($template);
		}
		$template->parseCurrentBlock();
		return $template->get();
	}

	
	//
	// EVALUTION
	// 
	
	/**
	* Creates the detailed output of the cumulated results for the question
	*
	* @param integer $survey_id The database ID of the survey
	* @param integer $counter The counter of the question position in the survey
	* @return string HTML text with the cumulated results
	* @access private
	*/
	function getCumulatedResultsDetails($survey_id, $counter, $finished_ids)
	{
		if (count($this->cumulated) == 0)
		{
			if(!$finished_ids)
			{
				include_once "./Modules/Survey/classes/class.ilObjSurvey.php";			
				$nr_of_users = ilObjSurvey::_getNrOfParticipants($survey_id);
			}
			else
			{
				$nr_of_users = sizeof($finished_ids);
			}
			$this->cumulated =& $this->object->getCumulatedResults($survey_id, $nr_of_users, $finished_ids);
		}
		$output = "";
		include_once "./Services/UICore/classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_svy_svy_cumulated_results_detail.html", TRUE, TRUE, "Modules/Survey");

		$template->setCurrentBlock("detail_row");
		$template->setVariable("TEXT_OPTION", $this->lng->txt("question"));
		$questiontext = $this->object->getQuestiontext();
		$template->setVariable("TEXT_OPTION_VALUE", $this->object->prepareTextareaOutput($questiontext, TRUE));
		$template->parseCurrentBlock();
		$template->setCurrentBlock("detail_row");
		$template->setVariable("TEXT_OPTION", $this->lng->txt("question_type"));
		$template->setVariable("TEXT_OPTION_VALUE", $this->lng->txt($this->getQuestionType()));
		$template->parseCurrentBlock();
		$template->setCurrentBlock("detail_row");
		$template->setVariable("TEXT_OPTION", $this->lng->txt("users_answered"));
		$template->setVariable("TEXT_OPTION_VALUE", $this->cumulated["USERS_ANSWERED"]);
		$template->parseCurrentBlock();
		$template->setCurrentBlock("detail_row");
		$template->setVariable("TEXT_OPTION", $this->lng->txt("users_skipped"));
		$template->setVariable("TEXT_OPTION_VALUE", $this->cumulated["USERS_SKIPPED"]);
		$template->parseCurrentBlock();
		/*
		$template->setCurrentBlock("detail_row");
		$template->setVariable("TEXT_OPTION", $this->lng->txt("mode"));
		$template->setVariable("TEXT_OPTION_VALUE", $this->cumulated["MODE"]);
		$template->parseCurrentBlock();
		$template->setCurrentBlock("detail_row");
		$template->setVariable("TEXT_OPTION", $this->lng->txt("mode_nr_of_selections"));
		$template->setVariable("TEXT_OPTION_VALUE", $this->cumulated["MODE_NR_OF_SELECTIONS"]);
		$template->parseCurrentBlock();
		*/
		$template->setCurrentBlock("detail_row");
		$template->setVariable("TEXT_OPTION", $this->lng->txt("median"));
		$template->setVariable("TEXT_OPTION_VALUE", $this->cumulated["MEDIAN"]);
		$template->parseCurrentBlock();
		
		$template->setCurrentBlock("detail_row");
		$template->setVariable("TEXT_OPTION", $this->lng->txt("categories"));
		$categories = "";
		foreach ($this->cumulated["variables"] as $key => $value)
		{
			$categories .= "<li>" . $value["title"] . ": n=" . $value["selected"] . 
				" (" . sprintf("%.2f", 100*$value["percentage"]) . "%)</li>";
		}
		$categories = "<ol>$categories</ol>";
		$template->setVariable("TEXT_OPTION_VALUE", $categories);
		$template->parseCurrentBlock();
		
		// add text answers to detailed results
		if (is_array($this->cumulated["textanswers"]))
		{
			$template->setCurrentBlock("detail_row");
			$template->setVariable("TEXT_OPTION", $this->lng->txt("freetext_answers"));	
			$html = "";		
			foreach ($this->cumulated["textanswers"] as $key => $answers)
			{
				$html .= $this->cumulated["variables"][$key]["title"] ."\n";
				$html .= "<ul>\n";
				foreach ($answers as $answer)
				{
					$html .= "<li>" . preg_replace("/\n/", "<br>\n", $answer) . "</li>\n";
				}
				$html .= "</ul>\n";
			}
			$template->setVariable("TEXT_OPTION_VALUE", $html);
			$template->parseCurrentBlock();
		}			
				
		// chart 
		$template->setCurrentBlock("detail_row");				
		$template->setVariable("TEXT_OPTION", $this->lng->txt("chart"));
		$template->setVariable("TEXT_OPTION_VALUE", $this->renderChart("svy_ch_".$this->object->getId(), $this->cumulated["variables"]));
		$template->parseCurrentBlock();
		
		$template->setVariable("QUESTION_TITLE", "$counter. ".$this->object->getTitle());
		return $template->get();
	}
}
?>
