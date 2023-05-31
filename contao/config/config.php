<?php

declare(strict_types=1);

use Contao\ArrayUtil;

// Back end modules
ArrayUtil::arrayInsert($GLOBALS['BE_MOD']['content'], 5, [
    'recommendation' => [
        'tables' => [
            'tl_recommendation_archive',
            'tl_recommendation'
        ]
    ]
]);

ArrayUtil::arrayInsert($GLOBALS['BE_MOD']['system'], 3, [
    'recommendation_settings' => [
        'tables' => [
            'tl_recommendation_settings'
        ],
        'hideInNavigation' => true
    ]
]);

// Front end modules
ArrayUtil::arrayInsert($GLOBALS['FE_MOD'], 2, [
    'recommendation' => [
        'recommendationlist'    => 'Oveleon\ContaoRecommendationBundle\ModuleRecommendationList',
        'recommendationreader'  => 'Oveleon\ContaoRecommendationBundle\ModuleRecommendationReader',
        'recommendationform'    => 'Oveleon\ContaoRecommendationBundle\ModuleRecommendationForm',
    ]
]);

// Models
$GLOBALS['TL_MODELS']['tl_recommendation']         = 'Oveleon\ContaoRecommendationBundle\RecommendationModel';
$GLOBALS['TL_MODELS']['tl_recommendation_archive'] = 'Oveleon\ContaoRecommendationBundle\RecommendationArchiveModel';

// Add permissions
$GLOBALS['TL_PERMISSIONS'][] = 'recommendations';
$GLOBALS['TL_PERMISSIONS'][] = 'recommendationp';

// Cron jobs
$GLOBALS['TL_CRON']['daily']['purgeRecommendations'] = ['Oveleon\ContaoRecommendationBundle\Recommendation', 'purgeRecommendations'];

// Register hooks
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = ['Oveleon\ContaoRecommendationBundle\Recommendation', 'getSearchablePages'];
