<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoRecommendationBundle;

use Contao\Backend;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\PageModel;
use Contao\System;
use Psr\Log\LogLevel;

/**
 * Provide methods to handle recommendations.
 *
 * @author Sebastian Zoglowek <https://github.com/zoglo>
 */
class Recommendation extends Backend
{
	/**
	 * Purge recommendations that have not been activated within 24 hours
	 */
	public function purgeRecommendations()
	{
		$objRecommendation = RecommendationModel::findExpiredRecommendations();

		if ($objRecommendation === null)
		{
			return;
		}

		while ($objRecommendation->next())
		{
			$objRecommendation->delete();
		}

		// Add a log entry
		$logger = System::getContainer()->get('monolog.logger.contao');
		$logger->log(LogLevel::INFO, 'Purged the unactivated recommendations', array('contao' => new ContaoContext(__METHOD__, TL_CRON)));
	}

	/**
	 * Add Recommendations to the indexer
	 *
	 * @param array   $arrPages
	 * @param integer $intRoot
	 * @param boolean $blnIsSitemap
	 *
	 * @return array
	 */
	public function getSearchablePages($arrPages, $intRoot=0, $blnIsSitemap=false)
	{
		$arrRoot = array();

		if ($intRoot > 0)
		{
			$arrRoot = $this->Database->getChildRecords($intRoot, 'tl_page');
		}

		$arrProcessed = array();
		$time = time();

		// Get all categories
		$objRecommendationArchive = RecommendationArchiveModel::findByProtected('');

		// Walk through each archive
		if ($objRecommendationArchive !== null)
		{
			while($objRecommendationArchive->next())
			{
				// Skip archives without a target page
				if (!$objRecommendationArchive->jumpTo)
				{
					continue;
				}

				// Skip archives outside the root nodes
				if (!empty($arrRoot) && !\in_array($objRecommendationArchive->jumpTo, $arrRoot))
				{
					continue;
				}

				// Get the URL of the jumpTo page
				if (!isset($arrProcessed[$objRecommendationArchive->jumpTo]))
				{
					$objParent = PageModel::findWithDetails($objRecommendationArchive->jumpTo);

					// The target page does not exist
					if ($objParent === null)
					{
						continue;
					}

					// The target page has not been published
					if (!$objParent->published || ($objParent->start && $objParent->start > $time) || ($objParent->stop && $objParent->stop <= $time))
					{
						continue;
					}

					if($blnIsSitemap)
					{
						// The target page is protected
						if ($objParent->protected)
						{
							continue;
						}

						// the target page is exempt from the sitemap
						if ($objParent->robots == 'noindex,nofollow')
						{
							continue;
						}
					}

					// Generate the URL
					$arrProcessed[$objRecommendationArchive->jumpTo] = $objParent->getAbsoluteUrl(\Config::get('useAutoItem') ? '/%s' : '/items/%s');
				}

				$strUrl = $arrProcessed[$objRecommendationArchive->jumpTo];

				// Get the items
				$objItems = RecommendationModel::findPublishedByPid($objRecommendationArchive->id);

				if($objItems !== null)
				{
					while ($objItems->next())
					{
						$arrPages[] = sprintf(preg_replace('/%(?!s)/', '%%', $strUrl), ($objItems->alias ?: $objItems->id));
					}
				}
			}
		}

		return $arrPages;
	}
}
