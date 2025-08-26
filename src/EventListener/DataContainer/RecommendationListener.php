<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoRecommendationBundle\EventListener\DataContainer;

use Contao\Automator;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Contao\DataContainer;
use Contao\Date;
use Contao\Input;
use Contao\PageModel;
use Contao\System;
use Doctrine\DBAL\Exception;
use Oveleon\ContaoRecommendationBundle\Model\RecommendationArchiveModel;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class RecommendationListener
{
    public function __construct(
        protected ContaoFramework $framework,
    ){}

    /**
     * Set the timestamp to 00:00:00
     */
    public function loadDate(int $value): bool|int
    {
        return strtotime(date('Y-m-d', $value) . ' 00:00:00');
    }

    /**
     * Set the timestamp to 1970-01-01
     */
    public function loadTime(int $value): bool|int
    {
        return strtotime('1970-01-01 ' . date('H:i:s', $value));
    }

    /**
     * Adjust start and end time of the event based on date, span, startTime and endTime
     */
    public function adjustTime(DataContainer $dc): void
    {
        // Return if there is no active record (override all)
        if (!$dc->activeRecord)
        {
            return;
        }

        $arrSet['date'] = strtotime(date('Y-m-d', $dc->activeRecord->date) . ' ' . date('H:i:s', $dc->activeRecord->time));
        $arrSet['time'] = $arrSet['date'];

        $db = Database::getInstance();
        $db->prepare("UPDATE tl_recommendation %s WHERE id=?")->set($arrSet)->execute($dc->id);
    }

    /**
     * @throws Exception
     */
    public function generateRecommendationAlias($varValue, DataContainer $dc)
    {
        $db = Database::getInstance();

        $aliasExists = (fn(string $alias): bool => $db->prepare("SELECT id FROM tl_recommendation WHERE alias=? AND id!=?")->execute($alias, $dc->id)->numRows > 0);

        // Generate alias if there is none
        if (!$varValue)
        {
            // Use alias prefix if no title has been set
            if (!$title = $dc->activeRecord->title)
            {
                $title = Config::get('recommendationAliasPrefix') ?? 'recommendation';
            }

            $varValue = System::getContainer()->get('contao.slug')->generate($title, RecommendationArchiveModel::findByPk($dc->activeRecord->pid)->jumpTo, $aliasExists);
        }
        elseif (preg_match('/^[1-9]\d*$/', (string) $varValue))
        {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
        }
        elseif ($aliasExists($varValue))
        {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        return $varValue;
    }

    public function checkRecommendationPermission(DataContainer $dc)
    {
        $objUser = Controller::getContainer()->get('security.helper')->getUser();

        if ($objUser->isAdmin)
        {
            return;
        }

        // Set the root IDs
        if (empty($objUser->recommendations) || !is_array($objUser->recommendations))
        {
            $root = [0];
        }
        else
        {
            $root = $objUser->recommendations;
        }

        $id = strlen(Input::get('id')) ? Input::get('id') : $dc->currentPid;
        $db = Database::getInstance();

        // Check current action
        switch (Input::get('act'))
        {
            case 'paste':
            case 'select':
                // Check currentPid
                if (!in_array($dc->currentPid, $root))
                {
                    throw new AccessDeniedException('Not enough permissions to access recommendation archive ID ' . $id . '.');
                }
                break;

            case 'create':
                if (!Input::get('pid') || !in_array(Input::get('pid'), $root))
                {
                    throw new AccessDeniedException('Not enough permissions to create recommendation items in recommendation archive ID ' . Input::get('pid') . '.');
                }
                break;

            case 'cut':
            case 'copy':
                if (Input::get('act') == 'cut' && Input::get('mode') == 1)
                {
                    $objArchive = $db->prepare("SELECT pid FROM tl_recommendation WHERE id=?")
                        ->limit(1)
                        ->execute(Input::get('pid'));

                    if ($objArchive->numRows < 1)
                    {
                        throw new AccessDeniedException('Invalid recommendation item ID ' . Input::get('pid') . '.');
                    }

                    $pid = $objArchive->pid;
                }
                else
                {
                    $pid = Input::get('pid');
                }

                if (!in_array($pid, $root))
                {
                    throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' recommendation item ID ' . $id . ' to recommendation archive ID ' . $pid . '.');
                }
            // no break

            case 'edit':
            case 'show':
            case 'delete':
            case 'toggle':
                $objArchive = $db->prepare("SELECT pid FROM tl_recommendation WHERE id=?")
                    ->limit(1)
                    ->execute($id);

                if ($objArchive->numRows < 1)
                {
                    throw new AccessDeniedException('Invalid recommendation item ID ' . $id . '.');
                }

                if (!in_array($objArchive->pid, $root))
                {
                    throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' recommendation item ID ' . $id . ' of recommendation archive ID ' . $objArchive->pid . '.');
                }
                break;

            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
            case 'cutAll':
            case 'copyAll':
                if (!in_array($id, $root))
                {
                    throw new AccessDeniedException('Not enough permissions to access recommendation archive ID ' . $id . '.');
                }

                $objArchive = $db->prepare("SELECT id FROM tl_recommendation WHERE pid=?")
                    ->execute($id);

                $objSession = System::getContainer()->get('request_stack')->getSession();

                $session = $objSession->all();
                $session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $objArchive->fetchEach('id'));
                $objSession->replace($session);
                break;

            default:
                if (Input::get('act'))
                {
                    throw new AccessDeniedException('Invalid command "' . Input::get('act') . '".');
                }

                if (!in_array($id, $root))
                {
                    throw new AccessDeniedException('Not enough permissions to access recommendation archive ID ' . $id . '.');
                }
                break;
        }
    }

    /**
     * List a recommendation record
     */
    public function listRecommendations(array $arrRow): string
    {
        if(!$arrRow['verified'])
        {
            return '<div class="tl_content_left">' . $arrRow['author'] . ' <span style="color:#fe3922;padding-left:3px">[' . ($GLOBALS['TL_LANG']['tl_recommendation']['notVerified'] ?? null) . ']</span></div>';
        }

        return '<div class="tl_content_left">' . $arrRow['author'] . ' <span style="color:#999;padding-left:3px">[' . Date::parse(Config::get('datimFormat'), $arrRow['date']) . ']</span></div>';
    }

    /**
     * Check for modified recommendation and update the XML files if necessary
     */
    public function generateSitemap(): void
    {
        /** @var SessionInterface $objSession */
        $objSession = System::getContainer()->get('request_stack')->getSession();

        $session = $objSession->get('recommendation_updater');

        if (empty($session) || !is_array($session))
        {
            return;
        }

        $automator = new Automator();
        $automator->generateSitemap();

        $objSession->set('recommendation_updater', null);
    }

    /**
     * Schedule a recommendation update
     *
     * This method is triggered when a single recommendation or multiple recommendations
     * are modified (edit/editAll), moved (cut/cutAll) or deleted (delete/deleteAll).
     * Since duplicated items are unpublished by default, it is not necessary to
     * schedule updates on copyAll as well.
     */
    public function scheduleUpdate(DataContainer $dc): void
    {
        // Return if there is no ID
        if (!$dc->activeRecord || !$dc->activeRecord->pid || Input::get('act') == 'copy')
        {
            return;
        }

        /** @var SessionInterface $objSession */
        $objSession = System::getContainer()->get('request_stack')->getSession();

        // Store the ID in the session
        $session = $objSession->get('recommendation_updater');
        $session[] = $dc->activeRecord->pid;
        $objSession->set('recommendation_updater', array_unique($session));
    }

    public function addSitemapCacheInvalidationTag(DataContainer $dc, array $tags): array
    {
        $archiveModel = RecommendationArchiveModel::findByPk($dc->activeRecord->pid);
        $pageModel = PageModel::findWithDetails($archiveModel->jumpTo);

        if ($pageModel === null)
        {
            return $tags;
        }

        return array_merge($tags, ['contao.sitemap.' . $pageModel->rootId]);
    }
}
