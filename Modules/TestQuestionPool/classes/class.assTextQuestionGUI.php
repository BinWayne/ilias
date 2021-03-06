<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once './Modules/TestQuestionPool/classes/class.assQuestionGUI.php';
require_once './Modules/TestQuestionPool/interfaces/interface.ilGuiQuestionScoringAdjustable.php';
require_once './Modules/TestQuestionPool/interfaces/interface.ilGuiAnswerScoringAdjustable.php';
require_once './Modules/Test/classes/inc.AssessmentConstants.php';

/**
 * Text question GUI representation
 *
 * The assTextQuestionGUI class encapsulates the GUI representation for text questions.
 *
 * @author	Helmut Schottmüller <helmut.schottmueller@mac.com>
 * @author	Björn Heyser <bheyser@databay.de>
 * @author	Maximilian Becker <mbecker@databay.de>
 *
 * @version	$Id$
 *
 * @ingroup ModulesTestQuestionPool
 */
class assTextQuestionGUI extends assQuestionGUI implements ilGuiQuestionScoringAdjustable, ilGuiAnswerScoringAdjustable
{
	/**
	 * assTextQuestionGUI constructor
	 *
	 * The constructor takes possible arguments an creates an instance of the assTextQuestionGUI object.
	 *
	 * @param integer $id The database id of a text question object
	 */
	public function __construct($id = -1)
	{
		parent::__construct();
		include_once "./Modules/TestQuestionPool/classes/class.assTextQuestion.php";
		$this->object = new assTextQuestion();
		if ($id >= 0)
		{
			$this->object->loadFromDb($id);
		}
	}

	/**
	 * Evaluates a posted edit form and writes the form data in the question object
	 *
	 * @param bool $always
	 *
	 * @return integer A positive value, if one of the required fields wasn't set, else 0
	 */
	public function writePostData($always = false)
	{
		$hasErrors = (!$always) ? $this->editQuestion(true) : false;
		if (!$hasErrors)
		{
			$this->writeQuestionGenericPostData();
			$this->writeQuestionSpecificPostData();
			$this->writeAnswerSpecificPostData();
			$this->saveTaxonomyAssignments();
			return 0;
		}
		return 1;
	}

	/**
	* Creates an output of the edit form for the question
	*
	* @access public
	*/
	public function editQuestion($checkonly = FALSE)
	{
		$save = $this->isSaveCommand();
		$this->getQuestionTemplate();

		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->outQuestionType());
		$form->setMultipart(TRUE);
		$form->setTableWidth("100%");
		$form->setId("asstextquestion");

		$this->addBasicQuestionFormProperties( $form );
		$this->populateQuestionSpecificFormPart( $form );
		$this->populateAnswerSpecificFormPart( $form );

		
		$this->populateTaxonomyFormSection($form);
		
		$this->addQuestionFormCommandButtons($form);
		
		$errors = false;
	
		if ($save)
		{
			$form->setValuesByPost();
			$errors = !$form->checkInput();
			$form->setValuesByPost(); // again, because checkInput now performs the whole stripSlashes handling and we need this if we don't want to have duplication of backslashes
			if ($errors) $checkonly = false;
		}

		if (!$checkonly) $this->tpl->setVariable("QUESTION_DATA", $form->getHTML());
		return $errors;
	}
	
	private static function buildAnswerTextOnlyArray($answers)
	{
		$answerTexts = array();
		
		foreach($answers as $answer)
		{
			$answerTexts[] = $answer->getAnswertext();
		}
		
		return $answerTexts;
	}

	function outAdditionalOutput()
	{
		if ($this->object->getMaxNumOfChars() > 0)
		{
			$this->tpl->addBlockFile("CONTENT_BLOCK", "charcounter", "tpl.charcounter.html", "Modules/TestQuestionPool");
			$this->tpl->setCurrentBlock("charcounter");
			$this->tpl->setVariable("MAXCHARS", $this->object->getMaxNumOfChars());
			$this->tpl->parseCurrentBlock();
		}
	}

	function outQuestionForTest($formaction, $active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE)
	{
		$test_output = $this->getTestOutput($active_id, $pass, $is_postponed, $use_post_solutions); 
		$this->tpl->setVariable("QUESTION_OUTPUT", $test_output);
		$this->tpl->setVariable("FORMACTION", $formaction);
		include_once "./Services/RTE/classes/class.ilRTE.php";
		$rtestring = ilRTE::_getRTEClassname();
		include_once "./Services/RTE/classes/class.$rtestring.php";
		$rte = new $rtestring();
		include_once "./Services/Object/classes/class.ilObject.php";
		$obj_id = ilObject::_lookupObjectId($_GET["ref_id"]);
		$obj_type = ilObject::_lookupType($_GET["ref_id"], TRUE);
		$rte->addUserTextEditor("textinput");
		$this->outAdditionalOutput();
	}

	/**
	* Get the question solution output
	*
	* @param integer $active_id The active user id
	* @param integer $pass The test pass
	* @param boolean $graphicalOutput Show visual feedback for right/wrong answers
	* @param boolean $result_output Show the reached points for parts of the question
	* @param boolean $show_question_only Show the question without the ILIAS content around
	* @param boolean $show_feedback Show the question feedback
	* @param boolean $show_correct_solution Show the correct solution instead of the user solution
	* @param boolean $show_manual_scoring Show specific information for the manual scoring output
	* @return The solution output of the question as HTML code
	*/
	function getSolutionOutput(
		$active_id,
		$pass = NULL,
		$graphicalOutput = FALSE,
		$result_output = FALSE,
		$show_question_only = TRUE,
		$show_feedback = FALSE,
		$show_correct_solution = FALSE,
		$show_manual_scoring = FALSE,
		$show_question_text = TRUE
	)
	{
		// get the solution of the user for the active pass or from the last pass if allowed
		
		$user_solution = $this->getUserAnswer( $active_id, $pass );
		
		if (($active_id > 0) && (!$show_correct_solution))
		{
			$solution = $user_solution;
		}
		else
		{
			$solution = $this->getBestAnswer();
		}
		
		// generate the question output
		include_once "./Services/UICore/classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_text_question_output_solution.html", TRUE, TRUE, "Modules/TestQuestionPool");
		$solutiontemplate = new ilTemplate("tpl.il_as_tst_solution_output.html",TRUE, TRUE, "Modules/TestQuestionPool");
		$solution = htmlentities($solution, ENT_COMPAT | ENT_HTML401, 'UTF-8');
		$template->setVariable("ESSAY", $this->object->prepareTextareaOutput($solution, TRUE));
		
		$questiontext = $this->object->getQuestion();
		
		if (!$show_correct_solution)
		{
			$max_no_of_chars = $this->object->getMaxNumOfChars();
			
			if ($max_no_of_chars == 0)
			{
				$max_no_of_chars = ucfirst($this->lng->txt('unlimited'));
			}
			
			$act_no_of_chars = strlen($user_solution);
			$template->setVariable("CHARACTER_INFO", '<b>' . $max_no_of_chars . '</b>' . 
				$this->lng->txt('answer_characters') . ' <b>' . $act_no_of_chars . '</b>');
		}
		if (($active_id > 0) && (!$show_correct_solution))
		{
			if ($graphicalOutput)
			{
				// output of ok/not ok icons for user entered solutions
				$reached_points = $this->object->getReachedPoints($active_id, $pass);
				if ($reached_points == $this->object->getMaximumPoints())
				{
					$template->setCurrentBlock("icon_ok");
					$template->setVariable("ICON_OK", ilUtil::getImagePath("icon_ok.png"));
					$template->setVariable("TEXT_OK", $this->lng->txt("answer_is_right"));
					$template->parseCurrentBlock();
				}
				else
				{
					$template->setCurrentBlock("icon_ok");
					if ($reached_points > 0)
					{
						$template->setVariable("ICON_NOT_OK", ilUtil::getImagePath("icon_mostly_ok.png"));
						$template->setVariable("TEXT_NOT_OK", $this->lng->txt("answer_is_not_correct_but_positive"));
					}
					else
					{
						$template->setVariable("ICON_NOT_OK", ilUtil::getImagePath("icon_not_ok.png"));
						$template->setVariable("TEXT_NOT_OK", $this->lng->txt("answer_is_wrong"));
					}
					$template->parseCurrentBlock();
				}
			}
		}
		if ($show_question_text==true)
		{
			$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, TRUE));
		}
		$questionoutput = $template->get();
		
		$feedback = '';
		if($show_feedback)
		{
			$fb = $this->getGenericFeedbackOutput($active_id, $pass);
			$feedback .=  strlen($fb) ? $fb : '';
			
			$fb = $this->getSpecificFeedbackOutput($active_id, $pass);
			$feedback .=  strlen($fb) ? $fb : '';
		}
		if (strlen($feedback)) $solutiontemplate->setVariable("FEEDBACK", $this->object->prepareTextareaOutput( $feedback, true ));
		
		$solutiontemplate->setVariable("SOLUTION_OUTPUT", $questionoutput);

		$solutionoutput = $solutiontemplate->get(); 
		if (!$show_question_only)
		{
			// get page object output
			$solutionoutput = '<div class="ilc_question_Standard">'.$solutionoutput."</div>";
		}
		return $solutionoutput;
	}

	private function getBestAnswer()
	{
		$answers = $this->object->getAnswers();
		if (count( $answers ))
		{
			$user_solution = $this->lng->txt( "solution_contain_keywords" ) . "<ul>";
			
			foreach ($answers as $answer)
			{
				$user_solution .= '<li>'. $answer->getAnswertext();
				
				if( in_array($this->object->getKeywordRelation(), assTextQuestion::getScoringModesWithPointsByKeyword()) )
				{
					$user_solution .= ' ' . $this->lng->txt('for') . ' ';
					$user_solution .= $answer->getPoints() . ' ' . $this->lng->txt('points') . '</li>';
				}
			}
			$user_solution .= '</ul>';
			
			$user_solution .= $this->lng->txt('essay_scoring_mode') . ': ';
			
			switch( $this->object->getKeywordRelation() )
			{
				case 'any':
					$user_solution .= $this->lng->txt('essay_scoring_mode_keyword_relation_any');
					break;
				case 'all':
					$user_solution .= $this->lng->txt('essay_scoring_mode_keyword_relation_all');
					break;
				case 'one':
					$user_solution .= $this->lng->txt('essay_scoring_mode_keyword_relation_one');
					break;
			}
		}
		return $user_solution;
	}

	private function getUserAnswer($active_id, $pass)
	{
		$user_solution = "";
		$solutions     = $this->object->getSolutionValues( $active_id, $pass );
		foreach ($solutions as $idx => $solution_value)
		{
			$user_solution = $solution_value["value1"];
		}
		return $user_solution;
	}

	function getPreview($show_question_only = FALSE)
	{
		// generate the question output
		include_once "./Services/UICore/classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_text_question_output.html", TRUE, TRUE, "Modules/TestQuestionPool");
		if ($this->object->getMaxNumOfChars())
		{
			$template->setCurrentBlock("maximum_char_hint");
			$template->setVariable("MAXIMUM_CHAR_HINT", sprintf($this->lng->txt("text_maximum_chars_allowed"), $this->object->getMaxNumOfChars()));
			$template->parseCurrentBlock();
			#mbecker: No such block. $template->setCurrentBlock("has_maxchars");
			$template->setVariable("MAXCHARS", $this->object->getMaxNumOfChars());
			$template->parseCurrentBlock();
			$template->setCurrentBlock("maxchars_counter");
			$template->setVariable("MAXCHARS", $this->object->getMaxNumOfChars());
			$template->setVariable("TEXTBOXSIZE", strlen($this->object->getMaxNumOfChars()));
			$template->setVariable("CHARACTERS", $this->lng->txt("characters"));
			$template->parseCurrentBlock();
		}
		$questiontext = $this->object->getQuestion();
		$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, TRUE));
		$questionoutput = $template->get();
		if (!$show_question_only)
		{
			// get page object output
			$questionoutput = $this->getILIASPage($questionoutput);
		}
		return $questionoutput;
	}

	function getTestOutput($active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE)
	{
		// get the solution of the user for the active pass or from the last pass if allowed
		$user_solution = "";
		if ($active_id)
		{
			$solutions = NULL;
			include_once "./Modules/Test/classes/class.ilObjTest.php";
			if (!ilObjTest::_getUsePreviousAnswers($active_id, true))
			{
				if (is_null($pass)) $pass = ilObjTest::_getPass($active_id);
			}
			$solutions =& $this->object->getSolutionValues($active_id, $pass);
			foreach ($solutions as $idx => $solution_value)
			{
				$user_solution = $solution_value["value1"];
			}
		}
		
		// generate the question output
		include_once "./Services/UICore/classes/class.ilTemplate.php";
		$template = new ilTemplate("tpl.il_as_qpl_text_question_output.html", TRUE, TRUE, "Modules/TestQuestionPool");
		if ($this->object->getMaxNumOfChars())
		{
			$template->setCurrentBlock("maximum_char_hint");
			$template->setVariable("MAXIMUM_CHAR_HINT", sprintf($this->lng->txt("text_maximum_chars_allowed"), $this->object->getMaxNumOfChars()));
			$template->parseCurrentBlock();
			#mbecker: No such block. $template->setCurrentBlock("has_maxchars");
			$template->setVariable("MAXCHARS", $this->object->getMaxNumOfChars());
			$template->parseCurrentBlock();
			$template->setCurrentBlock("maxchars_counter");
			$template->setVariable("MAXCHARS", $this->object->getMaxNumOfChars());
			$template->setVariable("TEXTBOXSIZE", strlen($this->object->getMaxNumOfChars()));
			$template->setVariable("CHARACTERS", $this->lng->txt("characters"));
			$template->parseCurrentBlock();
		}
		$template->setVariable("ESSAY", ilUtil::prepareFormOutput($user_solution));
		$questiontext = $this->object->getQuestion();
		$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, TRUE));
		$questionoutput = $template->get();
		$pageoutput = $this->outQuestionPage("", $is_postponed, $active_id, $questionoutput);
		include_once "./Services/YUI/classes/class.ilYuiUtil.php";
		ilYuiUtil::initDomEvent();
		return $pageoutput;
	}

	function addSuggestedSolution()
	{
		$_SESSION["subquestion_index"] = 0;
		if ($_POST["cmd"]["addSuggestedSolution"])
		{
			if ($this->writePostData())
			{
				ilUtil::sendInfo($this->getErrorMessage());
				$this->editQuestion();
				return;
			}
			if (!$this->checkInput())
			{
				ilUtil::sendInfo($this->lng->txt("fill_out_all_required_fields_add_answer"));
				$this->editQuestion();
				return;
			}
		}
		$this->object->saveToDb();
		$this->ctrl->setParameter($this, "q_id", $this->object->getId());
		$this->tpl->setVariable("HEADER", $this->object->getTitle());
		$this->getQuestionTemplate();
		parent::addSuggestedSolution();
	}

	/**
	 * Sets the ILIAS tabs for this question type
	 *
	 * @access public
	 * 
	 * @todo:	MOVE THIS STEPS TO COMMON QUESTION CLASS assQuestionGUI
	 */
	function setQuestionTabs()
	{
		global $rbacsystem, $ilTabs;
		
		$this->ctrl->setParameterByClass("ilAssQuestionPageGUI", "q_id", $_GET["q_id"]);
		include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
		$q_type = $this->object->getQuestionType();

		if (strlen($q_type))
		{
			$classname = $q_type . "GUI";
			$this->ctrl->setParameterByClass(strtolower($classname), "sel_question_types", $q_type);
			$this->ctrl->setParameterByClass(strtolower($classname), "q_id", $_GET["q_id"]);
		}

		if ($_GET["q_id"])
		{
			if ($rbacsystem->checkAccess('write', $_GET["ref_id"]))
			{
				// edit page
				$ilTabs->addTarget("edit_page",
					$this->ctrl->getLinkTargetByClass("ilAssQuestionPageGUI", "edit"),
					array("edit", "insert", "exec_pg"),
					"", "", $force_active);
			}
	
			// edit page
			$ilTabs->addTarget("preview",
				$this->ctrl->getLinkTargetByClass("ilAssQuestionPageGUI", "preview"),
				array("preview"),
				"ilAssQuestionPageGUI", "", $force_active);
		}

		$force_active = false;
		if ($rbacsystem->checkAccess('write', $_GET["ref_id"]))
		{
			$url = "";
			if ($classname) $url = $this->ctrl->getLinkTargetByClass($classname, "editQuestion");
			// edit question properties
			$ilTabs->addTarget("edit_question",
				$url,
				array("editQuestion", "save", "saveEdit", "originalSyncForm"),
				$classname, "", $force_active);
		}

		// add tab for question feedback within common class assQuestionGUI
		$this->addTab_QuestionFeedback($ilTabs);

		// add tab for question hint within common class assQuestionGUI
		$this->addTab_QuestionHints($ilTabs);
		
		if ($_GET["q_id"])
		{
			$ilTabs->addTarget("solution_hint",
				$this->ctrl->getLinkTargetByClass($classname, "suggestedsolution"),
				array("suggestedsolution", "saveSuggestedSolution", "outSolutionExplorer", "cancel", 
				"addSuggestedSolution","cancelExplorer", "linkChilds", "removeSuggestedSolution"
				),
				$classname, 
				""
			);
		}

		// Assessment of questions sub menu entry
		if ($_GET["q_id"])
		{
			$ilTabs->addTarget("statistics",
				$this->ctrl->getLinkTargetByClass($classname, "assessment"),
				array("assessment"),
				$classname, "");
		}
		
		if (($_GET["calling_test"] > 0) || ($_GET["test_ref_id"] > 0))
		{
			$ref_id = $_GET["calling_test"];
			if (strlen($ref_id) == 0) $ref_id = $_GET["test_ref_id"];

                        global $___test_express_mode;

                        if (!$_GET['test_express_mode'] && !$___test_express_mode) {
                            $ilTabs->setBackTarget($this->lng->txt("backtocallingtest"), "ilias.php?baseClass=ilObjTestGUI&cmd=questions&ref_id=$ref_id");
                        }
                        else {
                            $link = ilTestExpressPage::getReturnToPageLink();
                            $ilTabs->setBackTarget($this->lng->txt("backtocallingtest"), $link);
                        }
		}
		else
		{
			$ilTabs->setBackTarget($this->lng->txt("qpl"), $this->ctrl->getLinkTargetByClass("ilobjquestionpoolgui", "questions"));
		}
	}

	function getSpecificFeedbackOutput($active_id, $pass)
	{
			$feedback = '<table><tbody>';
			$user_answers = $this->object->getSolutionValues($active_id);
			$user_answer = '  '. $user_answers[0]['value1'];
		
			foreach ($this->object->getAnswers() as $idx => $ans)
			{
				if ($this->object->isKeywordInAnswer($user_answer, $ans->getAnswertext() ))
				{
					$fb = $this->object->feedbackOBJ->getSpecificAnswerFeedbackTestPresentation(
							$this->object->getId(), $idx
					);
					$feedback .= '<tr><td><b><i>' . $ans->getAnswertext() . '</i></b></td><td>';
					$feedback .= $fb . '</td> </tr>';
				}
			}
		
			$feedback .= '</tbody></table>';
			return $this->object->prepareTextareaOutput($feedback, TRUE);
	}

	public function writeQuestionSpecificPostData( $always = true)
	{
		$this->object->setMaxNumOfChars( $_POST["maxchars"] );
		$this->object->setTextRating( $_POST["text_rating"] );
		$this->object->setKeywordRelation( $_POST['scoring_mode'] );
	}

	public function writeAnswerSpecificPostData( $always = true )
	{
		switch ($this->object->getKeywordRelation())
		{
			case 'non':
				$this->object->setAnswers( array() );
				$this->object->setPoints( $_POST['non_keyword_points'] );
				break;
			case 'any':
				$this->object->setAnswers( $_POST['any_keyword'] );
				$this->object->setPoints( $this->object->getMaximumPoints() );
				break;
			case 'all':
				$this->object->setAnswers( $_POST['all_keyword'] );
				$this->object->setPoints( $_POST['all_keyword_points'] );
				break;
			case 'one':
				$this->object->setAnswers( $_POST['one_keyword'] );
				$this->object->setPoints( $_POST['one_keyword_points'] );
				break;
		}
	}

	public function populateQuestionSpecificFormPart(\ilPropertyFormGUI $form)
	{
		// maxchars
		$maxchars = new ilNumberInputGUI($this->lng->txt( "maxchars" ), "maxchars");
		$maxchars->setSize( 5 );
		if ($this->object->getMaxNumOfChars() > 0)
			$maxchars->setValue( $this->object->getMaxNumOfChars() );
		$maxchars->setInfo( $this->lng->txt( "description_maxchars" ) );
		$form->addItem( $maxchars );
		return $form;
	}

	public function populateAnswerSpecificFormPart(\ilPropertyFormGUI $form)
	{
		$scoringMode = new ilRadioGroupInputGUI(
			$this->lng->txt( 'essay_scoring_mode' ), 'scoring_mode'
		);

		$scoringOptionNone = new ilRadioOption($this->lng->txt( 'essay_scoring_mode_without_keywords' ),
											   'non', $this->lng->txt( 'essay_scoring_mode_without_keywords_desc'
			)
		);
		$scoringOptionAnyKeyword = new ilRadioOption($this->lng->txt( 'essay_scoring_mode_keyword_relation_any' ),
													 'any', $this->lng->txt( 'essay_scoring_mode_keyword_relation_any_desc'
			)
		);
		$scoringOptionAllKeyword = new ilRadioOption($this->lng->txt( 'essay_scoring_mode_keyword_relation_all' ),
													 'all', $this->lng->txt( 'essay_scoring_mode_keyword_relation_all_desc'
			)
		);
		$scoringOptionOneKeyword = new ilRadioOption($this->lng->txt( 'essay_scoring_mode_keyword_relation_one' ),
													 'one', $this->lng->txt( 'essay_scoring_mode_keyword_relation_one_desc'
			)
		);

		$scoringMode->addOption( $scoringOptionNone );
		$scoringMode->addOption( $scoringOptionAnyKeyword );
		$scoringMode->addOption( $scoringOptionAllKeyword );
		$scoringMode->addOption( $scoringOptionOneKeyword );
		$scoringMode->setRequired( true );
		$scoringMode->setValue( strlen( $this->object->getKeywordRelation() ) ? $this->object->getKeywordRelation(
								) : 'non'
		);

		if ($this->object->getAnswerCount() == 0)
		{
			$this->object->addAnswer( "", 0, 0, 0 );
		}
		require_once "./Modules/TestQuestionPool/classes/class.ilEssayKeywordWizardInputGUI.php";

		// Without Keywords
		$nonKeywordPoints = new ilNumberInputGUI($this->lng->txt( "points" ), "non_keyword_points");
		$nonKeywordPoints->setValue( $this->object->getPoints() );
		$nonKeywordPoints->setRequired( TRUE );
		$nonKeywordPoints->setSize( 3 );
		$nonKeywordPoints->setMinValue( 0.0 );
		$nonKeywordPoints->setMinvalueShouldBeGreater( true );
		$scoringOptionNone->addSubItem( $nonKeywordPoints );

		// Any Keyword
		$anyKeyword = new ilEssayKeywordWizardInputGUI($this->lng->txt( "answers" ), "any_keyword");
		$anyKeyword->setRequired( TRUE );
		$anyKeyword->setQuestionObject( $this->object );
		$anyKeyword->setSingleline( TRUE );
		$anyKeyword->setValues( $this->object->getAnswers() );
		$scoringOptionAnyKeyword->addSubItem( $anyKeyword );

		// All Keywords
		$allKeyword = new ilTextWizardInputGUI($this->lng->txt( "answers" ), "all_keyword");
		$allKeyword->setRequired( TRUE );
		//$allKeyword->setQuestionObject($this->object);
		//$allKeyword->setSingleline(TRUE);
		$allKeyword->setValues( self::buildAnswerTextOnlyArray( $this->object->getAnswers() ) );
		$scoringOptionAllKeyword->addSubItem( $allKeyword );
		$allKeywordPoints = new ilNumberInputGUI($this->lng->txt( "points" ), "all_keyword_points");
		$allKeywordPoints->setValue( $this->object->getPoints() );
		$allKeywordPoints->setRequired( TRUE );
		$allKeywordPoints->setSize( 3 );
		$allKeywordPoints->setMinValue( 0.0 );
		$allKeywordPoints->setMinvalueShouldBeGreater( true );
		$scoringOptionAllKeyword->addSubItem( $allKeywordPoints );

		// One Keywords
		$oneKeyword = new ilTextWizardInputGUI($this->lng->txt( "answers" ), "one_keyword");
		$oneKeyword->setRequired( TRUE );
		//$oneKeyword->setQuestionObject($this->object);
		//$oneKeyword->setSingleline(TRUE);
		$oneKeyword->setValues( self::buildAnswerTextOnlyArray( $this->object->getAnswers() ) );
		$scoringOptionOneKeyword->addSubItem( $oneKeyword );
		$oneKeywordPoints = new ilNumberInputGUI($this->lng->txt( "points" ), "one_keyword_points");
		$oneKeywordPoints->setValue( $this->object->getPoints() );
		$oneKeywordPoints->setRequired( TRUE );
		$oneKeywordPoints->setSize( 3 );
		$oneKeywordPoints->setMinValue( 0.0 );
		$oneKeywordPoints->setMinvalueShouldBeGreater( true );
		$scoringOptionOneKeyword->addSubItem( $oneKeywordPoints );

		$form->addItem( $scoringMode );
	}

	/**
	 * Returns a list of postvars which will be suppressed in the form output when used in scoring adjustment.
	 * The form elements will be shown disabled, so the users see the usual form but can only edit the settings, which
	 * make sense in the given context.
	 *
	 * E.g. array('cloze_type', 'image_filename')
	 *
	 * @return string[]
	 */
	public function getAfterParticipationSuppressionAnswerPostVars()
	{
		return array();
	}

	/**
	 * Returns a list of postvars which will be suppressed in the form output when used in scoring adjustment.
	 * The form elements will be shown disabled, so the users see the usual form but can only edit the settings, which
	 * make sense in the given context.
	 *
	 * E.g. array('cloze_type', 'image_filename')
	 *
	 * @return string[]
	 */
	public function getAfterParticipationSuppressionQuestionPostVars()
	{
		return array();
	}

	/**
	 * Returns an html string containing a question specific representation of the answers so far
	 * given in the test for use in the right column in the scoring adjustment user interface.
	 *
	 * @param array $relevant_answers
	 *
	 * @return string
	 */
	public function getAggregatedAnswersView($relevant_answers)
	{
		return ''; //print_r($relevant_answers,true);
	}
}