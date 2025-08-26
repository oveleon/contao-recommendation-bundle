<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoRecommendationBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Oveleon\ContaoRecommendationBundle\Model\RecommendationModel;
use Psr\Log\LoggerInterface;

#[AsCronJob('daily')]
readonly class PurgeRecommendationsCron
{
    public function __construct(
        private ContaoFramework      $framework,
        private Connection           $connection,
        private LoggerInterface|null $logger
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(): void
    {
        $this->framework->initialize();

        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist('tl_recommendation'))
        {
            return;
        }

        $recommendations = $this->framework->getAdapter(RecommendationModel::class)->findExpiredRecommendations();

        if (null === $recommendations)
        {
            return;
        }

        /** @var RecommendationModel $recommendation */
        foreach ($recommendations as $recommendation)
        {
            $recommendation->delete();
        }

        $this->logger?->info('Purged the unactivated recommendations');
    }
}
