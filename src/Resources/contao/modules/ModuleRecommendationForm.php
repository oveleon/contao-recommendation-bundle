<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoRecommendationBundle;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\OptIn\OptIn;
use Contao\Email;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Idna;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;
use Patchwork\Utf8;
use Psr\Log\LogLevel;

/**
 * Front end module "recommendation form".
 *
 * @property integer 	$id
 * @property string		$headline
 * @property string		$name
 * @property integer    $recommendation_archive
 * @property array		$recommendation_optionalFormFields
 * @property boolean	$recommendation_notify
 * @property boolean	$recommendation_moderate
 * @property boolean	$recommendation_disableCaptcha
 * @property string		$recommendation_privacyText
 * @property integer	$jumpTo
 * @property boolean	$recommendation_activate
 * @property string		$recommendation_activateText
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
		System::loadLanguageFile('tl_recommendation');
		System::loadLanguageFile('tl_recommendation_notification');

		// Verify recommendation
		if (strncmp(Input::get('token'), 'rec-', 4) === 0)
		{
			$this->verifyRecommendation();

			return;
		}

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
                'eval'      => array('optional'=>true, 'maxlength'=>255),
            ),
			'location' => array
			(
				'name'      => 'location',
				'label'     => $GLOBALS['TL_LANG']['tl_recommendation']['location'],
				'inputType' => 'text',
				'eval'      => array('optional'=>true, 'maxlength'=>128),
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
				'eval'      => array('optional'=>true, 'maxlength'=>255, 'rgxp'=>'email', 'decodeEntities'=>true),
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

        // Set e-mail as mandatory and non-optional if comments should be validated via activation mail
		if ($this->recommendation_activate)
		{
			$arrFields['email']['eval']['optional'] = false;
			$arrFields['email']['eval']['mandatory'] = true;
		}

		// Set an opt-in checkbox when privacy text is given
		if ($this->recommendation_privacyText)
		{
			$arrFields['privacy'] = array
			(
				'name'      => 'privacy',
				'inputType' => 'checkbox',
				'options'   => array(1=>$this->recommendation_privacyText),
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
            if(($arrField['eval']['optional'] ?? null) && !in_array($fieldName, $arrOptionalFormFields))
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

            $arrField['eval']['required'] = $arrField['eval']['mandatory'] ?? null;

            /** @var Widget $objWidget */
            $objWidget = new $strClass($strClass::getAttributesFromDca($arrField, $arrField['name'], $arrField['value'] ?? null));

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
			$arrData = array
            (
                'tstamp'    => $time,
                'pid'       => $this->recommendation_archive,
                'title'     => $arrWidgets['title']->value ?: '',
                'alias'		=> $arrWidgets['title']->value ? StringUtil::generateAlias($arrWidgets['title']->value) : '',
                'author'    => $arrWidgets['author']->value,
                'email'		=> $arrWidgets['email']->value ?: '',
                'location'  => $arrWidgets['location']->value ?: '',
                'date'      => $time,
                'time'      => $time,
                'text'      => $this->convertLineFeeds($strText),
                'rating'    => $arrWidgets['rating']->value,
                'published' => $this->recommendation_moderate ? '' : 1
            );

            // Store the recommendation
            $objRecommendation = new RecommendationModel();
            $objRecommendation->setRow($arrData)->save();

			// Notify system administrator via e-mail
			if ($this->recommendation_notify && !$this->recommendation_activate)
			{
				$this->sendNotificationMail($objRecommendation);
			}

			// Send verification e-mail
			if ($this->recommendation_activate)
			{
				// Unverify recommendation
				$objRecommendation->verified = 0;
				$objRecommendation->save();

				$this->sendVerificationMail($arrData, $objRecommendation->id);
			}

			// Check whether there is a jumpTo page
			if (($objJumpTo = $this->objModel->getRelated('jumpTo')) instanceof PageModel)
			{
				$this->jumpToOrReload($objJumpTo->row());
			}
			else
			{
				$session->getFlashBag()->set('recommendation_added', $this->getFlashBagMessage());
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

	/**
	 * Get flashbag message
	 *
	 * @return string
	 */
	protected function getFlashBagMessage()
	{
		// Confirmation e-mail
		if ($this->recommendation_activate)
		{
			return $GLOBALS['TL_LANG']['tl_recommendation_notification']['confirm'];
		}
		// Needs approval
		else if ($this->recommendation_moderate)
		{
			return $GLOBALS['TL_LANG']['tl_recommendation_notification']['approval'];
		}
		else
		{
			return $GLOBALS['TL_LANG']['tl_recommendation_notification']['added'];
		}
	}

	/**
	 * Sends a notification to the administrator
	 *
	 * @param object	$objRecommendation
	 */
	protected function sendNotificationMail($objRecommendation)
	{
		$strText = $objRecommendation->text;

		$objEmail = new Email();
		$objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'];
		$objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'];
		$objEmail->subject = sprintf($GLOBALS['TL_LANG']['tl_recommendation_notification']['email_subject'], Idna::decode(Environment::get('host')));

		// Convert the recommendation to plain text
		$strText = strip_tags($strText);
		$strText = StringUtil::decodeEntities($strText);
		$strText = str_replace(array('[&]', '[lt]', '[gt]'), array('&', '<', '>'), $strText);

		// Add the recommendation details
		$objEmail->text = sprintf(
			$GLOBALS['TL_LANG']['tl_recommendation_notification']['email_message'],
			$objRecommendation->author,
			RecommendationArchiveModel::findById($this->recommendation_archive)->title,
			$objRecommendation->rating,
			$strText,
			Idna::decode(Environment::get('base')) . 'contao?do=recommendation&table=tl_recommendation&id=' . $objRecommendation->id . '&act=edit'
		);

		// Add a moderation hint to the e-mail
		if ($this->recommendation_moderate)
		{
			$objEmail->text .= "\n" . $GLOBALS['TL_LANG']['tl_recommendation_notification']['email_moderated'] . "\n";
		}

		// Send E-mail
		$objEmail->sendTo($GLOBALS['TL_ADMIN_EMAIL']);
	}

	/**
	 * Send the verification mail
	 *
	 * @param array		$arrData
	 * @param integer	$id
	 */
	protected function sendVerificationMail($arrData, $id)
	{
		/** @var OptIn $optIn */
		$optIn = System::getContainer()->get('contao.opt-in');
		$optInToken = $optIn->create('rec', $arrData['email'], array('tl_recommendation'=>array($id)));

		// Prepare the simple token data
		$arrTokenData = $arrData;
		$arrTokenData['token'] = $optInToken->getIdentifier();
		$arrTokenData['domain'] = Idna::decode(Environment::get('host'));
		$arrTokenData['link'] = Idna::decode(Environment::get('base')) . Environment::get('request') . ((strpos(Environment::get('request'), '?') !== false) ? '&' : '?') . 'token=' . $optInToken->getIdentifier();
		$arrTokenData['channel'] = '';

		// Send the token
		$optInToken->send(sprintf($GLOBALS['TL_LANG']['tl_recommendation_notification']['email_activation'][0], Idna::decode(Environment::get('host'))), StringUtil::parseSimpleTokens($this->recommendation_activateText, $arrTokenData));
	}

	/**
	 * Verifies the recommendation
	 */
	protected function verifyRecommendation()
	{
		$this->Template = new FrontendTemplate('mod_message');

		/** @var OptIn $optin */
		$optIn = System::getContainer()->get('contao.opt-in');

		// Find an unconfirmed token
		if ((!$optInToken = $optIn->find(Input::get('token'))) || !$optInToken->isValid() || \count($arrRelated = $optInToken->getRelatedRecords()) < 1 || key($arrRelated) != 'tl_recommendation' || \count($arrIds = current($arrRelated)) < 1)
		{
			$this->Template->type = 'error';
			$this->Template->message = $GLOBALS['TL_LANG']['MSC']['invalidToken'];

			return;
		}

		if ($optInToken->isConfirmed())
		{
			$this->Template->type = 'error';
			$this->Template->message = $GLOBALS['TL_LANG']['MSC']['tokenConfirmed'];

			return;
		}

		$arrRecommendations = array();

		foreach ($arrIds as $intId)
		{
			if (!$objRecommendation = RecommendationModel::findByPk($intId))
			{
				$this->Template->type = 'error';
				$this->Template->message = $GLOBALS['TL_LANG']['MSC']['invalidToken'];

				return;
			}

			if ($optInToken->getEmail() != $objRecommendation->email)
			{
				$this->Template->type = 'error';
				$this->Template->message = $GLOBALS['TL_LANG']['MSC']['tokenEmailMismatch'];

				return;
			}

			$arrRecommendations[] = $objRecommendation;
		}

		$objRecommendation->verified = 1;
		$objRecommendation->save();


		// Notify system administrator via e-mail
		if ($this->recommendation_notify)
		{
			$this->sendNotificationMail($objRecommendation);
		}

		$optInToken->confirm();

		// Log activity
		$logger = System::getContainer()->get('monolog.logger.contao');
		$logger->log(LogLevel::INFO, 'Recommendation ID ' . $objRecommendation->id . ' (' . Idna::decodeEmail($objRecommendation->email) . ') has been verified', array('contao' => new ContaoContext(__METHOD__, TL_ACCESS)));

		// Redirect to the jumpTo page
		if (($objTarget = $this->objModel->getRelated('recommendation_activateJumpTo')) instanceof PageModel)
		{
			/** @var PageModel $objTarget */
			$this->redirect($objTarget->getFrontendUrl());
		}
		
		// Confirm activation
		$this->Template->type = 'confirm';

		$this->Template->message = $GLOBALS['TL_LANG']['tl_recommendation_notification']['verified'];

		if ($this->recommendation_moderate)
		{
			$this->Template->message = $GLOBALS['TL_LANG']['tl_recommendation_notification']['approval'];
		}
	}
}
