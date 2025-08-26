<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Extend the default palette
PaletteManipulator::create()
    ->addLegend('recommendation_legend', 'amg_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField(['recommendations', 'recommendationp'], 'recommendation_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_user_group')
;

// Add fields to tl_user_group
$GLOBALS['TL_DCA']['tl_user_group']['fields']['recommendations'] = [
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'foreignKey'              => 'tl_recommendation_archive.title',
    'eval'                    => ['multiple'=>true],
    'sql'                     => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_user_group']['fields']['recommendationp'] = [
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'options'                 => ['create', 'delete'],
    'reference'               => &$GLOBALS['TL_LANG']['MSC'],
    'eval'                    => ['multiple'=>true],
    'sql'                     => "blob NULL"
];
