<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 2019-01-07
 * Time: 12:51
 */

namespace UniteCMS\CoreBundle\Expression;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use UniteCMS\CoreBundle\Entity\ContentType;

/**
 * Provides expression language functions based on the doctrine entity manager.
 */
class UniteExpressionLanguageDoctrineContentProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var ContentType $contentType
     */
    private $contentType;

    public function __construct(EntityManager $entityManager, ContentType $contentType)
    {
        $this->entityManager = $entityManager;
        $this->contentType = $contentType;
    }

    /**
     * Returns true, if the value is unique for the given (nested) json path.
     * @param $value
     * @param $path
     * @return bool
     */
    private function contentDataUnique($value, $path) {
        $query = $this->entityManager->createQueryBuilder()
            ->select('count(c.id)')
            ->where('c.contentType = :contentType')
            ->andWhere('JSON_EXTRACT(c.data, :identifier) = :value')
            ->from('UniteCMSCoreBundle:Content', 'c')
            ->setParameters(
                [
                    ':contentType' => $this->contentType,
                    ':identifier' => '$.'.$path,
                    ':value' => $value,
                ]
            )
            ->getQuery();

        try {
            return $query->getSingleScalarResult() == 0;
        } catch (NonUniqueResultException $e) {
            return false;
        }
    }

    /**
     * @return ExpressionFunction[] An array of Function instances
     */
    public function getFunctions()
    {
        return [

            // Returns true, if the value at the given data_path is unique in this content type.
            new ExpressionFunction('content_unique', function ($str) {}, function ($arguments, $value, $path) {

                if (!is_string($value) && !is_bool($value) && !is_numeric($value)) {
                    return false;
                }

                if (!is_string($path)) {
                    return false;
                }

                return $this->contentDataUnique($value, $path);
            }),
        ];
    }
}