<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoRecommendationBundle;

use Contao\Config;
use Contao\Controller;
use Contao\Date;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\FrontendUser;
use Contao\Model\Collection;
use Contao\Module;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;

/**
 * Parent class for recommendation modules.
 *
 * @property string $recommendation_template
 * @property mixed  $recommendation_metaFields
 *
 * @author Fabian Ekert <fabian@oveleon.de>
 * @author Sebastian Zoglowek <sebastian@oveleon.de>
 */
abstract class ModuleRecommendation extends Module
{

    /**
     * Sort out protected archives
     *
     * @param array $arrArchives
     *
     * @return array
     */
    protected function sortOutProtected($arrArchives)
    {
        if (empty($arrArchives) || !\is_array($arrArchives))
        {
            return $arrArchives;
        }

        $this->import(FrontendUser::class, 'User');
        $objArchive = RecommendationArchiveModel::findMultipleByIds($arrArchives);
        $arrArchives = array();

        if ($objArchive !== null)
        {
            while ($objArchive->next())
            {
                if ($objArchive->protected)
                {
                    if (!FE_USER_LOGGED_IN)
                    {
                        continue;
                    }

                    $groups = StringUtil::deserialize($objArchive->groups);

                    if (empty($groups) || !\is_array($groups) || !\count(array_intersect($groups, $this->User->groups)))
                    {
                        continue;
                    }
                }

                $arrArchives[] = $objArchive->id;
            }
        }

        return $arrArchives;
    }

    /**
     * Parse an item and return it as string
     *
     * @param RecommendationModel        $objRecommendation
     * @param RecommendationArchiveModel $objRecommendationArchive
     * @param string                     $strClass
     * @param integer                    $intCount
     *
     * @return string
     */
    protected function parseRecommendation($objRecommendation, $objRecommendationArchive, $strClass='', $intCount=0)
    {
        /** @var FrontendTemplate|object $objTemplate */
        $objTemplate = new FrontendTemplate($this->recommendation_template ?: 'recommendation_default');
        $objTemplate->setData($objRecommendation->row());

        if ($objRecommendation->cssClass != '')
        {
            $strClass = ' ' . $objRecommendation->cssClass . $strClass;
        }

        if ($objRecommendation->featured)
        {
            $strClass = ' featured' . $strClass;
        }

        $objTemplate->class = $strClass;
        $objTemplate->archiveId = $objRecommendationArchive->id;

        if ($objRecommendationArchive->jumpTo)
        {
            $objTemplate->allowRedirect = true;
            $objTemplate->more = $this->generateLink($GLOBALS['TL_LANG']['MSC']['more'], $objRecommendation, $objRecommendation->title, true);
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
        $objTemplate->datetime = date('Y-m-d\TH:i:sP', $objRecommendation->date);
        $objTemplate->date = $arrMeta['date'] ?? null;
        $objTemplate->addAuthor = array_key_exists('author', $arrMeta);
        $objTemplate->author = $arrMeta['author'] ?? null;
        $objTemplate->addCustomField = array_key_exists('recommendation_customField', $arrMeta);
        $objTemplate->customField = $arrMeta['recommendation_customField'] ?? null;
        $objTemplate->addLocation = array_key_exists('location', $arrMeta);
        $objTemplate->location = $arrMeta['location'] ?? null;

        // Add styles
        $color = unserialize(Config::get('recommendationActiveColor'))[0];
        $objTemplate->styles = $color ? ' style="color:#'.$color.'"' : '';

        $objTemplate->addExternalImage = false;
        $objTemplate->addInternalImage = false;

        // Parsing image meta field to template for backwards compatibility // Works for recommendation_default.html5
        $objTemplate->addRecommendationImage = array_key_exists('recommendation_image', $arrMeta);

        // Add an image
        if ($objRecommendation->imageUrl != '')
        {
            $objRecommendation->imageUrl = Controller::replaceInsertTags($objRecommendation->imageUrl);

            if ($this->isExternal($objRecommendation->imageUrl))
            {
                $objTemplate->addExternalImage = true;

                $objTemplate->imageUrl = $objRecommendation->imageUrl;
            }
            else
            {
                $objModel = FilesModel::findByPath($objRecommendation->imageUrl);

                $this->addInternalImage($objModel, $objRecommendation, $objTemplate);
            }
        }
        elseif (Config::get('recommendationDefaultImage'))
        {
            $objModel = FilesModel::findByUuid(Config::get('recommendationDefaultImage'));

            $this->addInternalImage($objModel, $objRecommendation, $objTemplate);
        }

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
        if (System::getContainer()->has('fos_http_cache.http.symfony_response_tagger'))
        {
            $responseTagger = System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
            $responseTagger->addTags(array('contao.db.tl_recommendation.' . $objRecommendation->id));
        }

        return $objTemplate->parse();
    }

    /**
     * Parse one or more items and return them as array
     *
     * @param Collection $objRecommendations
     *
     * @return array
     */
    protected function parseRecommendations($objRecommendations)
    {
        $limit = $objRecommendations->count();

        if ($limit < 1)
        {
            return array();
        }

        $count = 0;
        $arrRecommendations = array();

        while ($objRecommendations->next())
        {
            /** @var RecommendationModel $objRecommendation */
            $objRecommendation = $objRecommendations->current();

            /** @var RecommendationArchiveModel $objRecommendationArchive */
            $objRecommendationArchive = $objRecommendation->getRelated('pid');

            $arrRecommendations[] = $this->parseRecommendation($objRecommendation, $objRecommendationArchive, ((++$count == 1) ? ' first' : '') . (($count == $limit) ? ' last' : '') . ((($count % 2) == 0) ? ' odd' : ' even'), $count);
        }

        return $arrRecommendations;
    }

    /**
     * Return the meta fields of a recommendation as array
     *
     * @param RecommendationModel $objRecommendation
     *
     * @return array
     */
    protected function getMetaFields($objRecommendation)
    {
        $meta = StringUtil::deserialize($this->recommendation_metaFields);

        if (!\is_array($meta))
        {
            return array();
        }

        /** @var PageModel $objPage */
        global $objPage;

        $return = array();

        foreach ($meta as $field)
        {
            switch ($field)
            {
                case 'date':
                    $return['date'] = Date::parse($objPage->datimFormat, $objRecommendation->date);
                    break;

                case 'recommendation_image':
                    $return['recommendation_image'] = true;
                    break;

                default:
                    $return[ $field ] = $objRecommendation->{$field};
            }
        }

        return $return;
    }

    /**
     * Generate a link and return it as string
     *
     * @param string              $strLink
     * @param RecommendationModel $objRecommendation
     * @param string              $strTitle
     * @param boolean             $blnIsReadMore
     *
     * @return string
     */
    protected function generateLink($strLink, $objRecommendation, $strTitle, $blnIsReadMore=false)
    {
        return sprintf('<a href="%s" title="%s" itemprop="url">%s%s</a>',
                        $this->generateRecommendationUrl($objRecommendation),
                        StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $strTitle), true),
                        $strLink,
                        ($blnIsReadMore ? '<span class="invisible"> '.$strTitle.'</span>' : ''));
    }

    /**
     * Generate a URL and return it as string
     *
     * @param RecommendationModel $objRecommendation
     *
     * @return string
     */
    protected function generateRecommendationUrl($objRecommendation)
    {
        $objPage = PageModel::findByPk($objRecommendation->getRelated('pid')->jumpTo);

        return ampersand($objPage->getFrontendUrl((Config::get('useAutoItem') ? '/' : '/items/') . ($objRecommendation->alias ?: $objRecommendation->id)));
    }

    /**
     * Check whether path is external
     *
     * @param string $strPath The file path
     *
     * @return boolean
     */
    protected function isExternal($strPath)
    {
        if (substr($strPath, 0, 7) == 'http://' || substr($strPath, 0, 8) == 'https://')
        {
            return true;
        }

        return false;
    }

    /**
     * Add an internal image to template
     *
     * @param FilesModel $objModel                   The files model
     * @param RecommendationModel $objRecommendation The recommendation model
     * @param FrontendTemplate $objTemplate          The frontend template
     */
    protected function addInternalImage($objModel, $objRecommendation, &$objTemplate)
    {
        if ($objModel !== null && is_file(TL_ROOT . '/' . $objModel->path))
        {
            $objTemplate->addInternalImage = true;

            // Do not override the field now that we have a model registry (see #6303)
            $arrRecommendation = $objRecommendation->row();

            // Override the default image size
            if ($this->imgSize != '')
            {
                $size = StringUtil::deserialize($this->imgSize);

                if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2]) || ($size[2][0] ?? null) === '_')
                {
                    $arrRecommendation['size'] = $this->imgSize;
                }
            }

            $arrRecommendation['singleSRC'] = $objModel->path;
            $this->addImageToTemplate($objTemplate, $arrRecommendation, null, null, $objModel);
        }
    }
}
