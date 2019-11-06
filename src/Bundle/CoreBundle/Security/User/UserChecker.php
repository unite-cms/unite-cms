<?php

namespace UniteCMS\CoreBundle\Security\User;

use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\Exception\ProviderNotFoundException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
use UniteCMS\CoreBundle\GraphQL\SchemaManager;
use UniteCMS\CoreBundle\GraphQL\Util;

class UserChecker implements UserCheckerInterface
{
    /**
     * @var SchemaManager $schemaManager
     */
    protected $schemaManager;

    /**
     * @var SaveExpressionLanguage $expressionLanguage
     */
    protected $expressionLanguage;

    public function __construct(SchemaManager $schemaManager, SaveExpressionLanguage $expressionLanguage)
    {
        $this->schemaManager = $schemaManager;
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * {@inheritDoc}
     */
    public function checkPreAuth(UserInterface $user)
    {
        if(!$user instanceof \UniteCMS\CoreBundle\Security\User\UserInterface) {
            return;
        }

        $userType = $user->getType();
        $minimalSchema = $this->schemaManager->buildBaseSchema();

        if(!in_array($userType, array_keys($minimalSchema->getTypeMap()))) {
            throw new ProviderNotFoundException(sprintf('The GraphQL type "%s" was not found.', $userType));
        }

        $userType = $minimalSchema->getType($userType);
        $directives = Util::getDirectives($userType->astNode);
        $authDirective = null;

        foreach ($directives as $directive) {
            if($directive['name'] === 'passwordAuthenticator') {
                $authDirective = $directive;
            }
        }

        if(empty($authDirective)) {
            return;
        }

        if(!empty($authDirective['args']['enabledIf'])) {
            if(!(bool)$this->expressionLanguage->evaluate($authDirective['args']['enabledIf'], [
                'user' => $user,
            ])) {
                throw new DisabledException('User account is disabled.');
            }
        }

        if(!empty($authDirective['args']['lockedIf'])) {
            if((bool)$this->expressionLanguage->evaluate($authDirective['args']['lockedIf'], [
                'user' => $user,
            ])) {
                throw new LockedException('User account is locked.');
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function checkPostAuth(UserInterface $user) {}
}
