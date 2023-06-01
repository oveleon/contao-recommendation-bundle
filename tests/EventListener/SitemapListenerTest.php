<?php

declare(strict_types=1);

namespace EventListener;

use Contao\CoreBundle\Event\SitemapEvent;
use Contao\Database;
use Oveleon\ContaoRecommendationBundle\EventListener\SitemapListener;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use Oveleon\ContaoRecommendationBundle\Model\RecommendationArchiveModel;
use Oveleon\ContaoRecommendationBundle\Model\RecommendationModel;
use Symfony\Component\HttpFoundation\Request;

class SitemapListenerTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_CONFIG']);

        parent::tearDown();
    }

    public function testNothingIsAddedIfNoPublishedArchive(): void
    {
        $adapters = [
            RecommendationArchiveModel::class => $this->mockConfiguredAdapter(['findByProtected' => null]),
        ];

        $sitemapEvent = $this->createSitemapEvent([]);
        $listener = $this->createListener([], $adapters);
        $listener($sitemapEvent);

        $this->assertStringNotContainsString('<url><loc>', (string) $sitemapEvent->getDocument()->saveXML());
    }

    public function testRecommendationIsAdded(): void
    {
        $jumpToPage = $this->mockClassWithProperties(PageModel::class, [
            'published' => 1,
            'protected' => 0,
        ]);

        $jumpToPage
            ->method('getAbsoluteUrl')
            ->willReturn('https://www.oveleon.de')
        ;

        $adapters = [
            RecommendationArchiveModel::class => $this->mockConfiguredAdapter([
                'findByProtected' => [
                    $this->mockClassWithProperties(RecommendationArchiveModel::class, [
                        'jumpTo' => 21,
                    ]),
                ],
            ]),
            PageModel::class => $this->mockConfiguredAdapter([
                'findWithDetails' => $jumpToPage,
            ]),
            RecommendationModel::class => $this->mockConfiguredAdapter([
                'findPublishedByPid' => [
                    $this->mockClassWithProperties(RecommendationModel::class, [
                        'jumpTo' => 21,
                    ]),
                ],
            ]),
        ];

        $sitemapEvent = $this->createSitemapEvent([1]);
        $listener = $this->createListener([1, 21], $adapters);
        $listener($sitemapEvent);

        $this->assertStringContainsString('<url><loc>https://www.oveleon.de</loc></url>', (string) $sitemapEvent->getDocument()->saveXML());
    }

    private function createListener(array $allPages, array $adapters): SitemapListener
    {
        $database = $this->createMock(Database::class);
        $database
            ->method('getChildRecords')
            ->willReturn($allPages)
        ;

        $instances = [
            Database::class => $database,
        ];

        $framework = $this->mockContaoFramework($adapters, $instances);

        return new SitemapListener($framework);
    }

    private function createSitemapEvent(array $rootPages): SitemapEvent
    {
        $sitemap = new \DOMDocument('1.0', 'UTF-8');
        $urlSet = $sitemap->createElementNS('https://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
        $sitemap->appendChild($urlSet);

        return new SitemapEvent($sitemap, new Request(), $rootPages);
    }
}
