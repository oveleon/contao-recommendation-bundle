<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

use Oveleon\ContaoRecommendationBundle\RecommendationArchiveModel;

$GLOBALS['TL_DCA']['tl_recommendation'] = [
    // Config
    'config' => [
        'dataContainer'               => 'Table',
        'ptable'                      => 'tl_recommendation_archive',
        'switchToEdit'                => true,
        'enableVersioning'            => true,
        'onload_callback' => [
            ['tl_recommendation', 'checkPermission'],
            ['tl_recommendation', 'generateSitemap']
        ],
        'oncut_callback' => [
            ['tl_recommendation', 'scheduleUpdate']
        ],
        'ondelete_callback' => [
            ['tl_recommendation', 'scheduleUpdate']
        ],
        'onsubmit_callback' => [
            ['tl_recommendation', 'adjustTime'],
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
            'mode'                    => 4,
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
                'icon'                => 'visible.svg',
                'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
                'button_callback'     => ['tl_recommendation', 'toggleIcon']
            ],
            'feature' => [
                'icon'                => 'featured.svg',
                'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleFeatured(this,%s)"',
                'button_callback'     => ['tl_recommendation', 'iconFeatured']
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
            'flag'                    => 1,
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
                ['tl_recommendation', 'generateAlias']
            ],
            'sql'                     => "varchar(255) BINARY NOT NULL default ''"
        ],
        'author' => [
            'exclude'                 => true,
            'search'                  => true,
            'sorting'                 => true,
            'flag'                    => 1,
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
            'flag'                    => 1,
            'inputType'               => 'text',
            'eval'                    => ['doNotCopy'=>true, 'maxlength'=>128, 'tl_class'=>'w50'],
            'sql'                     => "varchar(128) NOT NULL default ''"
        ],
        'date' => [
            'default'                 => time(),
            'exclude'                 => true,
            'filter'                  => true,
            'sorting'                 => true,
            'flag'                    => 8,
            'inputType'               => 'text',
            'eval'                    => ['rgxp'=>'date', 'mandatory'=>true, 'doNotCopy'=>true, 'datepicker'=>true, 'tl_class'=>'w50 wizard'],
            'load_callback' => [
                ['tl_recommendation', 'loadDate']
            ],
            'sql'                     => "int(10) unsigned NOT NULL default 0"
        ],
        'time' => [
            'default'                 => time(),
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => ['rgxp'=>'time', 'mandatory'=>true, 'doNotCopy'=>true, 'tl_class'=>'w50'],
            'load_callback' => [
                ['tl_recommendation', 'loadTime']
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
            'filter'                  => true,
            'flag'                    => 1,
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
     * Check permissions to edit table tl_recommendation
     *
     * @throws Contao\CoreBundle\Exception\AccessDeniedException
     */
    public function checkPermission()
    {
        if ($this->User->isAdmin)
        {
            return;
        }

        // Set the root IDs
        if (empty($this->User->recommendations) || !is_array($this->User->recommendations))
        {
            $root = [0];
        }
        else
        {
            $root = $this->User->recommendations;
        }

        $id = strlen(Contao\Input::get('id')) ? Contao\Input::get('id') : CURRENT_ID;

        // Check current action
        switch (Contao\Input::get('act'))
        {
            case 'paste':
            case 'select':
                if (!in_array(CURRENT_ID, $root))
                {
                    throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to access recommendation archive ID ' . $id . '.');
                }
                break;

            case 'create':
                if (!Contao\Input::get('pid') || !in_array(Contao\Input::get('pid'), $root))
                {
                    throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to create recommendation item in recommendation archive ID ' . Input::get('pid') . '.');
                }
                break;

            case 'cut':
            case 'copy':
                if (Contao\Input::get('act') == 'cut' && Contao\Input::get('mode') == 1)
                {
                    $objArchive = $this->Database->prepare("SELECT pid FROM tl_recommendation WHERE id=?")
                        ->limit(1)
                        ->execute(Contao\Input::get('pid'));

                    if ($objArchive->numRows < 1)
                    {
                        throw new Contao\CoreBundle\Exception\AccessDeniedException('Invalid recommendation item ID ' . Contao\Input::get('pid') . '.');
                    }

                    $pid = $objArchive->pid;
                }
                else
                {
                    $pid = Input::get('pid');
                }

                if (!in_array($pid, $root))
                {
                    throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to ' . Contao\Input::get('act') . ' recommendation item ID ' . $id . ' to recommendation archive ID ' . $pid . '.');
                }
            // no break

            case 'edit':
            case 'show':
            case 'delete':
            case 'toggle':
            case 'feature':
                $objArchive = $this->Database->prepare("SELECT pid FROM tl_recommendation WHERE id=?")
                    ->limit(1)
                    ->execute($id);

                if ($objArchive->numRows < 1)
                {
                    throw new Contao\CoreBundle\Exception\AccessDeniedException('Invalid recommendation item ID ' . $id . '.');
                }

                if (!in_array($objArchive->pid, $root))
                {
                    throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to ' . Contao\Input::get('act') . ' recommendation item ID ' . $id . ' of recommendation archive ID ' . $objArchive->pid . '.');
                }
                break;

            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
            case 'cutAll':
            case 'copyAll':
                if (!in_array($id, $root))
                {
                    throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to access recommendation archive ID ' . $id . '.');
                }

                $objArchive = $this->Database->prepare("SELECT id FROM tl_recommendation WHERE pid=?")
                    ->execute($id);

                /** @var Symfony\Component\HttpFoundation\Session\SessionInterface $objSession */
                $objSession = Contao\System::getContainer()->get('session');

                $session = $objSession->all();
                $session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $objArchive->fetchEach('id'));
                $objSession->replace($session);
                break;

            default:
                if (Contao\Input::get('act'))
                {
                    throw new Contao\CoreBundle\Exception\AccessDeniedException('Invalid command "' . Contao\Input::get('act') . '".');
                }

                if (!in_array($id, $root))
                {
                    throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to access recommendation archive ID ' . $id . '.');
                }
                break;
        }
    }

    /**
     * Auto-generate the recommendation alias if it has not been set yet
     *
     * @param mixed                $varValue
     * @param Contao\DataContainer $dc
     *
     * @return string
     *
     * @throws Exception
     */
    public function generateAlias($varValue, Contao\DataContainer $dc)
    {
        $autoAlias = false;

        // Generate alias if title is set
        if ($varValue == '' && !empty($dc->activeRecord->title))
        {
            $autoAlias = true;
            $varValue = Contao\StringUtil::generateAlias($dc->activeRecord->title);
        }

        $objAlias = $this->Database->prepare("SELECT id FROM tl_recommendation WHERE alias=? AND alias!='' AND id!=?")
                                   ->execute($varValue, $dc->id);

        // Check whether the recommendation alias exists
        if ($objAlias->numRows)
        {
            if (!$autoAlias)
            {
                throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
            }

            $varValue .= '-' . $dc->id;
        }

        return $varValue;
    }

    /**
     * Set the timestamp to 00:00:00
     *
     * @param integer $value
     *
     * @return integer
     */
    public function loadDate($value)
    {
        return strtotime(date('Y-m-d', $value) . ' 00:00:00');
    }

    /**
     * Set the timestamp to 1970-01-01
     *
     * @param integer $value
     *
     * @return integer
     */
    public function loadTime($value)
    {
        return strtotime('1970-01-01 ' . date('H:i:s', $value));
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
     * Adjust start end end time of the event based on date, span, startTime and endTime
     *
     * @param Contao\DataContainer $dc
     */
    public function adjustTime(Contao\DataContainer $dc)
    {
        // Return if there is no active record (override all)
        if (!$dc->activeRecord)
        {
            return;
        }

        $arrSet['date'] = strtotime(date('Y-m-d', $dc->activeRecord->date) . ' tl_recommendation.php' . date('H:i:s', $dc->activeRecord->time));
        $arrSet['time'] = $arrSet['date'];

        $this->Database->prepare("UPDATE tl_recommendation %s WHERE id=?")->set($arrSet)->execute($dc->id);
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
     * Return the "feature/unfeature element" button
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function iconFeatured($row, $href, $label, $title, $icon, $attributes)
    {
        if (strlen(Contao\Input::get('fid')))
        {
            $this->toggleFeatured(Contao\Input::get('fid'), (Input::get('state') == 1), (@func_get_arg(12) ?: null));
            $this->redirect($this->getReferer());
        }

        // Check permissions AFTER checking the fid, so hacking attempts are logged
        if (!$this->User->hasAccess('tl_recommendation::featured', 'alexf'))
        {
            return '';
        }

        $href .= '&amp;fid='.$row['id'].'&amp;state='.($row['featured'] ? '' : 1);

        if (!$row['featured'])
        {
            $icon = 'featured_.svg';
        }

        return '<a href="'.$this->addToUrl($href).'" title="'.Contao\StringUtil::specialchars($title).'"'.$attributes.'>'.Contao\Image::getHtml($icon, $label, 'data-state="' . ($row['featured'] ? 1 : 0) . '"').'</a> ';
    }

    /**
     * Feature/unfeature a recommendation
     *
     * @param integer              $intId
     * @param boolean              $blnVisible
     * @param Contao\DataContainer $dc
     *
     * @throws Contao\CoreBundle\Exception\AccessDeniedException
     */
    public function toggleFeatured($intId, $blnVisible, Contao\DataContainer $dc=null)
    {
        // Check permissions to edit
        Contao\Input::setGet('id', $intId);
        Contao\Input::setGet('act', 'feature');
        $this->checkPermission();

        // Check permissions to feature
        if (!$this->User->hasAccess('tl_recommendation::featured', 'alexf'))
        {
            throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to feature/unfeature recommendation ID ' . $intId . '.');
        }

        $objVersions = new Contao\Versions('tl_recommendation', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (is_array($GLOBALS['TL_DCA']['tl_recommendation']['fields']['featured']['save_callback'] ?? null))
        {
            foreach ($GLOBALS['TL_DCA']['tl_recommendation']['fields']['featured']['save_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    $this->import($callback[0]);
                    $blnVisible = $this->{$callback[0]}->{$callback[1]}($blnVisible, $dc);
                }
                elseif (is_callable($callback))
                {
                    $blnVisible = $callback($blnVisible, $this);
                }
            }
        }

        // Update the database
        $this->Database->prepare("UPDATE tl_recommendation SET tstamp=". time() .", featured='" . ($blnVisible ? 1 : '') . "' WHERE id=?")
                       ->execute($intId);

        $objVersions->create();

        if ($dc)
        {
            $dc->invalidateCacheTags();
        }
    }

    /**
     * Return the "toggle visibility" button
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
    {
        if (strlen(Input::get('tid')))
        {
            $this->toggleVisibility(Contao\Input::get('tid'), (Contao\Input::get('state') == 1), (func_num_args() <= 12 ? null : func_get_arg(12)));
            $this->redirect($this->getReferer());
        }

        // Check permissions AFTER checking the tid, so hacking attempts are logged
        if (!$this->User->hasAccess('tl_recommendation::published', 'alexf'))
        {
            return '';
        }

        $href .= '&amp;tid=' . $row['id'] . '&amp;state=' . ($row['published'] ? '' : 1);

        if (!$row['published'])
        {
            $icon = 'invisible.svg';
        }

        return '<a href="' . $this->addToUrl($href) . '" title="' . Contao\StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label, 'data-state="' . ($row['published'] ? 1 : 0) . '"') . '</a> ';
    }

    /**
     * Disable/enable a recommendation
     *
     * @param integer              $intId
     * @param boolean              $blnVisible
     * @param Contao\DataContainer $dc
     */
    public function toggleVisibility($intId, $blnVisible, Contao\DataContainer $dc=null)
    {
        // Set the ID and action
        Contao\Input::setGet('id', $intId);
        Contao\Input::setGet('act', 'toggle');

        if ($dc)
        {
            $dc->id = $intId;
        }

        // Trigger the onload_callback
        if (is_array($GLOBALS['TL_DCA']['tl_recommendation']['config']['onload_callback'] ?? null))
        {
            foreach ($GLOBALS['TL_DCA']['tl_recommendation']['config']['onload_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    $this->import($callback[0]);
                    $this->{$callback[0]}->{$callback[1]}($dc);
                }
                elseif (is_callable($callback))
                {
                    $callback($dc);
                }
            }
        }

        // Check the field access
        if (!$this->User->hasAccess('tl_recommendation::published', 'alexf'))
        {
            throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to publish/unpublish recommendation ID ' . $intId . '.');
        }

        // Set the current record
        if ($dc)
        {
            $objRow = $this->Database->prepare("SELECT * FROM tl_recommendation WHERE id=?")
                                     ->limit(1)
                                     ->execute($intId);

            if ($objRow->numRows)
            {
                $dc->activeRecord = $objRow;
            }
        }

        $objVersions = new Contao\Versions('tl_recommendation', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (is_array($GLOBALS['TL_DCA']['tl_recommendation']['fields']['published']['save_callback'] ?? null))
        {
            foreach ($GLOBALS['TL_DCA']['tl_recommendation']['fields']['published']['save_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    $this->import($callback[0]);
                    $blnVisible = $this->{$callback[0]}->{$callback[1]}($blnVisible, $dc);
                }
                elseif (is_callable($callback))
                {
                    $blnVisible = $callback($blnVisible, $dc);
                }
            }
        }

        $time = time();

        // Update the database
        $this->Database->prepare("UPDATE tl_recommendation SET tstamp=$time, published='" . ($blnVisible ? '1' : '') . "' WHERE id=?")
                       ->execute($intId);

        if ($dc)
        {
            $dc->activeRecord->tstamp = $time;
            $dc->activeRecord->published = ($blnVisible ? '1' : '');
        }

        // Trigger the onsubmit_callback
        if (is_array($GLOBALS['TL_DCA']['tl_recommendation']['config']['onsubmit_callback'] ?? null))
        {
            foreach ($GLOBALS['TL_DCA']['tl_recommendation']['config']['onsubmit_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    $this->import($callback[0]);
                    $this->{$callback[0]}->{$callback[1]}($dc);
                }
                elseif (is_callable($callback))
                {
                    $callback($dc);
                }
            }
        }

        $objVersions->create();

        if ($dc)
        {
            $dc->invalidateCacheTags();
        }
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