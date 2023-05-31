<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

use Contao\Config;
use Contao\DC_File;

$GLOBALS['TL_DCA']['tl_recommendation_settings'] = [

    // Config
    'config' => [
        'dataContainer'               => DC_File::class,
        'closed'                      => true
    ],

    // Palettes
    'palettes' => [
        'default'                     => '{recommendation_legend},recommendationDefaultImage,recommendationActiveColor;'
    ],

    // Fields
    'fields' => [
        'recommendationDefaultImage' => [
            'inputType'               => 'fileTree',
            'eval'                    => ['fieldType'=>'radio', 'filesOnly'=>true, 'isGallery'=>true, 'extensions'=> Config::get('validImageTypes'), 'tl_class'=>'clr']
        ],
        'recommendationActiveColor' => [
            'inputType'               => 'text',
            'eval'                    => ['maxlength'=>6, 'multiple'=>true, 'size'=>1, 'colorpicker'=>true, 'isHexColor'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50 wizard'],
        ]
    ]
];
