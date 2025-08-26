<?php

declare(strict_types=1);

namespace Oveleon\ContaoRecommendationBundle\Import\Validator;

use Oveleon\ContaoRecommendationBundle\Model\RecommendationArchiveModel;

/**
 * Validator class for validating the recommendation records during and after import.
 *
 * @author Daniele Sciannimanica <https://github.com/doishub>
 */
class RecommendationArchiveValidator
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
