<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

use Contao\Config;
use Contao\DC_File;
use Contao\StringUtil;

$GLOBALS['TL_DCA']['tl_recommendation_settings'] = [

    // Config
    'config' => [
        'dataContainer'               => DC_File::class,
        'closed'                      => true
    ],

    // Palettes
    'palettes' => [
        'default'                     => '{recommendation_legend},recommendationDefaultImage,recommendationActiveColor,recommendationAliasPrefix;'
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
            'save_callback'           => [
                // See contao/issues (#6105)
                static function ($value)
                {
                    if (!\is_array($value))
                    {
                        return StringUtil::restoreBasicEntities($value);
                    }

                    return serialize(array_map('\Contao\StringUtil::restoreBasicEntities', $value));
                }
            ]
        ],
        'recommendationAliasPrefix' => [
            'inputType'               => 'text',
            'eval'                    => ['rgxp'=>'alias', 'maxlength'=>255, 'tl_class'=>'w50 clr'],
            'save_callback'           => [
                static function ($value)
                {
                    if (!$value)
                    {
                        $value = &$GLOBALS['TL_LANG']['tl_recommendation_settings']['defaultPrefix'];
                    }

                    return $value;
                }
            ]
        ]
    ]
];
