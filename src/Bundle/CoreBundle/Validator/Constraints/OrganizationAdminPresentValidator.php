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

        // if this member is not an admin, ...
        if($thisMember->getSingleRole() !== Organization::ROLE_ADMINISTRATOR) {

            // ... and if this member was also not an admin before, be can just skip any checks.
            $originalEntity = $this->entityManager->getUnitOfWork()->getOriginalEntityData($thisMember);
            if(!in_array(Organization::ROLE_ADMINISTRATOR, $originalEntity['roles'])) {
                return;
            }
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