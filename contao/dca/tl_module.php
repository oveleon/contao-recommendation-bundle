<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

use Contao\System;
use Contao\Controller;

System::loadLanguageFile('tl_recommendation');
System::loadLanguageFile('tl_recommendation_notification');
System::loadLanguageFile('tl_recommendation_list');

// Add a palette selector
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'recommendation_activate';

// Add palettes to tl_module
$GLOBALS['TL_DCA']['tl_module']['palettes']['recommendationlist']    = '{title_legend},name,headline,type;{config_legend},recommendation_archives,recommendation_readerModule,recommendation_minRating,recommendation_featured,recommendation_order,numberOfItems,perPage;{template_legend:hide},recommendation_metaFields,recommendation_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['recommendationreader']  = '{title_legend},name,headline,type;{config_legend},recommendation_archives;{template_legend:hide},recommendation_metaFields,recommendation_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['recommendationform']    = '{title_legend},name,headline,type;{config_legend},recommendation_archive,recommendation_optionalFormFields,recommendation_customFieldLabel,recommendation_notify,recommendation_moderate,recommendation_disableCaptcha;{privacy_legend},recommendation_privacyText;{redirect_legend:hide},jumpTo;{email_legend:hide},recommendation_activate;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['recommendation_activate'] = 'recommendation_activateJumpTo,recommendation_activateText';

// Add fields to tl_module
$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_archives'] = [
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'options_callback'        => ['tl_module_recommendation', 'getRecommendationArchives'],
    'eval'                    => ['multiple'=>true, 'mandatory'=>true],
    'sql'                     => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_archive'] = [
    'exclude'                 => true,
    'inputType'               => 'select',
    'options_callback'        => ['tl_module_recommendation', 'getRecommendationArchives'],
    'eval'                    => ['mandatory'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50 clr'],
    'sql'                     => "int(10) unsigned NOT NULL default 0"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_featured'] = [
    'default'                 => 'all_items',
    'exclude'                 => true,
    'inputType'               => 'select',
    'options'                 => ['all_items', 'featured', 'unfeatured', 'featured_first'],
    'reference'               => &$GLOBALS['TL_LANG']['tl_recommendation_list'],
    'eval'                    => ['tl_class'=>'w50'],
    'sql'                     => "varchar(16) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_readerModule'] = [
    'exclude'                 => true,
    'inputType'               => 'select',
    'options_callback'        => ['tl_module_recommendation', 'getReaderModules'],
    'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
    'eval'                    => ['includeBlankOption'=>true, 'tl_class'=>'w50'],
    'sql'                     => "int(10) unsigned NOT NULL default 0"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_metaFields'] = [
    'default'                 => ['date', 'author'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'options'                 => ['image', 'date', 'author', 'rating', 'location', 'customField'],
    'reference'               => &$GLOBALS['TL_LANG']['tl_recommendation'],
    'eval'                    => ['multiple'=>true],
    'sql'                     => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_order'] = [
    'exclude'                 => true,
    'inputType'               => 'select',
    'options'        		  => ['order_date_asc', 'order_date_desc', 'order_random', 'order_rating_desc'],
    'reference'               => &$GLOBALS['TL_LANG']['tl_recommendation_list'],
    'eval'                    => ['tl_class'=>'w50'],
    'sql'                     => "varchar(32) NOT NULL default 'order_date_desc'"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_minRating'] = [
    'exclude'                 => true,
    'inputType'               => 'select',
    'options'                 => [1=> 'minOne', 2=>'minTwo', 3=>'minThree', 4=>'minFour', 5=>'minFive'],
    'reference'               => &$GLOBALS['TL_LANG']['tl_recommendation_list'],
    'eval'                    => ['tl_class'=>'w50'],
    'sql'                     => "char(1) NOT NULL default '1'"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_optionalFormFields'] = [
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'options'                 => ['title', 'location', 'email', 'customField'],
    'reference'               => &$GLOBALS['TL_LANG']['tl_recommendation'],
    'eval'                    => ['multiple'=>true, 'tl_class'=>'w50 clr'],
    'sql'                     => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_customFieldLabel'] = [
    'exclude'                 => true,
    'inputType'               => 'text',
    'eval'                    => ['maxlength'=>64, 'tl_class'=>'w50 clr'],
    'sql'                     => "varchar(64) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_notify'] = [
    'default'				  => true,
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => ['tl_class'=>'w50 clr'],
    'sql'                     => "char(1) NOT NULL default '1'"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_moderate'] = [
    'default'				  => true,
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => ['tl_class'=>'w50'],
    'sql'                     => "char(1) NOT NULL default '1'"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_disableCaptcha'] = [
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => ['tl_class'=>'w50'],
    'sql'                     => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_disableCaptcha'] = [
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => ['tl_class'=>'w50'],
    'sql'                     => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_privacyText'] = [
    'exclude'                 => true,
    'inputType'               => 'textarea',
    'eval'                    => ['style'=>'height:100px', 'allowHtml'=>true],
    'sql'                     => "text NULL"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_activate'] = [
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => ['submitOnChange'=>true],
    'sql'                     => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_activateJumpTo'] = [
    'exclude'                 => true,
    'inputType'               => 'pageTree',
    'foreignKey'              => 'tl_page.title',
    'eval'                    => ['fieldType'=>'radio'],
    'sql'                     => "int(10) unsigned NOT NULL default 0",
    'relation'                => ['type'=>'hasOne', 'load'=>'lazy']
];

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_activateText'] = [
    'exclude'                 => true,
    'inputType'               => 'textarea',
    'eval'                    => ['style'=>'height:120px', 'decodeEntities'=>true, 'alwaysSave'=>true],
    'load_callback' =>
        [
        ['tl_module_recommendation', 'getRecommendationActivationDefault']
        ],
    'sql'                     => "text NULL"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_template'] = [
    'exclude'                 => true,
    'inputType'               => 'select',
    'options_callback'        => static fn () => Controller::getTemplateGroup('recommendation_'),
    'eval'                    => ['includeBlankOption' => true, 'chosen' => true, 'tl_class'=>'w50'],
    'sql'                     => "varchar(64) NOT NULL default ''"
];

class tl_module_recommendation extends Contao\Backend
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
     * Get all recommendation archives and return them as array
     */
    public function getRecommendationArchives(): array
    {
        if (!$this->User->isAdmin && !is_array($this->User->recommendations))
        {
            return [];
        }

        $arrArchives = [];
        $objArchives = $this->Database->execute("SELECT id, title FROM tl_recommendation_archive ORDER BY title");

        while ($objArchives->next())
        {
            if ($this->User->hasAccess($objArchives->id, 'recommendations'))
            {
                $arrArchives[$objArchives->id] = $objArchives->title;
            }
        }

        return $arrArchives;
    }

    /**
     * Get all recommendation reader modules and return them as array
     */
    public function getReaderModules(): array
    {
        $arrModules = [];
        $objModules = $this->Database->execute("SELECT m.id, m.name, t.name AS theme FROM tl_module m LEFT JOIN tl_theme t ON m.pid=t.id WHERE m.type='recommendationreader' ORDER BY t.name, m.name");

        while ($objModules->next())
        {
            $arrModules[$objModules->theme][$objModules->id] = $objModules->name . ' (ID ' . $objModules->id . ')';
        }

        return $arrModules;
    }

    /**
     * Load the default recommendation activation text
     */
    public function getRecommendationActivationDefault(mixed $varValue): mixed
    {
        if (trim($varValue) === '')
        {
            $varValue = (is_array($GLOBALS['TL_LANG']['tl_recommendation_notification']['email_activation'] ?? null) ? $GLOBALS['TL_LANG']['tl_recommendation_notification']['email_activation'][1] : ($GLOBALS['TL_LANG']['tl_recommendation_notification']['email_activation'] ?? null));
        }

        return $varValue;
    }
}
