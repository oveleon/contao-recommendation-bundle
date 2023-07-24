<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

use Contao\DataContainer;
use Contao\DC_Table;
use Oveleon\ContaoRecommendationBundle\EventListener\DataContainer\RecommendationArchiveListener;

$GLOBALS['TL_DCA']['tl_recommendation_archive'] = [
    // Config
    'config' => [
        'dataContainer'               => DC_Table::class,
        'ctable'                      => ['tl_recommendation'],
        'switchToEdit'                => true,
        'enableVersioning'            => true,
        'oncreate_callback' => [
            [RecommendationArchiveListener::class, 'adjustPermissions']
        ],
        'oncopy_callback' => [
            [RecommendationArchiveListener::class, 'adjustPermissions']
        ],
        'oninvalidate_cache_tags_callback' => [
            [RecommendationArchiveListener::class, 'addSitemapCacheInvalidationTag'],
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ],

    // List
    'list' => [
        'sorting' => [
            'mode'                    => DataContainer::MODE_SORTED,
            'fields'                  => ['title'],
            'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout'             => 'filter;search,limit'
        ],
        'label' => [
            'fields'                  => ['title'],
            'format'                  => '%s'
        ],
        'global_operations' => [
            'settings' => [
                'href'                => 'do=recommendation_settings',
                'class'				  => '',
                'icon'                => 'edit.svg',
                'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ],
            'all' => [
                'href'                => 'act=select',
                'class'               => 'header_edit_all',
                'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ],
        'operations' => [
            'editheader' => [
                'href'                => 'act=edit',
                'icon'                => 'header.svg',
                'button_callback'     => [RecommendationArchiveListener::class, 'editHeader']
            ],
            'edit' => [
                'href'                => 'table=tl_recommendation',
                'icon'                => 'edit.svg'
            ],
            'copy' => [
                'href'                => 'act=copy',
                'icon'                => 'copy.svg',
                'button_callback'     => [RecommendationArchiveListener::class, 'copyArchive']
            ],
            'delete' => [
                'href'                => 'act=delete',
                'icon'                => 'delete.svg',
                'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
                'button_callback'     => [RecommendationArchiveListener::class, 'deleteArchive']
            ],
            'show' => [
                'href'                => 'act=show',
                'icon'                => 'show.svg'
            ]
        ]
    ],

    // Palettes
    'palettes' => [
        '__selector__'                => ['protected'],
        'default'                     => '{title_legend},title,jumpTo,useAutoItem;{protected_legend:hide},protected'
    ],

    // Subpalettes
    'subpalettes' => [
        'protected'                   => 'groups'
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ],
        'tstamp' => [
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ],
        'title' => [
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => ['mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'],
            'sql'                     => "varchar(255) NOT NULL default ''"
        ],
        'jumpTo' => [
            'exclude'                 => true,
            'inputType'               => 'pageTree',
            'foreignKey'              => 'tl_page.title',
            'eval'                    => ['fieldType'=>'radio', 'tl_class'=>'clr'],
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
            'relation'                => ['type'=>'hasOne', 'load'=>'eager']
        ],
        'protected' => [
            'exclude'                 => true,
            'filter'                  => true,
            'inputType'               => 'checkbox',
            'eval'                    => ['submitOnChange'=>true],
            'sql'                     => "char(1) NOT NULL default ''"
        ],
        'useAutoItem' => [
            'exclude'                 => true,
            'filter'                  => true,
            'inputType'               => 'checkbox',
            'sql'                     => "char(1) NOT NULL default ''"
        ],
        'groups' => [
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'foreignKey'              => 'tl_member_group.name',
            'eval'                    => ['mandatory'=>true, 'multiple'=>true],
            'sql'                     => "blob NULL",
            'relation'                => ['type'=>'hasMany', 'load'=>'lazy']
        ]
    ]
];
