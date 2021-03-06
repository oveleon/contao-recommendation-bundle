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
 * Front end module "recommendation list".
 *
 * @property array  $recommendation_archives
 * @property string $recommendation_featured
 *
 * @author Fabian Ekert <fabian@oveleon.de>
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
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			/** @var \BackendTemplate|object $objTemplate */
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['recommendationlist'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		$this->recommendation_archives = $this->sortOutProtected(\StringUtil::deserialize($this->recommendation_archives));

		// Return if there are no archives
		if (empty($this->recommendation_archives) || !\is_array($this->recommendation_archives))
		{
			return '';
		}

		return parent::generate();
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$limit = null;
		$offset = (int) $this->skipFirst;

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

		$this->Template->recommendations = array();
		$this->Template->empty = $GLOBALS['TL_LANG']['MSC']['emptyList'];

		// Get the total number of items
		$intTotal = $this->countItems($this->recommendation_archives, $blnFeatured);

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
			$id = 'page_n' . $this->id;
			$page = (\Input::get($id) !== null) ? \Input::get($id) : 1;

			// Do not index or cache the page if the page number is outside the range
			if ($page < 1 || $page > max(ceil($total/$this->perPage), 1))
			{
				throw new PageNotFoundException('Page not found: ' . \Environment::get('uri'));
			}

			// Set limit and offset
			$limit = $this->perPage;
			$offset += (max($page, 1) - 1) * $this->perPage;
			$skip = (int) $this->skipFirst;

			// Overall limit
			if ($offset + $limit > $total + $skip)
			{
				$limit = $total + $skip - $offset;
			}

			// Add the pagination menu
			$objPagination = new \Pagination($total, $this->perPage, \Config::get('maxPaginationLinks'), $id);
			$this->Template->pagination = $objPagination->generate("\n  ");
		}

		$objRecommendations = $this->fetchItems($this->recommendation_archives, $blnFeatured, ($limit ?: 0), $offset);

		// Add recommendations
		if ($objRecommendations !== null)
		{
			$this->Template->recommendations = $this->parseRecommendations($objRecommendations);
		}
	}

	/**
	 * Count the total matching items
	 *
	 * @param array   $recommendationArchives
	 * @param boolean $blnFeatured
	 *
	 * @return integer
	 */
	protected function countItems($recommendationArchives, $blnFeatured)
	{
		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['recommendationListCountItems']) && \is_array($GLOBALS['TL_HOOKS']['recommendationListCountItems']))
		{
			foreach ($GLOBALS['TL_HOOKS']['recommendationListCountItems'] as $callback)
			{
				if (($intResult = \System::importStatic($callback[0])->{$callback[1]}($recommendationArchives, $blnFeatured, $this)) === false)
				{
					continue;
				}

				if (\is_int($intResult))
				{
					return $intResult;
				}
			}
		}

		return RecommendationModel::countPublishedByPids($recommendationArchives, $blnFeatured);
	}

	/**
	 * Fetch the matching items
	 *
	 * @param array   $recommendationArchives
	 * @param boolean $blnFeatured
	 * @param integer $limit
	 * @param integer $offset
	 *
	 * @return \Model\Collection|RecommendationModel|null
	 */
	protected function fetchItems($recommendationArchives, $blnFeatured, $limit, $offset)
	{
		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['recommendationListFetchItems']) && \is_array($GLOBALS['TL_HOOKS']['recommendationListFetchItems']))
		{
			foreach ($GLOBALS['TL_HOOKS']['recommendationListFetchItems'] as $callback)
			{
				if (($objCollection = \System::importStatic($callback[0])->{$callback[1]}($recommendationArchives, $blnFeatured, $limit, $offset, $this)) === false)
				{
					continue;
				}

				if ($objCollection === null || $objCollection instanceof \Model\Collection)
				{
					return $objCollection;
				}
			}
		}

		return RecommendationModel::findPublishedByPids($recommendationArchives, $blnFeatured, $limit, $offset);
	}
}
