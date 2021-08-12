<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoRecommendationBundle;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Email;
use Contao\Environment;
use Contao\Idna;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;
use Patchwork\Utf8;

/**
 * Front end module "recommendation form".
 *
 * @property integer    $recommendation_archive
 * @property array		$recommendation_optionalFormFields
 * @property boolean	$recommendation_notify
 * @property boolean	$recommendation_moderate
 * @property boolean	$recommendation_disableCaptcha
 * @property integer	$jumpTo
 * @property boolean	$recommendation_activate
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
			'location' => array
			(
				'name'      => 'location',
				'label'     => $GLOBALS['TL_LANG']['tl_recommendation']['location'],
				'inputType' => 'text',
				'eval'      => array('maxlength'=>128),
				'optional'  => true
			),
            'text' => array
            (
                'name'      => 'text',
                'label'     => $GLOBALS['TL_LANG']['tl_recommendation']['text'],
                'inputType' => 'textarea',
                'eval'      => array('mandatory'=>true, 'rows'=>4, 'cols'=>40)
            ),
			'email' => array
			(
				'name'      => 'email',
				'label'     => $GLOBALS['TL_LANG']['tl_recommendation']['email'],
				'inputType' => 'text',
				'eval'      => array('maxlength'=>255, 'rgxp'=>'email', 'decodeEntities'=>true),
				'optional'  => true
			),
        );

        // Captcha
        if (!$this->recommendation_disableCaptcha == true)
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
        $strFormId = 'recommendation_' . $this->id;

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
		$this->Template->submit = $GLOBALS['TL_LANG']['tl_recommendation']['recommendation_submit'];
        $this->Template->formId = $strFormId;
        $this->Template->hasError = $doNotSubmit;

		$session = System::getContainer()->get('session');

		// Do not index or cache the page with the confirmation message
		if ($session->isStarted())
		{
			$flashBag = $session->getFlashBag();

			if ($flashBag->has('recommendation_added'))
			{
				/** @var PageModel $objPage */
				global $objPage;

				$objPage->noSearch = 1;
				$objPage->cache = 0;

				$this->Template->confirm = $flashBag->get('recommendation_added')[0];
			}
		}

        // Store the recommendation
        if (!$doNotSubmit && Input::post('FORM_SUBMIT') == $strFormId)
        {
            $time = time();

			// Do not parse any tags in the recommendation
			$strText = StringUtil::specialchars(trim($arrWidgets['text']->value));
			$strText = str_replace(array('&amp;', '&lt;', '&gt;'), array('[&]', '[lt]', '[gt]'), $strText);

			// Remove multiple line feeds
			$strText = preg_replace('@\n\n+@', "\n\n", $strText);

			// Prevent cross-site request forgeries
			$strText = preg_replace('/(href|src|on[a-z]+)="[^"]*(contao\/main\.php|typolight\/main\.php|javascript|vbscri?pt|script|alert|document|cookie|window)[^"]*"+/i', '$1="#"', $strText);


			// Prepare the record
            $arrSet = array
            (
                'tstamp'    => $time,
                'pid'       => $this->recommendation_archive,
                'title'     => $arrWidgets['title']->value ?: '',
                'author'    => $arrWidgets['author']->value,
                'location'  => $arrWidgets['location']->value ?: '',
                'date'      => $time,
                'time'      => $time,
                'text'      => $this->convertLineFeeds($strText),
                'rating'    => $arrWidgets['rating']->value,
                'published' => $this->recommendation_moderate ? '' : 1
            );

            // Store the recommendation
            $objRecommendation = new RecommendationModel();
            $objRecommendation->setRow($arrSet)->save();

			// Notify system administrator via e-mail
			if ($this->recommendation_notify) {

				$objEmail = new Email();
				$objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'];
				$objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'];
				$objEmail->subject = sprintf($GLOBALS['TL_LANG']['tl_recommendation']['recommendation_email_subject'], Idna::decode(Environment::get('host')));

				// Convert the recommendation to plain text
				$strText = strip_tags($strText);
				$strText = StringUtil::decodeEntities($strText);
				$strText = str_replace(array('[&]', '[lt]', '[gt]'), array('&', '<', '>'), $strText);

				// Add the recommendation details
				$objEmail->text = sprintf(
					$GLOBALS['TL_LANG']['tl_recommendation']['recommendation_email_message'],
					$arrSet['author'],
					RecommendationArchiveModel::findById($this->recommendation_archive)->title,
					$arrSet['rating'],
					$strText,
					Idna::decode(Environment::get('base')) . 'contao?do=recommendation&table=tl_recommendation&id=' . $objRecommendation->id . '&act=edit'
				);

				// Add a moderation hint to the e-mail
				if ($this->recommendation_moderate) {
					$objEmail->text .= "\n" . $GLOBALS['TL_LANG']['tl_recommendation']['recommendation_moderated'] . "\n";
				}

				// Send E-mail
				$objEmail->sendTo($GLOBALS['TL_ADMIN_EMAIL']);
			}

			// Check whether there is a jumpTo page
			if (($objJumpTo = $this->objModel->getRelated('jumpTo')) instanceof PageModel)
			{
				$this->jumpToOrReload($objJumpTo->row());
			}
			// Pending for approval
			else if ($this->recommendation_moderate)
			{
				$session->getFlashBag()->set('recommendation_added', $GLOBALS['TL_LANG']['tl_recommendation']['recommendation_confirm']);
			}
			else
			{
				$session->getFlashBag()->set('recommendation_added', $GLOBALS['TL_LANG']['tl_recommendation']['recommendation_added']);
			}

            $this->reload();
        }
    }

	/**
	 * Convert line feeds to <br /> tags
	 *
	 * @param string $strRecommendation
	 *
	 * @return string
	 */
	public function convertLineFeeds($strText)
	{
		$strText = nl2br_pre($strText);

		// Use paragraphs to generate new lines
		if (strncmp('<p>', $strText, 3) !== 0)
		{
			$strText = '<p>' . $strText . '</p>';
		}

		$arrReplace = array
		(
			'@<br>\s?<br>\s?@' => "</p>\n<p>", // Convert two linebreaks into a new paragraph
			'@\s?<br></p>@'    => '</p>',      // Remove BR tags before closing P tags
			'@<p><div@'        => '<div',      // Do not nest DIVs inside paragraphs
			'@</div></p>@'     => '</div>'     // Do not nest DIVs inside paragraphs
		);

		return preg_replace(array_keys($arrReplace), array_values($arrReplace), $strText);
	}
}
