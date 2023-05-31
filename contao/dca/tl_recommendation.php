<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

use Contao\DataContainer;
use Contao\DC_Table;
use Oveleon\ContaoRecommendationBundle\EventListener\DataContainer\DataContainerListener;
use Oveleon\ContaoRecommendationBundle\RecommendationArchiveModel;

$GLOBALS['TL_DCA']['tl_recommendation'] = [
    // Config
    'config' => [
        'dataContainer'               => DC_Table::class,
        'ptable'                      => 'tl_recommendation_archive',
        'switchToEdit'                => true,
        'enableVersioning'            => true,
        'onload_callback' => [
            [DataContainerListener::class, 'checkRecommendationPermission'],
            ['tl_recommendation', 'generateSitemap']
        ],
        'oncut_callback' => [
            ['tl_recommendation', 'scheduleUpdate']
        ],
        'ondelete_callback' => [
            ['tl_recommendation', 'scheduleUpdate']
        ],
        'onsubmit_callback' => [
            [DataContainerListener::class, 'adjustTime'],
            ['tl_recommendation', 'scheduleUpdate']
        ],
        'oninvalidate_cache_tags_callback' => [
            ['tl_recommendation', 'addSitemapCacheInvalidationTag'],
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'alias' => 'index',
                'pid,start,stop,published' => 'index'
            ]
        ]
    ],

    // List
    'list' => [
        'sorting' => [
            'mode'                    => DataContainer::MODE_PARENT,
            'fields'                  => ['date DESC'],
            'headerFields'            => ['title', 'jumpTo', 'tstamp', 'protected'],
            'panelLayout'             => 'filter;sort,search,limit',
            'child_record_callback'   => ['tl_recommendation', 'listRecommendations'],
            'child_record_class'      => 'no_padding'
        ],
        'global_operations' => [
            'all' => [
                'href'                => 'act=select',
                'class'               => 'header_edit_all',
                'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ],
        'operations' => [
            'edit' => [
                'href'                => 'act=edit',
                'icon'                => 'edit.svg'
            ],
            'copy' => [
                'href'                => 'act=paste&amp;mode=copy',
                'icon'                => 'copy.svg'
            ],
            'cut' => [
                'href'                => 'act=paste&amp;mode=cut',
                'icon'                => 'cut.svg'
            ],
            'delete' => [
                'href'                => 'act=delete',
                'icon'                => 'delete.svg',
                'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"'
            ],
            'toggle' => [
                'href'                => 'act=toggle&amp;field=published',
                'icon'                => 'visible.svg',
                'showInHeader'        => true
            ],
            'feature' => [
                'href'                => 'act=toggle&amp;field=featured',
                'icon'                => 'featured.svg',
            ],
            'show' => [
                'href'                => 'act=show',
                'icon'                => 'show.svg'
            ]
        ]
    ],

    // Palettes
    'palettes' => [
        'default'                     => '{title_legend},author,title,alias,email,location;{date_legend},date,time;{recommendation_legend},text,imageUrl,rating,customField;{teaser_legend:hide},teaser;{expert_legend:hide},cssClass,featured;{publish_legend},published,start,stop'
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ],
        'pid' => [
            'foreignKey'              => 'tl_recommendation_archive.title',
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
            'relation'                => ['type'=>'belongsTo', 'load'=>'lazy']
        ],
        'tstamp' => [
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ],
        'title' => [
            'exclude'                 => true,
            'search'                  => true,
            'sorting'                 => true,
            'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
            'inputType'               => 'text',
            'eval'                    => ['maxlength'=>255, 'tl_class'=>'w50 clr'],
            'sql'                     => "varchar(255) NOT NULL default ''"
        ],
        'alias' => [
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => ['rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>128, 'tl_class'=>'w50'],
            'save_callback' => [
                [DataContainerListener::class, 'generateRecommendationAlias']
            ],
            'sql'                     => "varchar(255) BINARY NOT NULL default ''"
        ],
        'author' => [
            'exclude'                 => true,
            'search'                  => true,
            'sorting'                 => true,
            'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
            'inputType'               => 'text',
            'eval'                    => ['doNotCopy'=>true, 'mandatory'=>true, 'maxlength'=>128, 'tl_class'=>'w50'],
            'sql'                     => "varchar(128) NOT NULL default ''"
        ],
        'email' => [
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => ['doNotCopy'=>true, 'maxlength'=>255, 'rgxp'=>'email', 'decodeEntities'=>true, 'tl_class'=>'w50'],
            'sql'                     => "varchar(255) NOT NULL default ''"
        ],
        'location' => [
            'exclude'                 => true,
            'search'                  => true,
            'sorting'                 => true,
            'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
            'inputType'               => 'text',
            'eval'                    => ['doNotCopy'=>true, 'maxlength'=>128, 'tl_class'=>'w50'],
            'sql'                     => "varchar(128) NOT NULL default ''"
        ],
        'date' => [
            'default'                 => time(),
            'exclude'                 => true,
            'filter'                  => true,
            'sorting'                 => true,
            'flag'                    => DataContainer::SORT_MONTH_DESC,
            'inputType'               => 'text',
            'eval'                    => ['rgxp'=>'date', 'mandatory'=>true, 'doNotCopy'=>true, 'datepicker'=>true, 'tl_class'=>'w50 wizard'],
            'load_callback' => [
                [DataContainerListener::class, 'loadDate']
            ],
            'sql'                     => "int(10) unsigned NOT NULL default 0"
        ],
        'time' => [
            'default'                 => time(),
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => ['rgxp'=>'time', 'mandatory'=>true, 'doNotCopy'=>true, 'tl_class'=>'w50'],
            'load_callback' => [
                [DataContainerListener::class, 'loadTime']
            ],
            'sql'                     => "int(10) unsigned NOT NULL default 0"
        ],
        'teaser' => [
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'textarea',
            'eval'                    => ['rte'=>'tinyMCE', 'tl_class'=>'clr'],
            'sql'                     => "mediumtext NULL"
        ],
        'text' => [
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'textarea',
            'eval'                    => ['mandatory'=>true, 'rte'=>'tinyMCE', 'tl_class'=>'clr'],
            'sql'                     => "mediumtext NULL"
        ],
        'imageUrl' => [
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => ['rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>255, 'dcaPicker'=>true, 'tl_class'=>'w50 wizard'],
            'sql'                     => "varchar(255) NOT NULL default ''"
        ],
        'rating' => [
            'default'                 => 5,
            'exclude'                 => true,
            'search'                  => true,
            'filter'                  => true,
            'sorting'                 => true,
            'inputType'               => 'select',
            'options'                 => [1,2,3,4,5],
            'eval'                    => ['mandatory'=>true, 'tl_class'=>'w50'],
            'sql'                     => "char(1) NOT NULL default ''"
        ],
        'customField' => [
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => ['doNotCopy'=>true, 'maxlength'=>255, 'tl_class'=>'w100 clr'],
            'sql'                     => "varchar(255) NOT NULL default ''"
        ],
        'cssClass' => [
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => ['tl_class'=>'w50'],
            'sql'                     => "varchar(255) NOT NULL default ''"
        ],
        'featured' => [
            'exclude'                 => true,
            'toggle'                  => true,
            'filter'                  => true,
            'inputType'               => 'checkbox',
            'eval'                    => ['tl_class'=>'w50 m12'],
            'sql'                     => "char(1) NOT NULL default ''"
        ],
        'verified' => [
            'filter'                  => true,
            'eval'                    => ['isBoolean'=>true],
            'sql'                     => "char(1) NOT NULL default '1'"
        ],
        'published' => [
            'exclude'                 => true,
            'toggle'                  => true,
            'filter'                  => true,
            'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
            'inputType'               => 'checkbox',
            'eval'                    => ['doNotCopy'=>true],
            'sql'                     => "char(1) NOT NULL default ''"
        ],
        'start' => [
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'],
            'sql'                     => "varchar(10) NOT NULL default ''"
        ],
        'stop' => [
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'],
            'sql'                     => "varchar(10) NOT NULL default ''"
        ]
    ]
];

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Fabian Ekert <fabian@oveleon.de>
 */
class tl_recommendation extends Contao\Backend
{
    /**
     * Import the back end user object
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('Contao\BackendUser', 'User');
    }

    /**
     * List a recommendation record
     *
     * @param array $arrRow
     *
     * @return string
     */
    public function listRecommendations($arrRow)
    {
        if(!$arrRow['verified'])
        {
            return '<div class="tl_content_left">' . $arrRow['author'] . ' <span style="color:#fe3922;padding-left:3px">[' . $GLOBALS['TL_LANG']['tl_recommendation']['notVerified'] . ']</span></div>';
        }

        return '<div class="tl_content_left">' . $arrRow['author'] . ' <span style="color:#999;padding-left:3px">[' . Date::parse(Config::get('datimFormat'), $arrRow['date']) . ']</span></div>';
    }

    /**
     * Check for modified recommendation and update the XML files if necessary
     */
    public function generateSitemap()
    {
        /** @var Symfony\Component\HttpFoundation\Session\SessionInterface $objSession */
        $objSession = Contao\System::getContainer()->get('session');

        $session = $objSession->get('recommendation_updater');

        if (empty($session) || !is_array($session))
        {
            return;
        }

        $this->import('Contao\Automator', 'Automator');
        $this->Automator->generateSitemap();

        $objSession->set('recommendation_updater', null);
    }

    /**
     * Schedule a recommendation update
     *
     * This method is triggered when a single recommendation or multiple recommendations
     * are modified (edit/editAll), moved (cut/cutAll) or deleted (delete/deleteAll).
     * Since duplicated items are unpublished by default, it is not necessary to
     * schedule updates on copyAll as well.
     *
     * @param Contao\DataContainer $dc
     */
    public function scheduleUpdate(Contao\DataContainer $dc)
    {
        // Return if there is no ID
        if (!$dc->activeRecord || !$dc->activeRecord->pid || Contao\Input::get('act') == 'copy')
        {
            return;
        }

        /** @var Symfony\Component\HttpFoundation\Session\SessionInterface $objSession */
        $objSession = Contao\System::getContainer()->get('session');

        // Store the ID in the session
        $session = $objSession->get('recommendation_updater');
        $session[] = $dc->activeRecord->pid;
        $objSession->set('recommendation_updater', array_unique($session));
    }

    /**
     * @param Contao\DataContainer $dc
     *
     * @return array
     */
    public function addSitemapCacheInvalidationTag($dc, array $tags)
    {
        $archiveModel = RecommendationArchiveModel::findByPk($dc->activeRecord->pid);
        $pageModel = Contao\PageModel::findWithDetails($archiveModel->jumpTo);

        if ($pageModel === null)
        {
            return $tags;
        }

        return array_merge($tags, ['contao.sitemap.' . $pageModel->rootId]);
    }
}
