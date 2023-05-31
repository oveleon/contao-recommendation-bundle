<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

$GLOBALS['TL_DCA']['tl_recommendation_settings'] = [

    // Config
    'config' => [
        'dataContainer'               => 'File',
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
            'eval'                    => ['fieldType'=>'radio', 'filesOnly'=>true, 'isGallery'=>true, 'extensions'=>Contao\Config::get('validImageTypes'), 'tl_class'=>'clr']
        ],
        'recommendationActiveColor' => [
            'inputType'               => 'text',
            'eval'                    => ['maxlength'=>6, 'multiple'=>true, 'size'=>1, 'colorpicker'=>true, 'isHexColor'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50 wizard'],
        ]
    ]
];
