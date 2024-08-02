<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoRecommendationBundle\EventListener;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\SitemapEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Contao\PageModel;
use Oveleon\ContaoRecommendationBundle\Model\RecommendationArchiveModel;
use Oveleon\ContaoRecommendationBundle\Model\RecommendationModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(ContaoCoreEvents::SITEMAP)]
class SitemapListener
{
    public function __construct(
        private ContaoFramework $framework
    ) {
    }

    public function __invoke(SitemapEvent $event): void
    {
        $arrRoot = $this->framework->createInstance(Database::class)->getChildRecords($event->getRootPageIds(), 'tl_page');

        // Early return here in the unlikely case that there are no pages
        if (empty($arrRoot))
        {
            return;
        }

        $arrPages = [];
        $time = time();

        // Get all recommendation archives
        $objArchives = $this->framework->getAdapter(RecommendationArchiveModel::class)->findByProtected('');

        if (null === $objArchives)
        {
            return;
        }

        // Walk through each recommendation archive
        foreach ($objArchives as $objArchive)
        {
            // Skip recommendation archives without target page
            if (!$objArchive->jumpTo)
            {
                continue;
            }

            // Skip recommendation archives outside the root nodes
            if (!\in_array($objArchive->jumpTo, $arrRoot, true))
            {
                continue;
            }

            $objParent = $this->framework->getAdapter(PageModel::class)->findWithDetails($objArchive->jumpTo);

            // The target page does not exist
            if (null === $objParent)
            {
                continue;
            }

            // The target page has not been published
            if (!$objParent->published || ($objParent->start && $objParent->start > $time) || ($objParent->stop && $objParent->stop <= $time))
            {
                continue;
            }

            // The target page is protected
            if ($objParent->protected)
            {
                continue;
            }

            // The target page is exempt from the sitemap
            if ('noindex,nofollow' === $objParent->robots)
            {
                continue;
            }

            // Get the items
            $objRecommendations = $this->framework->getAdapter(RecommendationModel::class)->findPublishedByPid($objArchive->id);

            if (null === $objRecommendations)
            {
                continue;
            }

            foreach ($objRecommendations as $objRecommendation)
            {
                $arrPages[] = $objParent->getAbsoluteUrl('/' . ($objRecommendation->alias ?: $objRecommendation->id));
            }
        }

        foreach ($arrPages as $strUrl)
        {
            $this->addUrlToDefaultUrlSet($strUrl, $event);
        }
    }

    private function addUrlToDefaultUrlSet(string $url, $event): self
    {
        $sitemap = $event->getDocument();
        $urlSet = $sitemap->getElementsByTagNameNS('https://www.sitemaps.org/schemas/sitemap/0.9', 'urlset')->item(0);

        if (null === $urlSet)
        {
            return $this;
        }

        $loc = $sitemap->createElement('loc', $url);
        $urlEl = $sitemap->createElement('url');
        $urlEl->appendChild($loc);
        $urlSet->appendChild($urlEl);

        $sitemap->appendChild($urlSet);

        return $this;
    }
}
