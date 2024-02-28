<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * @package     contao-recommendation-bundle
 * @license     AGPL-3.0
 * @author      Daniele Sciannimanica  <https://github.com/doishub>
 * @copyright   Oveleon                <https://www.oveleon.de/>
 */

namespace Oveleon\ContaoRecommendationBundle\EventListener\Import;

use Oveleon\ContaoRecommendationBundle\Import\Validator\RecommendationArchiveValidator;
use Oveleon\ContaoRecommendationBundle\Model\RecommendationArchiveModel;
use Oveleon\ProductInstaller\Import\Validator;
use Oveleon\ProductInstaller\Import\Validator\ModuleValidator;

class AddRecommendationValidatorListener
{
    public function addValidators(): void
    {
        // Connects jumpTo pages
        Validator::addValidatorCollection([RecommendationArchiveValidator::class], ['setJumpToPageConnection']);
    }

    public function setArchiveConnections(array $row): array
    {
        return match ($row['type']) {
            'recommendationlist', 'recommendationreader' => ['field' => 'recommendation_archives', 'table' => RecommendationArchiveModel::getTable()],
            'recommendationform' => ['field' => 'recommendation_archive', 'table' => RecommendationArchiveModel::getTable()],
            default => [],
        };
    }
}
