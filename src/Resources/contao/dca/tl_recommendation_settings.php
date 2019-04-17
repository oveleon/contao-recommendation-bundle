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
		'default'                     => '{image_legend},recommendationDefaultImage;'
	),

	// Fields
	'fields' => array
	(
		'recommendationDefaultImage' => array
		(
            'label'                   => &$GLOBALS['TL_LANG']['tl_recommendation_settings']['recommendationDefaultImage'],
            'inputType'               => 'fileTree',
            'eval'                    => array('fieldType'=>'radio', 'filesOnly'=>true, 'isGallery'=>true, 'extensions'=>Config::get('validImageTypes'), 'tl_class'=>'clr')
		)
	)
);
