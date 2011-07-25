<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/DidacticTemplate/exceptions/class.ilDidacticTemplateImportException.php';

/**
 * Description of ilDidacticTemplateImport
 *
 * @author Stefan Meyer <meyer@leifos.com>
 * @ingroup ServicesDidacticTemplate
 */
class ilDidacticTemplateImport
{
    const IMPORT_FILE = 1;

	private $type = 0;
	private $xmlfile = '';


	/**
	 * Constructor
	 * @param <type> $a_type
	 */
	public function __construct($a_type)
	{
		$this->type = $a_type;
	}

	/**
	 * Set input file
	 * @param string $a_file
	 */
	public function setInputFile($a_file)
	{
		$this->xmlfile = $a_file;
	}

	/**
	 * Get inputfile
	 * @return <type>
	 */
	public function getInputFile()
	{
		return $this->xmlfile;
	}

	/**
	 * Get input type
	 * @return string
	 */
	public function getInputType()
	{
		return $this->type;
	}

	/**
	 * Do import
	 */
	public function import()
	{
		libxml_use_internal_errors(true);

		switch($this->getInputType())
		{
			case self::IMPORT_FILE:

				$root = simplexml_load_file($this->getInputFile());
				if($root == FALSE)
				{
					throw new ilDidacticTemplateImportException(
						$this->parseXmlErrors()
					);
				}
				break;
		}

		$this->parseSettings($root);

	}

	/**
	 * Parse settings
	 * @param SimpleXMLElement $el
	 * @return ilDidacticTemplateSetting
	 */
	protected function parseSettings(SimpleXMLElement $root)
	{

		include_once './Services/DidacticTemplate/classes/class.ilDidacticTemplateSetting.php';
		$setting = new ilDidacticTemplateSetting();

		foreach($root->didacticTemplate as $tpl)
		{
			switch((string) $tpl->type)
			{
				case 'creation':
				default:
					$setting->setType(ilDidacticTemplateSetting::TYPE_CREATION);
					break;
			}
			$setting->setTitle(trim((string) $tpl->title));
			$setting->setDescription(trim((string) $tpl->description));

			foreach($tpl->assignments->assignment as $element)
			{
				$setting->addAssignment(trim((string) $element));
			}
		}
		$setting->save();
		return $setting;
	}

	/**
	 * Parse xml errors from libxml_get_errors
	 *
	 * @return string
	 */
	protected function parseXmlErrors()
	{
		$errors = '';
		foreach(libxml_get_errors() as $err)
		{
			$errors .= $err->code.'<br/>';
		}
		return $errors;
	}
}
?>