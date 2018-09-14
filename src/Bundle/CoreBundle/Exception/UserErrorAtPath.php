<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 14.09.18
 * Time: 10:25
 */

namespace UniteCMS\CoreBundle\Exception;

use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use GraphQL\Error\UserError;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * An user error at a specific path. Path will be shown to the user. Nodes will be set to null.
 */
class UserErrorAtPath extends UserError
{
    /**
     * @var array $path
     */
    private $path;

    /**
     * @var string $category
     */
    private $category;

    public function __construct(string $message = '', array $path = [], $category = 'user')
    {
        $this->path = $path;
        $this->category = $category;
        parent::__construct($message);
    }

    /**
     * @param FormError $error
     * @return UserErrorAtPath
     */
    static function createFromFormError(FormError $error) {
        $path = [];
        if($error->getCause() && $error->getCause() instanceof ConstraintViolationInterface) {
            $propertyPath = str_replace(']', '', $error->getCause()->getPropertyPath());
            $path = explode('[', $propertyPath);
        }

        return new UserErrorAtPath($error->getMessage(), $path, 'validation');
    }

    /**
     * Creates a new formatted error from a exception. If the previous exception is a UserErrorAtPath, path and nodes
     *w will be overridden.
     *
     * @param Error $error
     * @return array
     * @throws \Throwable
     */
    static function createFormattedErrorFromException(Error $error) {
        if($error->getPrevious() && $error->getPrevious() instanceof UserErrorAtPath) {
            $error->path = array_merge($error->path ?? [], $error->getPrevious()->getPath());
            $error->nodes = [];
        }
        return FormattedError::createFromException($error);
    }

    /**
     * @return array
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }
}