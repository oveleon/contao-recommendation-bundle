<?php

namespace Oveleon\ContaoRecommendationBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Database;
use Contao\DataContainer;
use Contao\Input;
use Contao\Message;
use Contao\ModuleModel;
use Contao\System;

class ModuleListener
{
    #[AsCallback(table: 'tl_module', target: 'config.onload')]
    public function showJsLibraryHint(DataContainer $dc): void
    {
        if ($_POST || Input::get('act') != 'edit')
        {
            return;
        }

        $security = System::getContainer()->get('security.helper');

        if (
            !$security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'themes') ||
            !$security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_LAYOUTS)
        ) {
            return;
        }

        $objModule = ModuleModel::findByPk($dc->id);

        if (null !== $objModule && 'recommendationlist' === $objModule->type)
        {
            // Get module
            $objModule = Database::getInstance()->prepare("SELECT * FROM " . $dc->table . " WHERE id=?")
                ->limit(1)
                ->execute($dc->id);

            if (null !== $objModule && !!$objModule->recommendation_useDialog)
            {
                Message::addInfo(sprintf(($GLOBALS['TL_LANG']['tl_module']['includeRecTemplate'] ?? null), 'js_recommendation'));
            }
        }
    }
}
