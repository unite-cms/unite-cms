<?php

namespace UniteCMS\RecaptchaFieldBundle\Field\Types;

use Doctrine\ORM\EntityRepository;
use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestMethod;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;
use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class RecaptchaFieldType extends FieldType
{
    const TYPE                      = "recaptcha";
    const FORM_TYPE                 = HiddenType::class;
    const SETTINGS                  = ['secret_key', 'expected_hostname', 'expected_apk_package_name', 'expected_action', 'score_threshold', 'challenge_timeout'];
    const REQUIRED_SETTINGS         = ['secret_key'];

    /**
     * @var RequestStack $requestStack
     */
    protected $requestStack;

    /**
     * @var RequestMethod $recaptchaRequestMethod
     */
    protected $recaptchaRequestMethod;

    public function __construct(RequestStack $requestStack, RequestMethod $recaptchaRequestMethod = null)
    {
        $this->requestStack = $requestStack;
        $this->recaptchaRequestMethod = $recaptchaRequestMethod;
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {
        return null; // We only allow input, but no output for this field.
    }

    /**
     * {@inheritdoc}
     */
    function validateData(FieldableField $field, $data, ExecutionContextInterface $context) {

        // Recaptcha should only be validated for the API and not for the admin interface.
        if(substr($this->requestStack->getCurrentRequest()->getPathInfo(), -4) === '/api') {

            if(empty($data)) {
                $context
                    ->buildViolation('required')
                    ->atPath('[' . $field->getIdentifier() . ']')
                    ->addViolation();
                return;
            }

            $settings = $field->getSettings();
            $recaptcha = new ReCaptcha($settings->secret_key, $this->recaptchaRequestMethod);

            if(!empty($settings->expected_hostname)) {
                $recaptcha->setExpectedHostname($settings->expected_hostname);
            }

            if(!empty($settings->expected_apk_package_name)) {
                $recaptcha->setExpectedApkPackageName($settings->expected_apk_package_name);
            }

            if(!empty($settings->expected_action)) {
                $recaptcha->setExpectedAction($settings->expected_action);
            }

            if(!empty($settings->score_threshold)) {
                $recaptcha->setScoreThreshold($settings->score_threshold);
            }

            if(!empty($settings->challenge_timeout)) {
                $recaptcha->setChallengeTimeout($settings->challenge_timeout);
            }

            $recaptchaResponse = $recaptcha->verify($data, $this->requestStack->getCurrentRequest()->getClientIp());
            if(!$recaptchaResponse->isSuccess()) {

                if(empty($recaptchaResponse->getErrorCodes())) {
                    $context
                        ->buildViolation(ReCaptcha::E_UNKNOWN_ERROR)
                        ->atPath('[' . $field->getIdentifier() . ']')
                        ->addViolation();
                } else {
                    foreach($recaptchaResponse->getErrorCodes() as $errorCode) {
                        $context
                            ->buildViolation($errorCode)
                            ->atPath('[' . $field->getIdentifier() . ']')
                            ->addViolation();
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onCreate(FieldableField $field, FieldableContent $content, EntityRepository $repository, &$data) {
        if(isset($data[$field->getIdentifier()])) {
            unset($data[$field->getIdentifier()]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onUpdate(FieldableField $field, FieldableContent $content, EntityRepository $repository, $old_data, &$data) {
        if(isset($data[$field->getIdentifier()])) {
            unset($data[$field->getIdentifier()]);
        }
    }

    /**
     * {@inheritdoc}
     */
    function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {
        // Validate allowed and required settings.
        parent::validateSettings($settings, $context);

        // Only continue, if there are no violations yet.
        if($context->getViolations()->count() > 0) {
            return;
        }

        if (isset($settings->secret_key)) {
            $context->getViolations()->addAll(
                $context->getValidator()->validate($settings->secret_key, [
                    new Assert\Type(['message' => 'nostring_value', 'type' => 'string']),
                ])
            );
        }

        if (isset($settings->expected_hostname)) {
            $context->getViolations()->addAll(
                $context->getValidator()->validate($settings->expected_hostname, [
                    new Assert\Type(['message' => 'nostring_value', 'type' => 'string']),
                ])
            );
        }

        if (isset($settings->expected_apk_package_name)) {
            $context->getViolations()->addAll(
                $context->getValidator()->validate($settings->expected_apk_package_name, [
                    new Assert\Type(['message' => 'nostring_value', 'type' => 'string']),
                ])
            );
        }

        if (isset($settings->expected_action)) {
            $context->getViolations()->addAll(
                $context->getValidator()->validate($settings->expected_action, [
                    new Assert\Type(['message' => 'nostring_value', 'type' => 'string']),
                ])
            );
        }

        if (isset($settings->score_threshold)) {
            $context->getViolations()->addAll(
                $context->getValidator()->validate($settings->score_threshold, [
                    new Assert\Type(['type' => 'float']),
                    new Assert\Range(['min' => 0, 'max' => 1]),
                ])
            );
        }

        if (isset($settings->challenge_timeout)) {
            $context->getViolations()->addAll(
                $context->getValidator()->validate($settings->challenge_timeout, [
                    new Assert\Type(['type' => 'int']),
                ])
            );
        }
    }
}
