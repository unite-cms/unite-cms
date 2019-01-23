<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 2019-01-23
 * Time: 10:33
 */

namespace UniteCMS\CoreBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

interface ApiUserInterface extends UserInterface, \Serializable {}