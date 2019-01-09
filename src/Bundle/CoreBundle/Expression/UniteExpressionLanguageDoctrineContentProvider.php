<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 2019-01-07
 * Time: 12:51
 */

namespace UniteCMS\CoreBundle\Expression;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Orx;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use UniteCMS\CoreBundle\Entity\ContentType;

/**
 * Provides expression language functions based on the doctrine entity manager.
 */
class UniteExpressionLanguageDoctrineContentProvider implements ExpressionFunctionProviderInterface
{
    // To prevent a infinity loop, we limit the nested uniquify calls to 8
    const MAXIMUM_NESTING_UNIQUIFY_TEST_RUNS = 8;

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
    private function contentDataUnique($value, $path) : bool {
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
        } catch (NoResultException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function contentDataUniquify($value, $path, $depth = 0) :string {

        // We only allow MAXIMUM_NESTING_UNIQUIFY_TEST_RUNS nested calls to prevent an infinity loop.
        if($depth >= static::MAXIMUM_NESTING_UNIQUIFY_TEST_RUNS) {
            return $value;
        }

        // First check if the value is already unique.
        if($this->contentDataUnique($value, $path)) {
            return $value;
        }

        // If value is not unique, get the last existing value without suffix
        $parameters = [
            ':contentType' => $this->contentType,
            ':identifier' => '$.'.$path,
        ];
        $slugWhere = new Orx();
        for($i = 0; $i < 10; $i++) {
            $slugWhere->add('JSON_UNQUOTE(JSON_EXTRACT(c.data, :identifier)) LIKE :value'.$i);
            $parameters[':value'.$i] = $value.'-%'.$i;
        }

        $lastExistingSuffixedEntry = null;
        $getLastExistingSuffixedEntry = $this->entityManager->createQueryBuilder()
            ->select('JSON_EXTRACT(c.data, :identifier)')
            ->where('c.contentType = :contentType')
            ->andWhere($slugWhere)
            ->from('UniteCMSCoreBundle:Content', 'c')
            ->orderBy('JSON_EXTRACT(c.data, :identifier)', 'DESC')
            ->setMaxResults(1)
            ->setParameters($parameters)
            ->getQuery();

        try {
            $lastExistingSuffixedEntry = $getLastExistingSuffixedEntry->getSingleScalarResult();
        } catch (NoResultException $e) {

            // No results where found means exactly:
            // There IS an entry with value == $value
            // But there IS no entry with value == $value-[0-9]*
            // Because of this, we just can add -1.
            return $value.'-1';

        } catch (\Exception $e) {

            // If an error occurred, just return the value.
            return $value;
        }

        // When we come to this point, we have an existing entry with the same prefix and a numeric suffix.
        $parts = explode('-', $lastExistingSuffixedEntry);
        $lastCount = (int)array_pop($parts);

        // Just return the next numeric value
        return $this->contentDataUniquify($value.'-'.($lastCount+1), $path, $depth+1);
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

            // Returns an uniquified representation of the given string by adding increasing suffixes.
            new ExpressionFunction('content_uniquify', function ($str) {}, function ($arguments, $value, $path) {

                if (!is_string($value)) {
                    return $value;
                }

                if (!is_string($path)) {
                    return $value;
                }

                return $this->contentDataUniquify($value, $path);
            }),
        ];
    }
}