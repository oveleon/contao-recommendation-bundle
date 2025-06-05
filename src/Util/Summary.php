<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoRecommendationBundle\Util;

use Contao\Controller;
use Contao\Database;
use Contao\Date;
use Contao\FrontendTemplate;
use Contao\StringUtil;
use Contao\System;

class Summary
{
    protected $arrArchives;

    protected $intTotal;

    protected $blnFeatured;

    protected $minRating;

    protected $intPrecision;

    public function __construct(array $recommendationArchives, int $intTotal, bool $blnFeatured=null, $minRating=null, int $intPrecision=1)
    {
        $this->arrArchives = $recommendationArchives;
        $this->intTotal = $intTotal;
        $this->blnFeatured = $blnFeatured;
        $this->minRating = $minRating;
        $this->intPrecision = $intPrecision;
    }

    public function generate()
    {
        $average = $this->getAverageRating();
        $averageRounded = round($average, $this->intPrecision);

        $objTemplate = new FrontendTemplate('recommendationsummary');

        $objTemplate->average = $average;
        $objTemplate->averageRounded = $averageRounded;
        $objTemplate->averageLabel = sprintf(StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['recommendationAverageLabel']), $average);
        $objTemplate->averageRoundedLabel = sprintf(StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['recommendationAverageLabel']), $averageRounded);
        $objTemplate->countLabel = sprintf(StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['recommendationCount']), $this->intTotal);
        $objTemplate->summary = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['recommendationSummary']);

        return $objTemplate->parse();
    }

    protected function getAverageRating(): float
    {
        if (empty($this->arrArchives) || !\is_array($this->arrArchives))
        {
            return 0.0;
        }

        $t = 'tl_recommendation';
        $objDatabase = Database::getInstance();

        $arrWhere = ["$t.pid IN(" . implode(',', array_map('\intval', $this->arrArchives)) . ")", "$t.verified='1'"];
        $arrValues = [];

        if ($this->blnFeatured !== null)
        {
            $arrWhere[] = "$t.featured=?";
            $arrValues[] = $this->blnFeatured ? '1' : '';
        }

        if ($this->minRating > 1)
        {
            $arrWhere[] = "$t.rating >= ?";
            $arrValues[] = (int) $this->minRating;
        }

        if (!System::getContainer()->get('contao.security.token_checker')->isPreviewMode())
        {
            $time = Date::floorToMinute();
            $arrWhere[] = "($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'" . ($time + 60) . "') AND $t.published='1'";
        }

        $strWhere = implode(' AND ', $arrWhere);

        $objResult = $objDatabase
            ->prepare("SELECT AVG($t.rating) AS avgRating FROM $t WHERE $strWhere")
            ->execute(...$arrValues);

        return (float) $objResult->avgRating;
    }
}

class_alias(Summary::class, 'Summary');
