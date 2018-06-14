<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 25.05.18
 * Time: 12:11
 */

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use Symfony\Component\Form\Form;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;

class OrganizationAdminPresentValidator extends ConstraintValidator
{

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed $value The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        $thisMember = $this->context->getObject();

        if (!$thisMember instanceof OrganizationMember) {
            throw new InvalidArgumentException(
                'The OrganizationAdminPresentValidator constraint expects a UniteCMS\CoreBundle\Entity\OrganizationMember object.'
            );
        }

        // context root can also be a form. If so, we need to check the form data.
        $root = $this->context->getRoot() instanceof Form ? $this->context->getRoot()->getData() : $this->context->getRoot();

        // Only continue if the root of this validation chain is this object or the related user object. Without this
        // check, this validator thinks on delete of other objects (e.g. domain) that this org member will get deleted.
        if($root != $thisMember && $root != $thisMember->getUser()) {
            return;
        }

        // if this member is not an admin, ...
        if($thisMember->getSingleRole() !== Organization::ROLE_ADMINISTRATOR) {

            // ... and if this member was also not an admin before, be can just skip any checks.
            $originalEntity = $this->entityManager->getUnitOfWork()->getOriginalEntityData($thisMember);
            if(!in_array(Organization::ROLE_ADMINISTRATOR, $originalEntity['roles'])) {
                return;
            }
        }

        // If this is an update validation and this user is admin, we can skip any checks.
        if($thisMember->getSingleRole() === Organization::ROLE_ADMINISTRATOR && $this->context->getGroup() === 'UPDATE') {
            return;
        }

        if (!$value instanceof Organization) {
            throw new InvalidArgumentException(
                'The OrganizationAdminPresentValidator constraint expects a UniteCMS\CoreBundle\Entity\Organization value.'
            );
        }

        // Get all other admin members of this organization.
        $orgAdmins = $value->getMembers()->filter(
            function (OrganizationMember $member) use ($thisMember) {
                return ($member->getSingleRole() === Organization::ROLE_ADMINISTRATOR) && ($member->getId() !== $thisMember->getId());
            }
        );

        if($orgAdmins->count() === 0) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}