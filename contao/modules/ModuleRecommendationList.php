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

/**
 * Front end module "recommendation list".
 *
 * @property array  $recommendation_archives
 * @property string $recommendation_featured
 * @property string $recommendation_order
 */
class ModuleRecommendationList extends ModuleRecommendation
{
    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_recommendationlist';

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
            $objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['recommendationlist'][0] . ' ###';
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

        // Show the recommendation reader if an item has been selected
        if ($this->recommendation_readerModule > 0 && (isset($_GET['items']) || (Config::get('useAutoItem') && isset($_GET['auto_item']))))
        {
            return $this->getFrontendModule($this->recommendation_readerModule, $this->strColumn);
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
        System::loadLanguageFile('tl_recommendation');

        $limit = null;
        $offset = 0;

        $minRating = $this->recommendation_minRating;


        // Maximum number of items
        if ($this->numberOfItems > 0)
        {
            $limit = $this->numberOfItems;
        }

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

        $this->Template->recommendations = [];
        $this->Template->empty = $GLOBALS['TL_LANG']['MSC']['emptyRecommendationList'];

        // Get the total number of items
        $intTotal = $this->countItems($this->recommendation_archives, $blnFeatured, $minRating);

        if ($intTotal < 1)
        {
            return;
        }

        $total = $intTotal - $offset;

        // Split the results
        if ($this->perPage > 0 && (!isset($limit) || $this->numberOfItems > $this->perPage))
        {
            // Adjust the overall limit
            if (isset($limit))
            {
                $total = min($limit, $total);
            }

            // Get the current page
            $id = 'page_r' . $this->id;
            $page = Input::get($id) ?? 1;

            // Do not index or cache the page if the page number is outside the range
            if ($page < 1 || $page > max(ceil($total/$this->perPage), 1))
            {
                throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
            }

            // Set limit and offset
            $limit = $this->perPage;
            $offset += (max($page, 1) - 1) * $this->perPage;
            $skip = 0;

            // Overall limit
            if ($offset + $limit > $total + $skip)
            {
                $limit = $total + $skip - $offset;
            }

            // Add the pagination menu
            $objPagination = new Pagination($total, $this->perPage, Config::get('maxPaginationLinks'), $id);
            $this->Template->pagination = $objPagination->generate("\n  ");
        }

        $objRecommendations = $this->fetchItems($this->recommendation_archives, $blnFeatured, ($limit ?: 0), $offset, $minRating);

        // Add summary details
        $this->Template->totalCount = $intTotal;
        $this->addSummary($objRecommendations);

        // Add recommendations
        if ($objRecommendations !== null)
        {
            $this->Template->recommendations = $this->parseRecommendations($objRecommendations);
        }
    }

    protected function addSummary($objRecommendations): void
    {
        Controller::loadLanguageFile('tl_recommendation_list');

        $ratings = $objRecommendations->fetchEach('rating');
        $grouped = \array_count_values($ratings);
        $average = 0;

        \array_walk($grouped, static function ($count, $number) use (&$average){
            $average += ($number * $count);
        });

        $this->Template->average = $average / $this->Template->totalCount;
        $this->Template->averageRound = ceil($this->Template->average);
        $this->Template->countLabel = sprintf($GLOBALS['TL_LANG']['tl_recommendation_list']['recommendation_count'], $this->Template->totalCount);
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
        if (isset($GLOBALS['TL_HOOKS']['recommendationListCountItems']) && \is_array($GLOBALS['TL_HOOKS']['recommendationListCountItems']))
        {
            foreach ($GLOBALS['TL_HOOKS']['recommendationListCountItems'] as $callback)
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

        //return RecommendationModel::countPublishedByPids($recommendationArchives, $blnFeatured, $minRating);
        return $this->fetchItems($recommendationArchives, $blnFeatured, 0, 0, $minRating)?->count() ?? 0;
    }

    /**
     * Fetch the matching items
     */
    protected function fetchItems($recommendationArchives, $blnFeatured, $limit, $offset, $minRating)
    {
        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['recommendationListFetchItems']) && \is_array($GLOBALS['TL_HOOKS']['recommendationListFetchItems']))
        {
            foreach ($GLOBALS['TL_HOOKS']['recommendationListFetchItems'] as $callback)
            {
                if (($objCollection = System::importStatic($callback[0])->{$callback[1]}($recommendationArchives, $blnFeatured, $limit, $offset, $this)) === false)
                {
                    continue;
                }

                if ($objCollection === null || $objCollection instanceof Collection)
                {
                    return $objCollection;
                }
            }
        }

        $t = RecommendationModel::getTable();
        $order = '';

        if ($this->recommendation_featured == 'featured_first')
        {
            $order .= "$t.featured DESC, ";
        }

        switch ($this->recommendation_order)
        {
            case 'order_random':
                $order .= "RAND()";
                break;

            case 'order_date_asc':
                $order .= "$t.date";
                break;

            case 'order_rating_desc':
                $order .= "$t.rating DESC";
                break;

            default:
                $order .= "$t.date DESC";
        }

        // Get archives
        $archives = RecommendationArchiveModel::findMultipleByIds($recommendationArchives);
        $archives = array_combine(
            $archives->fetchEach('id'),
            $archives->fetchAll()
        );

        // Fetch items
        $items = [];

        // Get auto item
        $autoItem = Input::get('auto_item', false, true);

        foreach (RecommendationModel::findPublishedByPids($recommendationArchives, $blnFeatured, $limit, $offset, $minRating, ['order'=>$order]) ?? [] as $item)
        {
            if($archives[$item->pid]['useAutoItem'] ?? null)
            {
                // Add only if the scope match to the current auto_item
                if($autoItem === $item->scope)
                {
                    $items[] = $item;
                }
            }else{
                $items[] = $item;
            }
        }

        return new Collection($items, $t);
    }
}

class_alias(ModuleRecommendationList::class, 'ModuleRecommendationList');
