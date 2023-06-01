<?php

declare(strict_types=1);

namespace Cron;

use Contao\Model\Collection;
use Contao\TestCase\ContaoTestCase;
use Oveleon\ContaoRecommendationBundle\Cron\PurgeRecommendationsCron;
use Oveleon\ContaoRecommendationBundle\Model\RecommendationModel;

class PurgeRecommendationsCronTest extends ContaoTestCase
{
    public function testDeletesUnactivatedRecommendations(): void
    {
        $recommendationModel = $this->createMock(RecommendationModel::class);
        $recommendationModel
            ->expects($this->once())
            ->method('delete')
        ;

        $recommendationModelAdapter = $this->mockAdapter(['findExpiredRecommendations']);
        $recommendationModelAdapter
            ->expects($this->once())
            ->method('findExpiredRecommendations')
            ->willReturn(new Collection([$recommendationModel], RecommendationModel::getTable()))
        ;

        $framework = $this->mockContaoFramework([RecommendationModel::class => $recommendationModelAdapter]);

        (new PurgeRecommendationsCron($framework, null))();
    }
}
