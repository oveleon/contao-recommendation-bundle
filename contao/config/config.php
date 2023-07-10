<?php

declare(strict_types=1);

use Contao\ArrayUtil;
use Oveleon\ContaoRecommendationBundle\ModuleRecommendationForm;
use Oveleon\ContaoRecommendationBundle\ModuleRecommendationList;
use Oveleon\ContaoRecommendationBundle\ModuleRecommendationReader;
use Oveleon\ContaoRecommendationBundle\Model\RecommendationArchiveModel;
use Oveleon\ContaoRecommendationBundle\Model\RecommendationModel;
use Oveleon\ContaoRecommendationBundle\EventListener\ProductInstaller\AddRecommendationValidatorListener;

// Back end modules
ArrayUtil::arrayInsert($GLOBALS['BE_MOD']['content'], 5, [
    'recommendation' => [
        'tables' => ['tl_recommendation_archive', 'tl_recommendation']
    ]
]);

ArrayUtil::arrayInsert($GLOBALS['BE_MOD']['system'], 3, [
    'recommendation_settings' => [
        'tables' => ['tl_recommendation_settings'],
        'hideInNavigation' => true
    ]
]);

// Front end modules
ArrayUtil::arrayInsert($GLOBALS['FE_MOD'], 2, [
    'recommendation' => [
        'recommendationform'    => ModuleRecommendationForm::class,
        'recommendationlist'    => ModuleRecommendationList::class,
        'recommendationreader'  => ModuleRecommendationReader::class
    ]
]);

// Add permissions
$GLOBALS['TL_PERMISSIONS'][] = 'recommendations';
$GLOBALS['TL_PERMISSIONS'][] = 'recommendationp';

// Models
$GLOBALS['TL_MODELS']['tl_recommendation']         = RecommendationModel::class;
$GLOBALS['TL_MODELS']['tl_recommendation_archive'] = RecommendationArchiveModel::class;

// Add product installer validators
$GLOBALS['PI_HOOKS']['addValidator'][] = [AddRecommendationValidatorListener::class, 'addValidators'];
