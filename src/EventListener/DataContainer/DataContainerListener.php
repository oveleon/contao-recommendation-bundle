<?php

namespace Oveleon\ContaoRecommendationBundle\EventListener\DataContainer;

use Contao\Controller;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Contao\DataContainer;
use Contao\Input;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Oveleon\ContaoRecommendationBundle\RecommendationArchiveModel;
use Symfony\Component\Security\Core\Security;

class DataContainerListener
{
    public function __construct(
        protected ContaoFramework $framework,
        protected Connection $connection,
        protected Security $security
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
     * @throws Exception
     */
    public function adjustTime(DataContainer $dc)
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

        $aliasExists = function (string $alias) use ($dc, $db): bool
        {
            return $db->prepare("SELECT id FROM tl_recommendation WHERE alias=? AND id!=?")->execute($alias, $dc->id)->numRows > 0;
        };

        // Generate alias if there is none
        if (!$varValue)
        {
            $varValue = System::getContainer()->get('contao.slug')->generate($dc->activeRecord->title, RecommendationArchiveModel::findByPk($dc->activeRecord->pid)->jumpTo, $aliasExists);
        }
        elseif (preg_match('/^[1-9]\d*$/', $varValue))
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
                // Check currentPid here (see #247)
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
}
