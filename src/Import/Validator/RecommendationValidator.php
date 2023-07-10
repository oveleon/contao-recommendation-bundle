<?php

namespace Oveleon\ContaoRecommendationBundle\Import\Validator;

use Oveleon\ContaoRecommendationBundle\Model\RecommendationModel;
use Oveleon\ProductInstaller\Import\Validator\ValidatorInterface;

/**
 * Validator class for validating the recommendation item records during and after import.
 *
 * @author Daniele Sciannimanica <https://github.com/doishub>
 */
class RecommendationValidator implements ValidatorInterface
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
