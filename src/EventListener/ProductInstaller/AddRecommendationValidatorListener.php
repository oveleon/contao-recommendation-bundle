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

namespace Oveleon\ContaoRecommendationBundle\EventListener\ProductInstaller;

use Oveleon\ContaoRecommendationBundle\Import\Validator\RecommendationArchiveValidator;
use Oveleon\ProductInstaller\Import\Validator;

class AddRecommendationValidatorListener
{
    public function addValidators(): void
    {
        // Connects jumpTo pages
        Validator::addValidatorCollection([RecommendationArchiveValidator::class], ['setJumpToPageConnection']);
    }
}
