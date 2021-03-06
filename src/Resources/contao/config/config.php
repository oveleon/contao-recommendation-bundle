<?php

declare(strict_types=1);

// Back end modules
array_insert($GLOBALS['BE_MOD']['content'], 5, array
(
    'recommendation' => array
    (
        'tables'      => array('tl_recommendation_archive', 'tl_recommendation')
    )
));
array_insert($GLOBALS['BE_MOD']['system'], 3, array
(
    'recommendation_settings' => array
    (
        'tables'            => array('tl_recommendation_settings'),
        'hideInNavigation'  => true
    )
));

// Front end modules
array_insert($GLOBALS['FE_MOD'], 2, array
(
    'recommendation' => array
    (
        'recommendationlist'    => '\\Oveleon\\ContaoRecommendationBundle\\ModuleRecommendationList',
        'recommendationreader'  => '\\Oveleon\\ContaoRecommendationBundle\\ModuleRecommendationReader',
    )
));

// Models
$GLOBALS['TL_MODELS']['tl_recommendation']         = '\\Oveleon\\ContaoRecommendationBundle\\RecommendationModel';
$GLOBALS['TL_MODELS']['tl_recommendation_archive'] = '\\Oveleon\\ContaoRecommendationBundle\\RecommendationArchiveModel';

// Add permissions
$GLOBALS['TL_PERMISSIONS'][] = 'recommendations';
$GLOBALS['TL_PERMISSIONS'][] = 'recommendationp';

// Style sheet
if (TL_MODE == 'BE')
{
    $GLOBALS['TL_CSS'][] = 'bundles/contaorecommendation/style.css|static';
}
