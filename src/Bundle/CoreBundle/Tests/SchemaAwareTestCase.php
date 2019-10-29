<?php


namespace UniteCMS\CoreBundle\Tests;

use Exception;
use GraphQL\Error\Error;
use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Utils\BuildSchema;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\GraphQL\SchemaManager;

class SchemaAwareTestCase extends KernelTestCase
{
    public function setUp() {
        static::bootKernel();
        static::$container->get('security.token_storage')->setToken(new AnonymousToken('', ''));
    }

    /**
     * @param string $schema
     * @param bool $catch
     *
     * @return \GraphQL\Language\AST\DocumentNode
     * @throws \GraphQL\Error\Error
     * @throws \GraphQL\Error\SyntaxError
     */
    protected function buildSchema(string $schema = '', bool $catch = false) : DocumentNode {

        $schemaManager = static::$container->get(SchemaManager::class);
        $domainManager = static::$container->get(DomainManager::class);
        $domain = $domainManager->current();

        $domainManager->setCurrentDomain(new Domain(
            'test',
            $domain->getContentManager(),
            $domain->getUserManager(),
            array_merge($domain->getSchema(), [$schema])
        ));

        if($catch) {
            try {
                return $schemaManager->buildCacheableSchema();
            } catch (Exception $e) {
                return null;
            }
        } else {
            return $schemaManager->buildCacheableSchema();
        }
    }

    /**
     * @param string $schema
     * @param bool $catch
     */
    protected function assertValidSchema(string $schema = '', bool $catch = false) : bool {
        BuildSchema::build($this->buildSchema($schema, $catch))->assertValid();
        return true;
    }

    protected function modifyArray($expected, $result, &$subs = []) {

        if(!is_array($expected) || !is_array($result)) {
            return $result;
        }

        foreach($result as $key => $value) {
            if(!empty($expected[$key]) && !empty($value)) {
                if(is_string($expected[$key])) {
                    if(preg_match('/{([a-z0-9A-Z_-]+)}/', $expected[$key])) {
                        $this->assertNotNull($value);
                        $subs[] = $value;
                        $result[$key] = $expected[$key];
                        continue;
                    }
                }
            }
            if(!empty($expected[$key])) {
                $result[$key] = $this->modifyArray($expected[$key], $value, $subs);
            }
        }

        return $result;
    }

    /**
     * @param array $expected
     * @param string $query
     * @param array $args
     * @param bool $expectData
     *
     * @return array
     */
    protected function assertGraphQL(array $expected, string $query, array $args = [], $expectData = true) : array {

        $result = null;

        try {
            $result = static::$container->get(SchemaManager::class)->execute($query, $args);
        } catch (SyntaxError $e) {
            $this->fail(sprintf('Could not build GraphQL schema: %s', $e->getMessage()));
            return [];
        } catch (Error $e) {
            $this->fail(sprintf('Could not build GraphQL schema: %s', $e->getMessage()));
            return [];
        }

        $result = $result->toArray(true);
        $subs = [];

        if($expectData) {

            if(empty($result['data'])) {
                $this->fail(sprintf('GraphQL result does not contain data, but the following errors: %s', json_encode($result['errors'])));
                return [];
            }

            if(!empty($result['errors'])) {
                $this->fail(sprintf('GraphQL result contain the following errors: %s', json_encode($result['errors'])));
                return [];
            }

            $this->assertEquals($expected, $this->modifyArray($expected, $result['data'], $subs));
        } else {

            if(empty($result['errors'])) {
                $this->fail('GraphQL result does not contain errors.');
                return [];
            }

            $result['errors'] = array_map(function($error){
                return [
                    'message' => $error['message'],
                    'path' => $error['path'] ?? null,
                    'extensions' => $error['extensions'],
                ];
            }, $result['errors']);

            $this->assertEquals($expected, $this->modifyArray($expected, $result['errors'], $subs));
        }

        return $subs;
    }
}
