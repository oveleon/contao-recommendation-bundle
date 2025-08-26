<?php

declare(strict_types=1);

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

use Contao\CoreBundle\ContaoCoreBundle;
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
                'attributes'          => 'data-action="contao--scroll-offset#store"'
            ],
            'all',
        ],
        'operations' => [
            'edit' => [
                'href'                => 'act=edit',
                'icon'                => 'edit.svg',
                'button_callback'     => [RecommendationArchiveListener::class, 'edit']
            ],
            'children',
            'copy' => [
                'href'                => 'act=copy',
                'icon'                => 'copy.svg',
                'button_callback'     => [RecommendationArchiveListener::class, 'copyArchive']
            ],
            'delete' => [
                'href'                => 'act=delete',
                'icon'                => 'delete.svg',
                'attributes'          => 'data-action="contao--scroll-offset#store" onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirmFile'] ?? null) . '\'))return false"',
                'button_callback'     => [RecommendationArchiveListener::class, 'deleteArchive']
            ],
            'show',
        ]
    ],

    // Palettes
    'palettes' => [
        '__selector__'                => ['protected'],
        'default'                     => '{title_legend},title,jumpTo;{protected_legend:collapsed},protected;{expert_legend:collapsed},useAutoItem'
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

// Backwards compatibility for old icons and position
$version = ContaoCoreBundle::getVersion();

if (version_compare($version, '5', '<'))
{
    $GLOBALS['TL_DCA']['tl_recommendation_archive']['list']['operations']['edit']['icon'] = 'header.svg';
    $GLOBALS['TL_DCA']['tl_recommendation_archive']['list']['operations']['children']['icon'] = 'edit.svg';

    // Swap places for backwards compatibility
    [
        $GLOBALS['TL_DCA']['tl_recommendation_archive']['list']['operations']['children'],
        $GLOBALS['TL_DCA']['tl_recommendation_archive']['list']['operations']['edit']
    ] = [
        $GLOBALS['TL_DCA']['tl_recommendation_archive']['list']['operations']['edit'],
        $GLOBALS['TL_DCA']['tl_recommendation_archive']['list']['operations']['children']
    ];
}
