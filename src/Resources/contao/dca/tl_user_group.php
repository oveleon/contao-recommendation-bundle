<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

// Extend the default palette
Contao\CoreBundle\DataContainer\PaletteManipulator::create()
    ->addLegend('recommendation_legend', 'amg_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_BEFORE)
    ->addField(array('recommendations', 'recommendationp'), 'recommendation_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_user_group')
;

// Add fields to tl_user_group
$GLOBALS['TL_DCA']['tl_user_group']['fields']['recommendations'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_user']['recommendations'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'foreignKey'              => 'tl_recommendation_archive.title',
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_user_group']['fields']['recommendationp'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_user']['recommendationp'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options'                 => array('create', 'delete'),
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);
