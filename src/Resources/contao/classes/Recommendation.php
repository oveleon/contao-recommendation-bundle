<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoRecommendationBundle;

use Contao\Backend;
use Contao\CoreBundle\Monolog\ContaoContext;
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
}
