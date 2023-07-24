<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoRecommendationBundle;

use Contao\BackendTemplate;
use Contao\CoreBundle\Exception\InternalServerErrorException;
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
use Oveleon\ContaoRecommendationBundle\Model\RecommendationArchiveModel;
use Oveleon\ContaoRecommendationBundle\Model\RecommendationModel;

/**
 * Front end module "recommendation form".
 *
 * @property integer 	$id
 * @property string		$headline
 * @property string		$name
 * @property integer    $recommendation_archive
 * @property array		$recommendation_optionalFormFields
 * @property string     $recommendation_customFieldLabel
 * @property boolean	$recommendation_notify
 * @property boolean	$recommendation_moderate
 * @property boolean	$recommendation_disableCaptcha
 * @property string		$recommendation_privacyText
 * @property integer	$jumpTo
 * @property boolean	$recommendation_activate
 * @property string		$recommendation_activateText
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
    public function generate(): string
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
        {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['recommendationform'][0] . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

            return $objTemplate->parse();
        }

        $this->recommendation_archives = $this->sortOutProtected([$this->recommendation_archive]);

        if (empty($this->recommendation_archives) || !\is_array($this->recommendation_archives))
        {
            throw new InternalServerErrorException('The recommendation form ID ' . $this->id . ' has no archive specified.');
        }

        return parent::generate();
    }

    /**
     * Generate the module
     */
    protected function compile(): void
    {
        System::loadLanguageFile('tl_recommendation');
        System::loadLanguageFile('tl_recommendation_notification');

        // Verify recommendation
        if (strncmp(Input::get('token'), 'rec-', 4) === 0)
        {
            $this->verifyRecommendation();

            return;
        }

        // Get archive record
        $archive = RecommendationArchiveModel::findMultipleByIds($this->recommendation_archives);
        $archive = $archive[0] ?? null;

        // Form fields
        $arrFields = [
            'author' => [
                'name'      => 'author',
                'label'     => $GLOBALS['TL_LANG']['tl_recommendation']['author'],
                'inputType' => 'text',
                'eval'      => ['mandatory'=>true, 'maxlength'=>128]
            ],
            'rating' => [
                'name'      => 'rating',
                'label'     => $GLOBALS['TL_LANG']['tl_recommendation']['rating'],
                'inputType' => 'select',
                'options'   => [5,4,3,2,1],
                'eval'      => ['mandatory'=>true]
            ],
            'title' => [
                'name'      => 'title',
                'label'     => $GLOBALS['TL_LANG']['tl_recommendation']['title'],
                'inputType' => 'text',
                'eval'      => ['optional'=>true, 'maxlength'=>255],
            ],
            'customField' => [
                'name'      => 'customField',
                'label'     => $this->recommendation_customFieldLabel ?: $GLOBALS['TL_LANG']['tl_recommendation']['customFieldLabel'],
                'inputType' => 'text',
                'eval'      => ['optional'=>true, 'maxlength'=>255],
            ],
            'location' => [
                'name'      => 'location',
                'label'     => $GLOBALS['TL_LANG']['tl_recommendation']['location'],
                'inputType' => 'text',
                'eval'      => ['optional'=>true, 'maxlength'=>128],
            ],
            'text' => [
                'name'      => 'text',
                'label'     => $GLOBALS['TL_LANG']['tl_recommendation']['text'],
                'inputType' => 'textarea',
                'eval'      => ['mandatory'=>true, 'rows'=>4, 'cols'=>40]
            ],
            'email' => [
                'name'      => 'email',
                'label'     => $GLOBALS['TL_LANG']['tl_recommendation']['email'],
                'inputType' => 'text',
                'eval'      => ['optional'=>true, 'maxlength'=>255, 'rgxp'=>'email', 'decodeEntities'=>true],
            ],
        ];

        // Add scope for auto alias archives
        if($archive && $archive->useAutoItem)
        {
            $arrFields['scope'] = [
                'name'      => 'scope',
                'inputType' => 'hidden',
                'value'     => Input::get('auto_item')
            ];
        }

        // Captcha
        if (!$this->recommendation_disableCaptcha)
        {
            $arrFields['captcha'] = [
                'name'      => 'captcha',
                'label'     => $GLOBALS['TL_LANG']['MSC']['securityQuestion'],
                'inputType' => 'captcha',
                'eval'      => ['mandatory'=>true]
            ];
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
            $arrFields['privacy'] = [
                'name'      => 'privacy',
                'inputType' => 'checkbox',
                'options'   => [1=>$this->recommendation_privacyText],
                'eval'      => ['mandatory'=>true]
            ];
        }

        $doNotSubmit = false;
        $arrWidgets  = [];
        $strFormId   = 'recommendation_' . $this->id;

        // Optional recommendation form fields
        $arrOptionalFormFields = StringUtil::deserialize($this->recommendation_optionalFormFields, true);

        // Initialize the widgets
        foreach ($arrFields as $fieldName => $arrField)
        {
            // Check for optional form fields
            if (($arrField['eval']['optional'] ?? null) && !\in_array($fieldName, $arrOptionalFormFields))
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

        $this->Template->fields       = $arrWidgets;
        $this->Template->submit       = $GLOBALS['TL_LANG']['tl_recommendation']['recommendation_submit'];
        $this->Template->formId       = $strFormId;
        $this->Template->hasError     = $doNotSubmit;
        $this->Template->requestToken = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

        $objSession = System::getContainer()->get('request_stack')->getSession();

        // Do not index or cache the page with the confirmation message
        if ($objSession->isStarted())
        {
            $flashBag = $objSession->getFlashBag();

            if ($flashBag->has('recommendation_added'))
            {
                $this->Template->confirm = $flashBag->get('recommendation_added')[0];
            }
        }

        // Store the recommendation
        if (!$doNotSubmit && Input::post('FORM_SUBMIT') == $strFormId)
        {
            $time = time();

            // Do not parse any tags in the recommendation
            $strText = StringUtil::specialchars(trim($arrWidgets['text']->value));
            $strText = str_replace(['&amp;', '&lt;', '&gt;'], ['[&]', '[lt]', '[gt]'], $strText);

            // Remove multiple line feeds
            $strText = preg_replace('@\n\n+@', "\n\n", $strText);

            // Prevent cross-site request forgeries
            $strText = preg_replace('/(href|src|on[a-z]+)="[^"]*(contao\/main\.php|typolight\/main\.php|javascript|vbscri?pt|script|alert|document|cookie|window)[^"]*"+/i', '$1="#"', $strText);

            // Prepare the record
            $arrData = [
                'tstamp' => $time,
                'pid' => $this->recommendation_archive,
                'title' => $arrWidgets['title']->value ?: '',
                'alias' => $arrWidgets['title']->value ? StringUtil::generateAlias($arrWidgets['title']->value) : '',
                'author' => $arrWidgets['author']->value,
                'email' => $arrWidgets['email']->value ?: '',
                'location' => $arrWidgets['location']->value ?: '',
                'customField' => $arrWidgets['customField']->value ?: '',
                'date' => $time,
                'time' => $time,
                'text' => $this->convertLineFeeds($strText),
                'rating' => $arrWidgets['rating']->value,
                'scope' => $arrWidgets['scope']->value ?? '',
                'published' => $this->recommendation_moderate ? '' : 1
            ];

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
                $objSession->getFlashBag()->set('recommendation_added', $this->getFlashBagMessage());
            }

            $this->reload();
        }
    }

    /**
     * Convert line feeds to <br /> tags
     */
    public function convertLineFeeds(string $strText): string
    {
        $strText = preg_replace('/\r?\n/', '<br>', $strText);

        // Use paragraphs to generate new lines
        if (strncmp('<p>', $strText, 3) !== 0)
        {
            $strText = '<p>' . $strText . '</p>';
        }

        $arrReplace = [
            '@<br>\s?<br>\s?@' => "</p>\n<p>", // Convert two linebreaks into a new paragraph
            '@\s?<br></p>@'    => '</p>',      // Remove BR tags before closing P tags
            '@<p><div@'        => '<div',      // Do not nest DIVs inside paragraphs
            '@</div></p>@'     => '</div>'     // Do not nest DIVs inside paragraphs
        ];

        return preg_replace(array_keys($arrReplace), array_values($arrReplace), $strText);
    }

    /**
     * Get flashbag message
     */
    protected function getFlashBagMessage(): string
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
     */
    protected function sendNotificationMail(RecommendationModel $objRecommendation): void
    {
        $strText = $objRecommendation->text;

        $objEmail = new Email();
        $objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'] ?? null;
        $objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'] ?? null;
        $objEmail->subject = sprintf($GLOBALS['TL_LANG']['tl_recommendation_notification']['email_subject'], Idna::decode(Environment::get('host')));

        // Convert the recommendation to plain text
        $strText = strip_tags($strText);
        $strText = StringUtil::decodeEntities($strText);
        $strText = str_replace(['[&]', '[lt]', '[gt]'], ['&', '<', '>'], $strText);

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
     */
    protected function sendVerificationMail(array $arrData, int $id): void
    {
        $container = System::getContainer();

        /** @var OptIn $optIn */
        $optIn = $container->get('contao.opt_in');
        $optInToken = $optIn->create('rec', $arrData['email'], ['tl_recommendation'=> [$id]]);

        // Prepare the simple token data
        $arrTokenData = $arrData;
        $arrTokenData['token']   = $optInToken->getIdentifier();
        $arrTokenData['domain']  = Idna::decode(Environment::get('host'));
        $arrTokenData['link']    = Idna::decode(Environment::get('base')) . Environment::get('request') . ((str_contains(Environment::get('request'), '?')) ? '&' : '?') . 'token=' . $optInToken->getIdentifier();
        $arrTokenData['channel'] = '';

        // Send the token
        $optInToken->send(sprintf($GLOBALS['TL_LANG']['tl_recommendation_notification']['email_activation'][0], Idna::decode(Environment::get('host'))), $container->get('contao.string.simple_token_parser')->parse($this->recommendation_activateText, $arrTokenData));
    }

    /**
     * Verifies the recommendation
     */
    protected function verifyRecommendation(): void
    {
        $this->Template = new FrontendTemplate('mod_message');

        /** @var OptIn $optin */
        $optIn = System::getContainer()->get('contao.opt_in');

        // Find an unconfirmed token
        if (
            (!$optInToken = $optIn->find(Input::get('token'))) ||
            !$optInToken->isValid() ||
            \count($arrRelated = $optInToken->getRelatedRecords()) < 1 ||
            key($arrRelated) != 'tl_recommendation' ||
            \count($arrIds = current($arrRelated)) < 1
        )
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

        $arrRecommendations = [];

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
        $logger?->info('Recommendation ID ' . $objRecommendation->id . ' (' . Idna::decodeEmail($objRecommendation->email) . ') has been verified');

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

class_alias(ModuleRecommendationForm::class, 'ModuleRecommendationForm');
