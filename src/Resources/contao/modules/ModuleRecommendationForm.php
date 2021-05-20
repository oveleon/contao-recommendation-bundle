<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoRecommendationBundle;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Input;
use Contao\StringUtil;
use Contao\Widget;
use Patchwork\Utf8;

/**
 * Front end module "recommendation form".
 *
 * @property integer    $recommendation_archive
 *
 * @author Sebastian Zoglowek <sebastian@oveleon.de>
 */
class ModuleRecommendationForm extends ModuleRecommendation
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_recommendationform';

    /**
     * Display a wildcard in the back end
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE')
        {
            /** @var \BackendTemplate|object $objTemplate */
            $objTemplate = new \BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['recommendationform'][0]) . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        // Set the item from the auto_item parameter
        /*if (!isset($_GET['items']) && \Config::get('useAutoItem') && isset($_GET['auto_item']))
        {
            \Input::setGet('items', \Input::get('auto_item'));
        }*/

        //$this->recommendation_archive = $this->sortOutProtected($this->recommendation_archive);

        // Do not index or cache the page if no recommendation item has been specified
        //if (!\Input::get('items') || empty($this->recommendation_archive) || !\is_array($this->recommendation_archive))
        //{
        //    /** @var \PageModel $objPage */
        //    global $objPage;
        //
        //    $objPage->noSearch = 1;
        //    $objPage->cache = 0;
        //
        //    return '';
        //}

        return parent::generate();
    }

    /**
     * Generate the module
     *
     */
    protected function compile()
    {

        // Form fields
        $arrFields = array
        (
            'author' => array
            (
                'name'      => 'author',
                'label'     => $GLOBALS['TL_LANG']['tl_recommendation']['author'],
                'inputType' => 'text',
                'eval'      => array('mandatory'=>true, 'maxlength'=>128)
            ),
            'rating' => array
            (
                'name'      => 'rating',
                'label'     => $GLOBALS['TL_LANG']['tl_recommendation']['rating'],
                'inputType' => 'select',
                'options'   => array(5,4,3,2,1),
                'eval'      => array('mandatory'=>true)
            ),
            'title' => array
            (
                'name'      => 'title',
                'label'     => $GLOBALS['TL_LANG']['tl_recommendation']['title'],
                'inputType' => 'text',
                'eval'      => array('maxlength'=>255),
                'optional'  => true
            ),
            'text' => array
            (
                'name'      => 'text',
                'label'     => $GLOBALS['TL_LANG']['tl_recommendation']['text'],
                'inputType' => 'textarea',
                'eval'      => array('mandatory'=>true, 'rows'=>4, 'cols'=>40)
            ),
            'location' => array
            (
                'name'      => 'location',
                'label'     => $GLOBALS['TL_LANG']['tl_recommendation']['location'],
                'inputType' => 'text',
                'eval'      => array('maxlength'=>128),
                'optional'  => true
            ),
            'image' => array
            (
                'name'      => 'image',
                'label'     => $GLOBALS['TL_LANG']['tl_recommendation']['image'],
                'inputType' => 'text',
                'eval'      => array('maxlength'=>64),
                'optional'  => true
            )
        );

        // Captcha
        if (!$this->rec_disableCaptcha == true)
        {
            $arrFields['captcha'] = array
            (
                'name'      => 'captcha',
                'label'     => $GLOBALS['TL_LANG']['MSC']['securityQuestion'],
                'inputType' => 'captcha',
                'eval'      => array('mandatory'=>true)
            );
        }

        $doNotSubmit = false;
        $arrWidgets = array();
        $strFormId = 'rec_' . $this->id;

        // Optional recommendation form fields
        $arrOptionalFormFields = StringUtil::deserialize($this->recommendation_optionalFormFields, true);

        // Initialize the widgets
        foreach ($arrFields as $fieldName => $arrField)
        {
            // Check for optional form fields
            if(isset($arrField['optional']) && !in_array($fieldName, $arrOptionalFormFields))
            {
                continue;
            }

            /** @var Widget $strClass */
            $strClass = $GLOBALS['TL_FFL'][$arrField['inputType']];

            // Continue if the class is not defined
            if (!class_exists($strClass))
            {
                continue;
            }

            $arrField['eval']['required'] = $arrField['eval']['mandatory'];

            /** @var Widget $objWidget */
            $objWidget = new $strClass($strClass::getAttributesFromDca($arrField, $arrField['name'], $arrField['value']));

            // Append the parent ID to prevent duplicate IDs (see #1493)
            $objWidget->id .= '_' . $this->id;

            // Validate the widget
            if (Input::post('FORM_SUBMIT') == $strFormId)
            {
                $objWidget->validate();

                if ($objWidget->hasErrors())
                {
                    $doNotSubmit = true;
                }
            }

            $arrWidgets[$arrField['name']] = $objWidget;
        }

        $this->Template->fields = $arrWidgets;
        $this->Template->formId = $strFormId;
        $this->Template->hasError = $doNotSubmit;

        // Store the recommendation

        if (!$doNotSubmit && Input::post('FORM_SUBMIT') == $strFormId)
        {

            $time = time();

            // Prepare the record
            // ToDo: Research image implementation with Fabian/Doi
            $arrSet = array
            (
                'tstamp'    => $time,
                'pid'       => $this->recommendation_archive,
                'title'     => $arrWidgets['title']->value ?: '',
                'author'    => $arrWidgets['author']->value,
                'location'  => $arrWidgets['location']->value ?: '',
                'date'      => $time,
                'time'      => $time,
                'text'      => $arrWidgets['text']->value,
                //'imageUrl'  => $arrWidgets['image']->value ?: '',
                'rating'    => $arrWidgets['rating']->value,
                'published' => $this->rec_moderate ? '' : 1
            );

            // Store the recommendation
            $objRecommendation = new RecommendationModel();
            $objRecommendation->setRow($arrSet)->save();

            $this->reload();
        }
    }
}
