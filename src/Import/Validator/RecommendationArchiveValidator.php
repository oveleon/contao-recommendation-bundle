<?php

namespace Oveleon\ContaoRecommendationBundle\Import\Validator;

use Oveleon\ContaoRecommendationBundle\Model\RecommendationArchiveModel;
use Oveleon\ProductInstaller\Import\Validator\ValidatorInterface;

/**
 * Validator class for validating the recommendation records during and after import.
 *
 * @author Daniele Sciannimanica <https://github.com/doishub>
 */
class RecommendationArchiveValidator implements ValidatorInterface
{
    static public function getTrigger(): string
    {
        return RecommendationArchiveModel::getTable();
    }

    static public function getModel(): string
    {
        return RecommendationArchiveModel::class;
    }
}
