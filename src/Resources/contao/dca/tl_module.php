<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

Contao\System::loadLanguageFile('tl_recommendation');

// Add a palette selector
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'recommendation_activate';

// Add palettes to tl_module
$GLOBALS['TL_DCA']['tl_module']['palettes']['recommendationlist']    = '{title_legend},name,headline,type;{config_legend},recommendation_archives,numberOfItems,recommendation_featured,perPage,skipFirst;{template_legend:hide},recommendation_metaFields,recommendation_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['recommendationreader']  = '{title_legend},name,headline,type;{config_legend},recommendation_archives;{template_legend:hide},recommendation_metaFields,recommendation_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['recommendationform']    = '{title_legend},name,headline,type;{config_legend},recommendation_archive,recommendation_optionalFormFields,recommendation_notify,recommendation_moderate,recommendation_disableCaptcha;{privacy_legend},recommendation_privacyText;{redirect_legend:hide},jumpTo;{email_legend:hide},recommendation_activate;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['recommendation_activate'] = 'recommendation_activateJumpTo,recommendation_activateText';

// Add fields to tl_module
$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_archives'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_archives'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options_callback'        => array('tl_module_recommendation', 'getRecommendationArchives'),
	'eval'                    => array('multiple'=>true, 'mandatory'=>true),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_archive'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_archive'],
    'exclude'                 => true,
    'inputType'               => 'select',
    'options_callback'        => array('tl_module_recommendation', 'getRecommendationArchives'),
    'eval'                    => array('mandatory'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50 clr'),
    'sql'                     => "int(10) unsigned NOT NULL default 0"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_featured'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_featured'],
	'default'                 => 'all_items',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => array('all_items', 'featured', 'unfeatured'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_recommendation'],
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "varchar(16) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_metaFields'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_metaFields'],
	'default'                 => array('date', 'author'),
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options'                 => array('date', 'author', 'rating', 'location'),
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('multiple'=>true),
	'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_optionalFormFields'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_optionalFormFields'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'options'                 => array('title', 'location', 'email'),
    'reference'               => &$GLOBALS['TL_LANG']['tl_recommendation'],
    'eval'                    => array('multiple'=>true, 'tl_class'=>'w50 clr'),
    'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_notify'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_notify'],
	'default'				  => true,
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50 clr'),
	'sql'                     => "char(1) NOT NULL default '1'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_moderate'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_moderate'],
	'default'				  => true,
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "char(1) NOT NULL default '1'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_disableCaptcha'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_disableCaptcha'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_disableCaptcha'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_disableCaptcha'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_privacyText'] = array
(
	'exclude'                 => true,
	'inputType'               => 'textarea',
	'eval'                    => array('style'=>'height:100px', 'allowHtml'=>true),
	'sql'                     => "text NULL"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_activate'] = array
(
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('submitOnChange'=>true),
	'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_activateJumpTo'] = array
(
	'exclude'                 => true,
	'inputType'               => 'pageTree',
	'foreignKey'              => 'tl_page.title',
	'eval'                    => array('fieldType'=>'radio'),
	'sql'                     => "int(10) unsigned NOT NULL default 0",
	'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_activateText'] = array
(
	'exclude'                 => true,
	'inputType'               => 'textarea',
	'eval'                    => array('style'=>'height:120px', 'decodeEntities'=>true, 'alwaysSave'=>true),
	'load_callback' => array
	(
		array('tl_module_recommendation', 'getRecommendationActivationDefault')
	),
	'sql'                     => "text NULL"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_template'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_template'],
	'default'                 => 'recommendation_latest',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback'        => array('tl_module_recommendation', 'getRecommendationTemplates'),
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "varchar(64) NOT NULL default ''"
);


/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Fabian Ekert <fabian@oveleon.de>
 */
class tl_module_recommendation extends Backend
{

	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	/**
	 * Get all recommendation archives and return them as array
	 *
	 * @return array
	 */
	public function getRecommendationArchives()
	{
		if (!$this->User->isAdmin && !\is_array($this->User->recommendations))
		{
			return array();
		}

		$arrArchives = array();
		$objArchives = $this->Database->execute("SELECT id, title FROM tl_recommendation_archive ORDER BY title");

		while ($objArchives->next())
		{
			if ($this->User->hasAccess($objArchives->id, 'recommendations'))
			{
				$arrArchives[$objArchives->id] = $objArchives->title;
			}
		}

		return $arrArchives;
	}

	/**
	 * Load the default recommendation activation text
	 *
	 * @param mixed $varValue
	 *
	 * @return mixed
	 */
	public function getRecommendationActivationDefault($varValue)
	{
		if (trim($varValue) === '')
		{
			$varValue = (is_array($GLOBALS['TL_LANG']['tl_recommendation']['emailActivationText']) ? $GLOBALS['TL_LANG']['tl_recommendation']['emailActivationText'][1] : $GLOBALS['TL_LANG']['tl_recommendation']['emailActivationText']);
		}

		return $varValue;
	}

	/**
	 * Return all recommendation templates as array
	 *
	 * @return array
	 */
	public function getRecommendationTemplates()
	{
		return $this->getTemplateGroup('recommendation_');
	}
}
