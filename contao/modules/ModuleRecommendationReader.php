<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoRecommendationBundle;

use Contao\BackendTemplate;
use Contao\Config;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Oveleon\ContaoRecommendationBundle\Model\RecommendationArchiveModel;
use Oveleon\ContaoRecommendationBundle\Model\RecommendationModel;

/**
 * Front end module "recommendation reader".
 *
 * @property array    $recommendation_archives
 */
class ModuleRecommendationReader extends ModuleRecommendation
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_recommendationreader';

    /**
     * Display a wildcard in the back end
     *
     * @return string
     */
    public function generate()
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
        {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['recommendationreader'][0] . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

            return $objTemplate->parse();
        }

        // Set the item from the auto_item parameter
        if (!isset($_GET['items']) && isset($_GET['auto_item']) && Config::get('useAutoItem'))
        {
            Input::setGet('items', Input::get('auto_item'));
        }

        // Return an empty string if "items" is not set (to combine list and reader on same page)
        if (!Input::get('items'))
        {
            return '';
        }

        $this->recommendation_archives = $this->sortOutProtected(StringUtil::deserialize($this->recommendation_archives));

        if (empty($this->recommendation_archives) || !\is_array($this->recommendation_archives))
        {
            throw new InternalServerErrorException('The recommendation reader ID ' . $this->id . ' has no archives specified.');
        }

        return parent::generate();
    }

    /**
     * Generate the module
     */
    protected function compile()
    {
        $this->Template->recommendation = '';
        $this->Template->referer = 'javascript:history.go(-1)';
        $this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];

        // Get the recommendation item
        $objRecommendation = RecommendationModel::findPublishedByParentAndIdOrAlias(Input::get('items'), $this->recommendation_archives);

        if (null === $objRecommendation)
        {
            throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
        }

        /** @var RecommendationArchiveModel $objRecommendationArchive */
        $objRecommendationArchive = $objRecommendation->getRelated('pid');

        $arrRecommendation = $this->parseRecommendation($objRecommendation, $objRecommendationArchive);
        $this->Template->recommendation = $arrRecommendation;
    }
}

class_alias(ModuleRecommendationReader::class, 'ModuleRecommendationReader');
