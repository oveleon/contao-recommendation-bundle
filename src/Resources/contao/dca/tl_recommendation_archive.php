<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

$GLOBALS['TL_DCA']['tl_recommendation_archive'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ctable'                      => array('tl_recommendation'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'onload_callback' => array
		(
			array('tl_recommendation_archive', 'checkPermission')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 1,
			'fields'                  => array('title'),
			'flag'                    => 1,
			'panelLayout'             => 'filter;search,limit'
		),
		'label' => array
		(
			'fields'                  => array('title'),
			'format'                  => '%s'
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_recommendation_archive']['edit'],
				'href'                => 'table=tl_recommendation',
				'icon'                => 'edit.svg'
			),
			'editheader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_recommendation_archive']['editheader'],
				'href'                => 'act=edit',
				'icon'                => 'header.svg',
				'button_callback'     => array('tl_recommendation_archive', 'editHeader')
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_recommendation_archive']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.svg',
				'button_callback'     => array('tl_recommendation_archive', 'copyArchive')
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_recommendation_archive']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"',
				'button_callback'     => array('tl_recommendation_archive', 'deleteArchive')
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_recommendation_archive']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('protected'),
		'default'                     => '{title_legend},title,jumpTo;{protected_legend:hide},protected'
	),

	// Subpalettes
	'subpalettes' => array
	(
		'protected'                   => 'groups'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_recommendation_archive']['title'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'jumpTo' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_recommendation_archive']['jumpTo'],
			'exclude'                 => true,
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('mandatory'=>true, 'fieldType'=>'radio', 'tl_class'=>'clr'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
			'relation'                => array('type'=>'hasOne', 'load'=>'eager')
		),
		'protected' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_recommendation_archive']['protected'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'groups' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_recommendation_archive']['groups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true),
			'sql'                     => "blob NULL",
			'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Fabian Ekert <fabian@oveleon.de>
 */
class tl_recommendation_archive extends Backend
{

	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	/**
	 * Check permissions to edit table tl_recommendation_archive
	 *
	 * @throws Contao\CoreBundle\Exception\AccessDeniedException
	 */
	public function checkPermission()
	{
		if ($this->User->isAdmin)
		{
			return;
		}

		// Set root IDs
		if (empty($this->User->recommendations) || !\is_array($this->User->recommendations))
		{
			$root = array(0);
		}
		else
		{
			$root = $this->User->recommendations;
		}

		$GLOBALS['TL_DCA']['tl_recommendation_archive']['list']['sorting']['root'] = $root;

		// Check permissions to add archives
		if (!$this->User->hasAccess('create', 'recommendationp'))
		{
			$GLOBALS['TL_DCA']['tl_recommendation_archive']['config']['closed'] = true;
		}

		/** @var Symfony\Component\HttpFoundation\Session\SessionInterface $objSession */
		$objSession = System::getContainer()->get('session');

		// Check current action
		switch (Input::get('act'))
		{
			case 'create':
			case 'select':
				// Allow
				break;

			case 'edit':
				// Dynamically add the record to the user profile
				if (!\in_array(Input::get('id'), $root))
				{
					/** @var Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface $objSessionBag */
					$objSessionBag = $objSession->getBag('contao_backend');

					$arrNew = $objSessionBag->get('new_records');

					if (\is_array($arrNew['tl_recommendation_archive']) && \in_array(Input::get('id'), $arrNew['tl_recommendation_archive']))
					{
						// Add the permissions on group level
						if ($this->User->inherit != 'custom')
						{
							$objGroup = $this->Database->execute("SELECT id, recommendations, recommendationp FROM tl_user_group WHERE id IN(" . implode(',', array_map('\intval', $this->User->groups)) . ")");

							while ($objGroup->next())
							{
								$arrRecommendationp = StringUtil::deserialize($objGroup->recommendationp);

								if (\is_array($arrRecommendationp) && \in_array('create', $arrRecommendationp))
								{
									$arrRecommendation = StringUtil::deserialize($objGroup->recommendations, true);
									$arrRecommendation[] = Input::get('id');

									$this->Database->prepare("UPDATE tl_user_group SET recommendations=? WHERE id=?")
												   ->execute(serialize($arrRecommendation), $objGroup->id);
								}
							}
						}

						// Add the permissions on user level
						if ($this->User->inherit != 'group')
						{
							$objUser = $this->Database->prepare("SELECT recommendations, recommendationp FROM tl_user WHERE id=?")
													   ->limit(1)
													   ->execute($this->User->id);

							$arrRecommendationp = StringUtil::deserialize($objUser->recommendationp);

							if (\is_array($arrRecommendationp) && \in_array('create', $arrRecommendationp))
							{
								$arrRecommendation = StringUtil::deserialize($objUser->recommendations, true);
								$arrRecommendation[] = Input::get('id');

								$this->Database->prepare("UPDATE tl_user SET recommendations=? WHERE id=?")
											   ->execute(serialize($arrRecommendation), $this->User->id);
							}
						}

						// Add the new element to the user object
						$root[] = Input::get('id');
						$this->User->recommendations = $root;
					}
				}
				// No break;

			case 'copy':
			case 'delete':
			case 'show':
				if (!\in_array(Input::get('id'), $root) || (Input::get('act') == 'delete' && !$this->User->hasAccess('delete', 'recommendationp')))
				{
					throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' recommendation archive ID ' . Input::get('id') . '.');
				}
				break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
				$session = $objSession->all();
				if (Input::get('act') == 'deleteAll' && !$this->User->hasAccess('delete', 'recommendationp'))
				{
					$session['CURRENT']['IDS'] = array();
				}
				else
				{
					$session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $root);
				}
				$objSession->replace($session);
				break;

			default:
				if (\strlen(Input::get('act')))
				{
					throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' recommendation archives.');
				}
				break;
		}
	}

	/**
	 * Return the edit header button
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
	public function editHeader($row, $href, $label, $title, $icon, $attributes)
	{
		return $this->User->canEditFieldsOf('tl_recommendation_archive') ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)).' ';
	}

	/**
	 * Return the copy archive button
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
	public function copyArchive($row, $href, $label, $title, $icon, $attributes)
	{
		return $this->User->hasAccess('create', 'recommendationp') ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)).' ';
	}

	/**
	 * Return the delete archive button
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
	public function deleteArchive($row, $href, $label, $title, $icon, $attributes)
	{
		return $this->User->hasAccess('delete', 'recommendationp') ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)).' ';
	}
}
