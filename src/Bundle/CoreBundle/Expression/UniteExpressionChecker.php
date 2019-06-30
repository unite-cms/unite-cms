<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 17.05.18
 * Time: 16:18
 */

namespace UniteCMS\CoreBundle\Expression;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\FieldableContent;

class UniteExpressionChecker
{
    /**
     * @var UniteExpressionLanguage  $expressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var array $variables
     */
    private $variables;

    public function __construct()
    {
        $this->variables = [];
        $this->expressionLanguage = new UniteExpressionLanguage();
    }

    /**
     * Clear all available variables.
     * @return UniteExpressionChecker
     */
    public function clearVariables() {
        $this->variables = [];
        return $this;
    }

    /**
     * Make a domain member data available in the expression.
     *
     * @param DomainMember $domainMember
     * @param string $variableName
     *
     * @return UniteExpressionChecker
     */
    public function registerDomainMember(DomainMember $domainMember = null, $variableName = 'member') {
        if(!$domainMember) {
            $this->variables[$variableName] = null;
        } else {
            $this->variables[$variableName] = (object)[
                'data' => json_decode(json_encode($domainMember->getData())),
                'type' => empty($domainMember->getDomainMemberType()) ? null : $domainMember->getDomainMemberType()->getIdentifier(),
                '_name' => (string)$domainMember,
                'accessor' => (object)[
                    'name' => (string)$domainMember->getAccessor(),
                    'id' => (string)$domainMember->getAccessor()->getId(),
                    'type' => $domainMember->getAccessor()::getType(),
                ],
            ];
        }

        return $this;
    }

    /**
     * Make a domain member data available in the expression.
     *
     * @param FieldableContent $fieldableContent
     * @param string $variableName
     *
     * @return UniteExpressionChecker
     */
    public function registerFieldableContent(FieldableContent $fieldableContent = null, $variableName = 'content') {
        if(!$fieldableContent) {
            $this->variables[$variableName] = null;
        } else {
            $data = [
                'locale' => $fieldableContent->getLocale(),
                'data' => json_decode(json_encode($fieldableContent->getData())),
            ];

            if($fieldableContent instanceof Content || $fieldableContent instanceof DomainMember) {
                $data['id'] = (string)$fieldableContent->getId();
                $data['_name'] = (string)$fieldableContent;
            }

            if($fieldableContent instanceof DomainMember) {
                $data['accessor'] = (object)[
                    'name' => (string)$fieldableContent->getAccessor(),
                    'id' => (string)$fieldableContent->getAccessor()->getId(),
                    'type' => $fieldableContent->getAccessor()::getType(),
                ];
            }

            $this->variables[$variableName] = (object)$data;
        }

        return $this;
    }

    /**
     * Make a variable available in the expression under the given name.
     *
     * @param $variableName
     * @param $variable
     *
     * @return UniteExpressionChecker
     */
    public function registerVariable($variableName, $variable = null) {
        $this->variables[$variableName] = $variable;
        return $this;
    }

    /**
     * Register content functions that are performed using the given content type and doctrine entity manager.
     *
     * @param EntityManagerInterface $entityManager
     * @param ContentType $contentType
     * @return $this
     */
    public function registerDoctrineContentFunctionsProvider(EntityManagerInterface $entityManager, ContentType $contentType) {
        $this->expressionLanguage->registerProvider(new UniteExpressionLanguageDoctrineContentProvider($entityManager, $contentType));
        return $this;
    }

    /**
     * Returns the expression evaluation result, All previously registered variables are available for evaluation.
     *
     * @param string $expression
     * @return string
     */
    public function evaluateToString(string $expression) : string {
        try {
            return (string) $this->expressionLanguage->evaluate($expression, $this->variables);
        }
        catch (\Exception $exception) {
            return ''; // Silently cache all exceptions. The expression can be defined by the user and we don't want to show him_her an error page.
        }
    }

    /**
     * Returns the expression evaluation result, All previously registered variables are available for evaluation.
     *
     * @param string $expression
     * @return bool
     */
    public function evaluateToBool(string $expression) : bool {
        try {
            return (bool) $this->expressionLanguage->evaluate($expression, $this->variables);
        }
        catch (\Exception $exception) {
            return ''; // Silently cache all exceptions. The expression can be defined by the user and we don't want to show him_her an error page.
        }
    }

    /**
     * Returns true, if the expression is valid (syntax and allowed variables).
     *
     * @param string $expression
     * @return bool
     */
    public function
    validate(string $expression) : bool {
        try {
            $this->expressionLanguage->parse($expression, array_keys($this->variables));
        }

        catch (SyntaxError $error) {
            return false;
        }

        return true;
    }
}