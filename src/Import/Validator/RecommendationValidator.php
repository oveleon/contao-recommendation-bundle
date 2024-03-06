<?php

namespace Oveleon\ContaoRecommendationBundle\Import\Validator;

use Oveleon\ContaoRecommendationBundle\Model\RecommendationModel;

/**
 * Validator class for validating the recommendation item records during and after import.
 *
 * @author Daniele Sciannimanica <https://github.com/doishub>
 */
class RecommendationValidator
{
    static public function getTrigger(): string
    {
        return RecommendationModel::getTable();
    }

    static public function getModel(): string
    {
        return RecommendationModel::class;
    }
}
