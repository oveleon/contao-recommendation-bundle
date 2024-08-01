<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoRecommendationBundle\EventListener\DataContainer;

use Contao\Backend;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Oveleon\ContaoRecommendationBundle\Security\ContaoRecommendationPermissions;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class RecommendationArchiveListener
{
    public function __construct(
        protected ContaoFramework $framework,
        protected AuthorizationCheckerInterface $security,
    ){}

    /**
     * Return the edit header button
     */
    public function editHeader(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        return $this->security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE, 'tl_recommendation_archive') ? '<a href="' . Backend::addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
    }

    /**
     * Return the copy archive button
     */
    public function copyArchive(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        return $this->security->isGranted(ContaoRecommendationPermissions::USER_CAN_CREATE_ARCHIVES) ? '<a href="' . Backend::addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
    }

    /**
     * Return the delete archive button
     */
    public function deleteArchive(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        return $this->security->isGranted(ContaoRecommendationPermissions::USER_CAN_DELETE_ARCHIVES) ? '<a href="' . Backend::addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ';
    }

    /**
     * Add the new archive to the permissions
     */
    public function adjustPermissions(int|string $insertId): void
    {
        // The oncreate_callback passes $insertId as second argument
        if (func_num_args() == 4)
        {
            $insertId = func_get_arg(1);
        }

        $objUser = Controller::getContainer()->get('security.helper')->getUser();

        if ($objUser->isAdmin)
        {
            return;
        }

        // Set root IDs
        if (empty($objUser->recommendations) || !is_array($objUser->recommendations))
        {
            $root = [0];
        }
        else
        {
            $root = $objUser->recommendations;
        }

        // The archive is enabled already
        if (in_array($insertId, $root))
        {
            return;
        }

        /** @var AttributeBagInterface $objSessionBag */
        $objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');

        $arrNew = $objSessionBag->get('new_records');

        if (is_array($arrNew['tl_recommendation_archive']) && in_array($insertId, $arrNew['tl_recommendation_archive']))
        {
            $db = Database::getInstance();

            // Add the permissions on group level
            if ($objUser->inherit != 'custom')
            {
                $objGroup = $db->execute("SELECT id, recommendations, recommendationp FROM tl_user_group WHERE id IN(" . implode(',', array_map('\intval', $objUser->groups)) . ")");

                while ($objGroup->next())
                {
                    $arrRecommendationp = StringUtil::deserialize($objGroup->recommendationp);

                    if (is_array($arrRecommendationp) && in_array('create', $arrRecommendationp))
                    {
                        $arrRecommendations = StringUtil::deserialize($objGroup->recommendations, true);
                        $arrRecommendations[] = $insertId;

                        $db->prepare("UPDATE tl_user_group SET recommendations=? WHERE id=?")
                           ->execute(serialize($arrRecommendations), $objGroup->id);
                    }
                }
            }

            // Add the permissions on user level
            if ($objUser->inherit != 'group')
            {
                $objUser = $db->prepare("SELECT recommendations, recommendationp FROM tl_user WHERE id=?")
                    ->limit(1)
                    ->execute($objUser->id);

                $arrRecommendationp = StringUtil::deserialize($objUser->recommendationp);

                if (is_array($arrRecommendationp) && in_array('create', $arrRecommendationp))
                {
                    $arrRecommendations = StringUtil::deserialize($objUser->recommendations, true);
                    $arrRecommendations[] = $insertId;

                    $db->prepare("UPDATE tl_user SET recommendations=? WHERE id=?")
                       ->execute(serialize($arrRecommendations), $objUser->id);
                }
            }

            // Add the new element to the user object
            $root[] = $insertId;
            $objUser->recommendations = $root;
        }
    }

    public function addSitemapCacheInvalidationTag(DataContainer $dc, array $tags): array
    {
        $pageModel = PageModel::findWithDetails($dc->activeRecord->jumpTo);

        if ($pageModel === null)
        {
            return $tags;
        }

        return array_merge($tags, ['contao.sitemap.' . $pageModel->rootId]);
    }
}
