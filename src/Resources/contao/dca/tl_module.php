<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

// Add palettes to tl_module
$GLOBALS['TL_DCA']['tl_module']['palettes']['recommendationlist']    = '{title_legend},name,headline,type;{config_legend},recommendation_archives,numberOfItems,recommendation_featured,perPage,skipFirst;{template_legend:hide},recommendation_metaFields,recommendation_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['recommendationreader']  = '{title_legend},name,headline,type;{config_legend},recommendation_archives;{template_legend:hide},recommendation_metaFields,recommendation_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

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

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_featured'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_featured'],
	'default'                 => 'all_items',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => array('all_items', 'featured', 'unfeatured'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
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
	 * Return all recommendation templates as array
	 *
	 * @return array
	 */
	public function getRecommendationTemplates()
	{
		return $this->getTemplateGroup('recommendation_');
	}
}
