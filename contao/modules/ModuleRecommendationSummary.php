<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoRecommendationBundle;

use Contao\BackendTemplate;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\Input;
use Contao\Model\Collection;
use Contao\Pagination;
use Contao\StringUtil;
use Contao\System;
use Oveleon\ContaoRecommendationBundle\Model\RecommendationArchiveModel;
use Oveleon\ContaoRecommendationBundle\Model\RecommendationModel;
use Oveleon\ContaoRecommendationBundle\Util\Summary;

/**
 * Front end module "recommendation summary".
 *
 * @property array  $recommendation_archives
 * @property string $recommendation_featured
 * @property string $recommendation_order
 */
class ModuleRecommendationSummary extends ModuleRecommendation
{
    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_recommendationsummary';

    /**
     * Display a wildcard in the back end
     *
     * @return string
     */
    public function generate(): string
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
        {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['recommendationsummary'][0] . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

            return $objTemplate->parse();
        }

        $this->recommendation_archives = $this->sortOutProtected(StringUtil::deserialize($this->recommendation_archives));

        // Return if there are no archives
        if (empty($this->recommendation_archives) || !\is_array($this->recommendation_archives))
        {
            return '';
        }

        // Tag recommendation archives
        if (System::getContainer()->has('fos_http_cache.http.symfony_response_tagger'))
        {
            $responseTagger = System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
            $responseTagger->addTags(array_map(static function ($id) { return 'contao.db.tl_recommendation_archive.' . $id; }, $this->recommendation_archives));
        }

        return parent::generate();
    }

    /**
     * Generate the module
     */
    protected function compile()
    {
        $minRating = $this->recommendation_minRating;

        // Handle featured recommendations
        if ($this->recommendation_featured == 'featured')
        {
            $blnFeatured = true;
        }
        elseif ($this->recommendation_featured == 'unfeatured')
        {
            $blnFeatured = false;
        }
        else
        {
            $blnFeatured = null;
        }

        // Get the total number of items
        $intTotal = $this->countItems($this->recommendation_archives, $blnFeatured, $minRating);

        // Add summary details
        $objSummary = new Summary($this->recommendation_archives, $intTotal, $blnFeatured, $minRating);
        $this->Template->summary = $objSummary->generate();
    }

    /**
     * Count the total matching items
     *
     * @param array   $recommendationArchives
     * @param boolean $blnFeatured
     *
     * @return integer
     */
    protected function countItems($recommendationArchives, $blnFeatured, $minRating)
    {
        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['recommendationSummaryCountItems']) && \is_array($GLOBALS['TL_HOOKS']['recommendationSummaryCountItems']))
        {
            foreach ($GLOBALS['TL_HOOKS']['recommendationSummaryCountItems'] as $callback)
            {
                if (($intResult = System::importStatic($callback[0])->{$callback[1]}($recommendationArchives, $blnFeatured, $this)) === false)
                {
                    continue;
                }

                if (\is_int($intResult))
                {
                    return $intResult;
                }
            }
        }

        return RecommendationModel::countPublishedByPids($recommendationArchives, $blnFeatured, $minRating);
    }
}

class_alias(ModuleRecommendationSummary::class, 'ModuleRecommendationSummary');
