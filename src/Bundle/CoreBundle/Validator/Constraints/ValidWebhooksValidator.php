<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 02.08.18
 * Time: 12:06
 */

namespace UniteCMS\CoreBundle\Validator\Constraints;

use UniteCMS\CoreBundle\Security\WebhookExpressionChecker;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;


class ValidWebhooksValidator extends ConstraintValidator
{
    /**
     * @var WebhookExpressionChecker $webhookExpressionChecker
     */
    private $webhookExpressionChecker;

    const REQUIRED_OPTIONS =  ['url', 'fire'];
    const ALLOWED_OPTIONS = ['url', 'fire', 'check_ssl', 'secret_key'];

    public function __construct()
    {
        $this->webhookExpressionChecker = new WebhookExpressionChecker();
    }

    public function validate($value, Constraint $constraint)
    {
        # check for right array syntax
        if (isset($value[0]) && !is_array($value[0])) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }

        foreach ($value as $index => $hook) {

            # check if allowed options are set
            foreach (self::REQUIRED_OPTIONS as $required) {
                if (!in_array($required, array_keys($hook))) {
                    $this->context->buildViolation($constraint->requiredAttributeMissingMessage)->addViolation();

                }
            }

            # check for non allowed attributes
            foreach ($hook as $key => $attribute) {
                if (!in_array($key, self::ALLOWED_OPTIONS)) {
                    $this->context->buildViolation($constraint->invalidAttributeMessage)->addViolation();
                }
            }

            # validate if check_ssl is a boolean
            $check_ssl = $hook['check_ssl'];
            if (isset($hook['check_ssl']) && !is_bool($check_ssl)) {
                $this->context->buildViolation($constraint->invalidCheckSSLMessage)->addViolation();
            }

            # validate secret key
            if (isset($hook['secret_key']) && !strlen($hook['secret_key']) >= 8) {
                $this->context->buildViolation($constraint->invalidSecretKeyMessage)->addViolation();
            }

            # validate fire expression
            if (isset($hook['fire']) && !$this->webhookExpressionChecker->validate($hook['fire'])) {
                $this->context->buildViolation($constraint->invalidExpressionMessage)->addViolation();
            }

        }

    }
}