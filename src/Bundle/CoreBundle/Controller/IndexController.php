<?php

namespace UniteCMS\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Security\Voter\OrganizationVoter;

class IndexController extends Controller
{

    /**
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\OrganizationVoter::LIST'), 'UniteCMS\\CoreBundle\\Entity\\Organization')")
     * @return Response
     */
    public function indexAction()
    {
        // Platform admins are allowed to view all organizations.
        if ($this->isGranted(User::ROLE_PLATFORM_ADMIN)) {
            $organizations = $this->getDoctrine()->getRepository('UniteCMSCoreBundle:Organization')->findAll();
        } else {
            $organizations = $this->getUser()->getOrganizations()->map(
                function (OrganizationMember $member) {
                    return $member->getOrganization();
                }
            );
        }

        $allowedOrganizations = [];
        foreach ($organizations as $organization) {
            if ($this->isGranted(OrganizationVoter::VIEW, $organization)) {
                $allowedOrganizations[] = $organization;
            }
        }

        // If only one organization was found on the system, we can redirect to it.
        if (count($allowedOrganizations) == 1) {
            return $this->redirectToRoute(
                'unitecms_core_domain_index',
                ['organization' => $allowedOrganizations[0]->getIdentifier()]
            );
        }

        // Otherwise redirect to the organization overview page.
        else {
            return $this->redirectToRoute('unitecms_core_organization_index');
        }
    }
}
