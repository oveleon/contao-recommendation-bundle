<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

use Contao\DataContainer;
use Contao\DC_Table;
use Oveleon\ContaoRecommendationBundle\EventListener\DataContainer\RecommendationListener;

$GLOBALS['TL_DCA']['tl_recommendation'] = [
    // Config
    'config' => [
        'dataContainer'               => DC_Table::class,
        'ptable'                      => 'tl_recommendation_archive',
        'switchToEdit'                => true,
        'enableVersioning'            => true,
        'onload_callback' => [
            [RecommendationListener::class, 'checkRecommendationPermission'],
            [RecommendationListener::class, 'generateSitemap']
        ],
        'oncut_callback' => [
            [RecommendationListener::class, 'scheduleUpdate']
        ],
        'ondelete_callback' => [
            [RecommendationListener::class, 'scheduleUpdate']
        ],
        'onsubmit_callback' => [
            [RecommendationListener::class, 'adjustTime'],
            [RecommendationListener::class, 'scheduleUpdate']
        ],
        'oninvalidate_cache_tags_callback' => [
            [RecommendationListener::class, 'addSitemapCacheInvalidationTag'],
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
            'child_record_callback'   => [RecommendationListener::class, 'listRecommendations'],
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
                [RecommendationListener::class, 'generateRecommendationAlias']
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
                [RecommendationListener::class, 'loadDate']
            ],
            'sql'                     => "int(10) unsigned NOT NULL default 0"
        ],
        'time' => [
            'default'                 => time(),
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => ['rgxp'=>'time', 'mandatory'=>true, 'doNotCopy'=>true, 'tl_class'=>'w50'],
            'load_callback' => [
                [RecommendationListener::class, 'loadTime']
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
