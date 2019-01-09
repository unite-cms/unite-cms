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
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\FieldableContent;

/**
 * @deprecated 0.8 This checker will be replaced by UniteExpressionChecker which have other variables: content, member
 * "locale" and "data" variable of this checker will become "content.locale" und "content.X" in version 0.8
 */
class ValidationExpressionChecker
{
    /**
     * @var UniteExpressionLanguage  $expressionLanguage
     */
    private $expressionLanguage;

    public function __construct()
    {
        $this->expressionLanguage = new UniteExpressionLanguage();
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
     * Returns the expression evaluation result, makes fieldable content available in the expression.
     *
     * @param string $expression
     * @param FieldableContent|null $fieldableContent
     * @return bool
     */
    public function evaluate(string $expression, FieldableContent $fieldableContent) : bool {

        $variables = [
            'locale' => $fieldableContent->getLocale(),
            'data' => json_decode(json_encode($fieldableContent->getData())),
        ];

        $variables['content'] = (object)$variables;

        try {
            return (bool) $this->expressionLanguage->evaluate($expression, $variables);
        }

        // Silently cache all exceptions. The expression can be defined by the user and we don't want to show him_her an error page.
        catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Returns true, if the expression is valid (syntax and allowed variables).
     *
     * @param string $expression
     * @return bool
     */
    public function validate(string $expression) : bool {
        $variables = ['locale', 'data', 'content'];

        try {
            $this->expressionLanguage->parse($expression, $variables);
        }

        catch (SyntaxError $error) {
            return false;
        }

        return true;
    }
}