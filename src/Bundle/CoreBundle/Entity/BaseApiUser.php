<?php
namespace UniteCMS\CoreBundle\Entity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * This class can be extended and resolved to have api users in unite cms. If
 * this class is not extended and resolved, nothing will happen and no api user
 * entity will be available in unite cms.
 */
abstract class BaseApiUser extends DomainAccessor implements UserInterface, \Serializable {}
