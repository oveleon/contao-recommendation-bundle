<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

Contao\System::loadLanguageFile('tl_recommendation');
Contao\System::loadLanguageFile('tl_recommendation_notification');
Contao\System::loadLanguageFile('tl_recommendation_list');

// Add a palette selector
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'recommendation_activate';

// Add palettes to tl_module
$GLOBALS['TL_DCA']['tl_module']['palettes']['recommendationlist']    = '{title_legend},name,headline,type;{config_legend},recommendation_archives,recommendation_readerModule,recommendation_minRating,recommendation_featured,recommendation_order,numberOfItems,perPage;{template_legend:hide},recommendation_metaFields,recommendation_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['recommendationreader']  = '{title_legend},name,headline,type;{config_legend},recommendation_archives;{template_legend:hide},recommendation_metaFields,recommendation_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['recommendationform']    = '{title_legend},name,headline,type;{config_legend},recommendation_archive,recommendation_optionalFormFields,recommendation_customFieldLabel,recommendation_notify,recommendation_moderate,recommendation_disableCaptcha;{privacy_legend},recommendation_privacyText;{redirect_legend:hide},jumpTo;{email_legend:hide},recommendation_activate;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['recommendation_activate'] = 'recommendation_activateJumpTo,recommendation_activateText';

// Add fields to tl_module
$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_archives'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_archives'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'options_callback'        => array('tl_module_recommendation', 'getRecommendationArchives'),
    'eval'                    => array('multiple'=>true, 'mandatory'=>true),
    'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_archive'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_archive'],
    'exclude'                 => true,
    'inputType'               => 'select',
    'options_callback'        => array('tl_module_recommendation', 'getRecommendationArchives'),
    'eval'                    => array('mandatory'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50 clr'),
    'sql'                     => "int(10) unsigned NOT NULL default 0"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_featured'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_featured'],
    'default'                 => 'all_items',
    'exclude'                 => true,
    'inputType'               => 'select',
    'options'                 => array('all_items', 'featured', 'unfeatured', 'featured_first'),
    'reference'               => &$GLOBALS['TL_LANG']['tl_recommendation_list'],
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "varchar(16) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_readerModule'] = array
(
    'exclude'                 => true,
    'inputType'               => 'select',
    'options_callback'        => array('tl_module_recommendation', 'getReaderModules'),
    'reference'               => &$GLOBALS['TL_LANG']['tl_module'],
    'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w50'),
    'sql'                     => "int(10) unsigned NOT NULL default 0"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_metaFields'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_metaFields'],
    'default'                 => array('date', 'author'),
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'options'                 => array('recommendation_image', 'date', 'author', 'rating', 'location', 'recommendation_customField'),
    'reference'               => &$GLOBALS['TL_LANG']['MSC'],
    'eval'                    => array('multiple'=>true),
    'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_order'] = array
(
    'exclude'                 => true,
    'inputType'               => 'select',
    'options'        		  => array('order_date_asc', 'order_date_desc', 'order_random', 'order_rating_desc'),
    'reference'               => &$GLOBALS['TL_LANG']['tl_recommendation_list'],
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "varchar(32) NOT NULL default 'order_date_desc'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_minRating'] = array
(
    'exclude'                 => true,
    'inputType'               => 'select',
    'options'                 => array(1=> 'minOne', 2=>'minTwo', 3=>'minThree', 4=>'minFour', 5=>'minFive'),
    'reference'               => &$GLOBALS['TL_LANG']['tl_recommendation_list'],
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "char(1) NOT NULL default '1'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_optionalFormFields'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_optionalFormFields'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'options'                 => array('title', 'location', 'email', 'recommendation_customField'),
    'reference'               => &$GLOBALS['TL_LANG']['tl_recommendation'],
    'eval'                    => array('multiple'=>true, 'tl_class'=>'w50 clr'),
    'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_customFieldLabel'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_customFieldLabel'],
    'exclude'                 => true,
    'inputType'               => 'text',
    'eval'                    => array('maxlength'=>64, 'tl_class'=>'w50 clr'),
    'sql'                     => "varchar(64) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_notify'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_notify'],
    'default'				  => true,
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50 clr'),
    'sql'                     => "char(1) NOT NULL default '1'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_moderate'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_moderate'],
    'default'				  => true,
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "char(1) NOT NULL default '1'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_disableCaptcha'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_disableCaptcha'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_disableCaptcha'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_disableCaptcha'],
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50'),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_privacyText'] = array
(
    'exclude'                 => true,
    'inputType'               => 'textarea',
    'eval'                    => array('style'=>'height:100px', 'allowHtml'=>true),
    'sql'                     => "text NULL"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_activate'] = array
(
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('submitOnChange'=>true),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_activateJumpTo'] = array
(
    'exclude'                 => true,
    'inputType'               => 'pageTree',
    'foreignKey'              => 'tl_page.title',
    'eval'                    => array('fieldType'=>'radio'),
    'sql'                     => "int(10) unsigned NOT NULL default 0",
    'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_activateText'] = array
(
    'exclude'                 => true,
    'inputType'               => 'textarea',
    'eval'                    => array('style'=>'height:120px', 'decodeEntities'=>true, 'alwaysSave'=>true),
    'load_callback' => array
    (
        array('tl_module_recommendation', 'getRecommendationActivationDefault')
    ),
    'sql'                     => "text NULL"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['recommendation_template'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['recommendation_template'],
    'exclude'                 => true,
    'inputType'               => 'select',
    'options_callback'        => static fn () => Controller::getTemplateGroup('recommendation_'),
    'eval'                    => array('includeBlankOption' => true, 'chosen' => true, 'tl_class'=>'w50'),
    'sql'                     => "varchar(64) NOT NULL default ''"
);


/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Fabian Ekert <fabian@oveleon.de>
 */
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
     *
     * @return array
     */
    public function getRecommendationArchives()
    {
        if (!$this->User->isAdmin && !is_array($this->User->recommendations))
        {
            return array();
        }

        $arrArchives = array();
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
     *
     * @return array
     */
    public function getReaderModules()
    {
        $arrModules = array();
        $objModules = $this->Database->execute("SELECT m.id, m.name, t.name AS theme FROM tl_module m LEFT JOIN tl_theme t ON m.pid=t.id WHERE m.type='recommendationreader' ORDER BY t.name, m.name");

        while ($objModules->next())
        {
            $arrModules[$objModules->theme][$objModules->id] = $objModules->name . ' (ID ' . $objModules->id . ')';
        }

        return $arrModules;
    }

    /**
     * Load the default recommendation activation text
     *
     * @param mixed $varValue
     *
     * @return mixed
     */
    public function getRecommendationActivationDefault($varValue)
    {
        if (trim($varValue) === '')
        {
            $varValue = (is_array($GLOBALS['TL_LANG']['tl_recommendation_notification']['email_activation'] ?? null) ? $GLOBALS['TL_LANG']['tl_recommendation_notification']['email_activation'][1] : ($GLOBALS['TL_LANG']['tl_recommendation_notification']['email_activation'] ?? null));
        }

        return $varValue;
    }
}
