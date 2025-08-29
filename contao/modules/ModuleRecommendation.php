<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoRecommendationBundle;

use Contao\Config;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Date;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\Model\Collection;
use Contao\Module;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Exception;
use Oveleon\ContaoRecommendationBundle\Model\RecommendationModel;
use Oveleon\ContaoRecommendationBundle\Model\RecommendationArchiveModel;
use Symfony\Component\Filesystem\Path;

/**
 * Parent class for recommendation modules.
 *
 * @property string $recommendation_template
 * @property mixed  $recommendation_metaFields
 * @property mixed  $recommendation_externalSize
 * @property bool   $recommendation_useDialog
 */
abstract class ModuleRecommendation extends Module
{
    /**
     * Sort out protected archives
     */
    protected function sortOutProtected(array $arrArchives): array
    {
        if (empty($arrArchives))
        {
            return $arrArchives;
        }

        $objArchive = RecommendationArchiveModel::findMultipleByIds($arrArchives);
        $arrArchives = [];

        if ($objArchive !== null)
        {
            $security = System::getContainer()->get('security.helper');

            while ($objArchive->next())
            {
                if ($objArchive->protected && !$security->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, StringUtil::deserialize($objArchive->groups, true)))
                {
                    continue;
                }

                $arrArchives[] = $objArchive->id;
            }
        }

        return $arrArchives;
    }

    /**
     * Parse an item and return it as string
     */
    protected function parseRecommendation(RecommendationModel $objRecommendation, RecommendationArchiveModel $objRecommendationArchive, string $strClass='', int $intCount=0): string
    {
        $objTemplate = new FrontendTemplate($this->recommendation_template ?: 'recommendation_default');
        $objTemplate->setData($objRecommendation->row());

        if ($objRecommendation->cssClass)
        {
            $strClass = ' ' . $objRecommendation->cssClass . $strClass;
        }

        if ($objRecommendation->featured)
        {
            $strClass = ' featured' . $strClass;
        }

        $objTemplate->class = $strClass;
        $objTemplate->archiveId = $objRecommendationArchive->id;

        $moreLabel = $this->customLabel ?: $GLOBALS['TL_LANG']['MSC']['more'];

        if ($this->recommendation_useDialog)
        {
            $objTemplate->dialog = true;
            $objTemplate->more = $moreLabel;
            $objRecommendationArchive->jumpTo = null;
        }
        elseif ($objRecommendationArchive->jumpTo)
        {
            $objTemplate->allowRedirect = true;
            $objTemplate->more = $this->generateLink($moreLabel, $objRecommendation, $objRecommendation->title ?: $objRecommendation->author, true);
        }

        if ($objRecommendation->title)
        {
            $objTemplate->headlineLink = $objRecommendationArchive->jumpTo ? $this->generateLink($objRecommendation->title, $objRecommendation, $objRecommendation->title) : $objRecommendation->title;
            $objTemplate->headline = $objRecommendation->title;
        }

        $arrMeta = $this->getMetaFields($objRecommendation);

        // Add the meta information
        $objTemplate->addRating = array_key_exists('rating', $arrMeta);
        $objTemplate->addDate = array_key_exists('date', $arrMeta);
        $objTemplate->datetime = $strDateTime = date('Y-m-d\TH:i:sP', $objRecommendation->date);
        $objTemplate->date = $arrMeta['date'] ?? null;
        $objTemplate->elapsedTime = $this->getElapsedTime($strDateTime);
        $objTemplate->addAuthor = array_key_exists('author', $arrMeta);
        $objTemplate->author = $arrMeta['author'] ?? null;
        $objTemplate->addCustomField = array_key_exists('customField', $arrMeta);
        $objTemplate->customField = $arrMeta['customField'] ?? null;
        $objTemplate->addLocation = array_key_exists('location', $arrMeta);
        $objTemplate->location = $arrMeta['location'] ?? null;

        // Add styles
        $color = unserialize(Config::get('recommendationActiveColor') ?? '')[0] ?? null;
        $objTemplate->styles = $color ? ' style="color:#'.$color.'"' : '';

        $objTemplate->addExternalImage = false;
        $objTemplate->addInternalImage = false;

        // Parsing image meta field to template for backwards compatibility // Works for recommendation_default.html5
        $objTemplate->addRecommendationImage = array_key_exists('image', $arrMeta);

        $container = System::getContainer();

        // Add an image
        if ($objRecommendation->imageUrl != '')
        {
            $objRecommendation->imageUrl = $container->get('contao.insert_tag.parser')->replace($objRecommendation->imageUrl);

            // Insert tag parser on contao ^5 returns a leading slash whilst contao 4.13 does not
            if (Path::isAbsolute($objRecommendation->imageUrl))
            {
                $objRecommendation->imageUrl = substr($objRecommendation->imageUrl,1);
            }

            if ($this->isExternal($objRecommendation->imageUrl))
            {
                $objTemplate->addExternalImage = true;
                $objTemplate->imageUrl = $objRecommendation->imageUrl;
            }
            else
            {
                $objModel = FilesModel::findByPath($objRecommendation->imageUrl);
                $this->addInternalImage($objModel, $objTemplate);
            }
        }
        elseif (Config::get('recommendationDefaultImage'))
        {
            $objModel = FilesModel::findByUuid(Config::get('recommendationDefaultImage'));
            $this->addInternalImage($objModel, $objTemplate);
        }

        $size = StringUtil::deserialize($this->recommendation_externalSize);
        $width = $height = 128;

        if (\is_array($size) && !empty($size[0]) && !empty($size[1]))
        {
            $width = $size[0];
            $height = $size[1];
        }

        $objTemplate->externalSize = ' width="' . $width . '" height="' . $height . '"';

        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['parseRecommendation']) && \is_array($GLOBALS['TL_HOOKS']['parseRecommendation']))
        {
            foreach ($GLOBALS['TL_HOOKS']['parseRecommendation'] as $callback)
            {
                $this->import($callback[0]);
                $this->{$callback[0]}->{$callback[1]}($objTemplate, $objRecommendation->row(), $this);
            }
        }

        // Tag recommendations
        if ($container->has('fos_http_cache.http.symfony_response_tagger'))
        {
            $responseTagger = $container->get('fos_http_cache.http.symfony_response_tagger');
            $responseTagger->addTags(['contao.db.tl_recommendation.' . $objRecommendation->id]);
        }

        return $objTemplate->parse();
    }

    /**
     * Parse one or more items and return them as array
     */
    protected function parseRecommendations(Collection $objRecommendations): array
    {
        $limit = $objRecommendations->count();

        if ($limit < 1)
        {
            return [];
        }

        $count = 0;
        $arrRecommendations = [];

        foreach ($objRecommendations as $recommendation)
        {
            /** @var RecommendationArchiveModel $objRecommendationArchive */
            $objRecommendationArchive = $recommendation->getRelated('pid');

            $arrRecommendations[] = $this->parseRecommendation(
                $recommendation,
                $objRecommendationArchive,
                ((++$count == 1) ? ' first' : '') . (($count == $limit) ? ' last' : '') . ((($count % 2) == 0) ? ' odd' : ' even'),
                $count
            );
        }

        return $arrRecommendations;
    }

    /**
     * Return the meta fields of a recommendation as array
     */
    protected function getMetaFields(RecommendationModel $objRecommendation): array
    {
        $meta = StringUtil::deserialize($this->recommendation_metaFields);

        if (!\is_array($meta))
        {
            return [];
        }

        /** @var PageModel $objPage */
        global $objPage;

        $return = [];

        foreach ($meta as $field)
        {
            switch ($field)
            {
                case 'date':
                    $return['date'] = Date::parse($objPage->datimFormat, $objRecommendation->date);
                    break;

                case 'image':
                    $return['image'] = true;
                    break;

                default:
                    $return[ $field ] = $objRecommendation->{$field};
            }
        }

        return $return;
    }

    /**
     * Generate a link and return it as string
     */
    protected function generateLink(string $strLink, RecommendationModel $objRecommendation, string $strTitle, bool $blnIsReadMore=false): string
    {
        return sprintf('<a href="%s" title="%s" itemprop="url">%s%s</a>',
                        $this->generateRecommendationUrl($objRecommendation),
                        StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['readRecommendation'], $strTitle), true),
                        $strLink,
                        ($blnIsReadMore ? '<span class="invisible"> '.$strTitle.'</span>' : ''));
    }

    /**
     * Generate a URL and return it as string
     */
    protected function generateRecommendationUrl(RecommendationModel $objRecommendation): string
    {
        $objPage = PageModel::findByPk($objRecommendation->getRelated('pid')->jumpTo);

        return StringUtil::ampersand($objPage->getFrontendUrl(($this->useAutoItem() ? '/' : '/items/') . ($objRecommendation->alias ?: $objRecommendation->id)));
    }

    /**
     * Check whether path is external
     */
    protected function isExternal(string $strPath): bool
    {
        if (str_starts_with($strPath, 'http://') || str_starts_with($strPath, 'https://'))
        {
            return true;
        }

        return false;
    }

    /**
     * Add an internal image to template
     */
    protected function addInternalImage($objModel, &$objTemplate): void
    {
        if (null !== $objModel)
        {
            $imgSize = $this->imgSize ?: null;
            $objTemplate->addInternalImage = true;

            // Override the default image size
            if ($this->imgSize)
            {
                $size = StringUtil::deserialize($this->imgSize);

                if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2]) || ($size[2][0] ?? null) === '_')
                {
                    $imgSize = $this->imgSize;
                }
            }

            $figureBuilder = System::getContainer()
                ->get('contao.image.studio')
                ->createFigureBuilder()
                ->from($objModel->path)
                ->setSize($imgSize);

            if (null !== ($figure = $figureBuilder->buildIfResourceExists()))
            {
                $figure->applyLegacyTemplateData($objTemplate);
            }
        }
    }

    /**
     * Parses a timestamp into a human-readable string
     * @throws Exception
     */
    protected function getElapsedTime(string $strDateTime): string
    {
        $objElapsedTime = (new \DateTime($strDateTime))->diff(new \DateTime(date("Y-m-d\TH:i:sP",time())));

        if (($years = $objElapsedTime->y) > 0)
        {
            return $this->translateElapsedTime($years, 'year');
        }
        elseif (($months = $objElapsedTime->m) > 0)
        {
            return $this->translateElapsedTime($months, 'month');
        }
        elseif (($weeks = $objElapsedTime->d) > 6)
        {
            return $this->translateElapsedTime((int) round($weeks / 7), 'week');
        }
        elseif (($days = $objElapsedTime->d) > 0)
        {
            return $this->translateElapsedTime($days, 'day');
        }
        elseif (($hours = $objElapsedTime->h) > 0)
        {
            return $this->translateElapsedTime($hours, 'hour');
        }
        else
        {
            return $GLOBALS['TL_LANG']['tl_recommendation']['justNow'] ?? 'just now';
        }
    }

    /**
     * Translates elapsed time
     */
    protected function translateElapsedTime(int $value, string $strUnit = 'justNow'): string
    {
        if (isset($GLOBALS['TL_LANG']['tl_recommendation'][$strUnit][!($value>1)]))
        {
            return sprintf($GLOBALS['TL_LANG']['tl_recommendation'][$strUnit][!($value>1)], $value);
        }

        return '';
    }

    /**
     * Checks weather auto_item should be used to provide BC
     *
     * @deprecated - To be removed when contao 4.13 support ends
     * @internal
     */
    protected function useAutoItem(): bool
    {
        return !str_starts_with(ContaoCoreBundle::getVersion(), '5.') ? Config::get('useAutoItem') : true;
    }
}
