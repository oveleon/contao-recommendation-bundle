<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoRecommendationBundle;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Patchwork\Utf8;

/**
 * Front end module "recommendation reader".
 *
 * @property array    $recommendation_archives
 *
 * @author Fabian Ekert <fabian@oveleon.de>
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
		if (TL_MODE == 'BE')
		{
			/** @var \BackendTemplate|object $objTemplate */
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['recommendationreader'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		// Set the item from the auto_item parameter
		if (!isset($_GET['items']) && \Config::get('useAutoItem') && isset($_GET['auto_item']))
		{
			\Input::setGet('items', \Input::get('auto_item'));
		}

		$this->recommendation_archives = $this->sortOutProtected(\StringUtil::deserialize($this->recommendation_archives));

		// Do not index or cache the page if no recommendation item has been specified
		if (!\Input::get('items') || empty($this->recommendation_archives) || !\is_array($this->recommendation_archives))
		{
			/** @var \PageModel $objPage */
			global $objPage;

			$objPage->noSearch = 1;
			$objPage->cache = 0;

			return '';
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

		// Get the news item
		$objRecommendation = RecommendationModel::findPublishedByParentAndIdOrAlias(\Input::get('items'), $this->recommendation_archives);

		if (null === $objRecommendation)
		{
			throw new PageNotFoundException('Page not found: ' . \Environment::get('uri'));
		}

		$arrRecommendation = $this->parseRecommendation($objRecommendation);
		$this->Template->recommendation = $arrRecommendation;
	}
}
