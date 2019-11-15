<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver\Field;


use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Content\FieldDataMapper;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Exception\ConstraintViolationsException;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Log\LoggerInterface;
use UniteCMS\CoreBundle\Security\User\UserInterface;

abstract class AbstractEmailConfirmationResolver implements FieldResolverInterface
{
    const TOKEN_KEY = null;
    const TYPE = 'UniteMutation';
    const REQUEST_FIELD = null;
    const CONFIRM_FIELD = null;

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var ValidatorInterface $validator
     */
    protected $validator;

    /**
     * @var FieldDataMapper $fieldDataMapper
     */
    protected $fieldDataMapper;

    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    protected $fieldTypeManager;

    /**
     * @var JWTEncoderInterface $JWTEncoder
     */
    protected $JWTEncoder;

    public function __construct(DomainManager $domainManager, ValidatorInterface $validator, FieldDataMapper $fieldDataMapper, JWTEncoderInterface $JWTEncoder)
    {
        $this->domainManager = $domainManager;
        $this->fieldDataMapper = $fieldDataMapper;
        $this->validator = $validator;
        $this->JWTEncoder = $JWTEncoder;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === static::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve($value, $args, $context, ResolveInfo $info)
    {
        switch ($info->fieldName) {
            case static::REQUEST_FIELD: return $this->resolveRequest($args);
            case static::CONFIRM_FIELD: return $this->resolveConfirm($args);
            default: return null;
        }
    }

    /**
     * @param UserInterface $user
     * @return array|null
     *
     * @throws JWTDecodeFailureException
     */
    protected function getTokenPayload(UserInterface $user) : ?array {
        if(!$user->getToken(static::TOKEN_KEY)) {
            return null;
        }

        return $this->JWTEncoder->decode($user->getToken(static::TOKEN_KEY));
    }

    /**
     * @param UserInterface $user
     * @return bool
     *
     * @throws JWTDecodeFailureException
     */
    protected function isTokenEmptyOrExpired(UserInterface $user) : bool {

        if(!($payload = $this->getTokenPayload($user))) {
            return true;
        }

        if($payload['exp'] < time()) {
            return true;
        }

        return false;
    }

    /**
     * @param UserInterface $user
     * @param string $token
     *
     * @return bool
     * @throws JWTDecodeFailureException
     */
    protected function isTokenValid(UserInterface $user, string $token) : bool {

        if($this->isTokenEmptyOrExpired($user)) {
            return false;
        }

        $storedToken = $user->getToken(static::TOKEN_KEY);
        $payload = $this->JWTEncoder->decode($storedToken);

        if($token !== $storedToken || $payload['username'] !== $user->getUsername()) {
            $this->domainManager->current()->log(LoggerInterface::WARNING, sprintf('User tried to confirm an invalid token for %s.', static::CONFIRM_FIELD));
            return false;
        }

        return true;
    }

    /**
     * @param UserInterface $user
     * @param array $payload
     * @throws JWTEncodeFailureException
     */
    protected function generateToken(UserInterface $user, $payload = []) : void {
        $user->setToken(static::TOKEN_KEY,
            $this->JWTEncoder->encode(array_merge([
                'username' => $user->getUsername(),
            ], $payload))
        );
    }

    /**
     * @param UserInterface $user
     * @param string $field
     * @param mixed|null $value
     * @param bool $resetValue
     */
    protected function tryToUpdate(UserInterface $user, string $field, $value = null, bool $resetValue = false) {

        $domain = $this->domainManager->current();
        $input = [$field => $value];
        $oldValue = $user->getData();
        $domain->getUserManager()->update($domain, $user, $this->fieldDataMapper->mapToFieldData($domain, $user, $input, null, true));
        $violations = $this->validator->validate($user);

        if($resetValue || count($violations) > 0) {
            $domain->getUserManager()->update($domain, $user, $oldValue);
        }

        if(count($violations) > 0) {
            throw new ConstraintViolationsException($violations);
        }
    }

    /**
     * @param $args
     * @return bool
     */
    abstract protected function resolveRequest($args) : bool;

    /**
     * @param $args
     * @return bool
     */
    abstract protected function resolveConfirm($args) : bool;
}
