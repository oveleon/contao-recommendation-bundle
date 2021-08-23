<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

$GLOBALS['TL_DCA']['tl_recommendation_settings'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'File',
		'closed'                      => true
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{recommendation_legend},recommendationDefaultImage,recommendationActiveColor;'
	),

	// Fields
	'fields' => array
	(
		'recommendationDefaultImage' => array
		(
            'label'                   => &$GLOBALS['TL_LANG']['tl_recommendation_settings']['recommendationDefaultImage'],
            'inputType'               => 'fileTree',
            'eval'                    => array('fieldType'=>'radio', 'filesOnly'=>true, 'isGallery'=>true, 'extensions'=>Contao\Config::get('validImageTypes'), 'tl_class'=>'clr')
		),
        'recommendationActiveColor' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_recommendation_settings']['recommendationActiveColor'],
            'inputType'               => 'text',
            'eval'                    => array('maxlength'=>6, 'multiple'=>true, 'size'=>1, 'colorpicker'=>true, 'isHexColor'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50 wizard'),
        )
	)
);
