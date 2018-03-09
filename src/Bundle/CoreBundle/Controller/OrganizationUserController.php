<?php

namespace UnitedCMS\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Entity\OrganizationMember;

class OrganizationUserController extends Controller
{
    /**
     * @Route("/")
     * @Method({"GET"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\OrganizationVoter::UPDATE'), organization)")
     *
     * @param Organization $organization
     * @return Response
     */
    public function indexAction(Organization $organization)
    {
        $users = $this->get('knp_paginator')->paginate($organization->getUsers());

        return $this->render(
            'UnitedCMSCoreBundle:Organization/User:index.html.twig',
            [
                'organization' => $organization,
                'users' => $users,
            ]
        );
    }

    /**
     * @Route("/update/{member}")
     * @Method({"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("member")
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\OrganizationVoter::UPDATE'), organization)")
     *
     * @param Organization $organization
     * @param OrganizationMember $member
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateAction(Organization $organization, OrganizationMember $member, Request $request)
    {
        $available_roles = [
            Organization::ROLE_USER => Organization::ROLE_USER,
            Organization::ROLE_ADMINISTRATOR => Organization::ROLE_ADMINISTRATOR,
        ];

        $form = $this->createFormBuilder($member)
            ->add('roles', ChoiceType::class, ['label' => 'Roles', 'multiple' => true, 'choices' => $available_roles])
            ->add('submit', SubmitType::class, ['label' => 'Update'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute(
                'unitedcms_core_organizationuser_index',
                [
                    'organization' => $organization->getIdentifier(),
                ]
            );
        }

        return $this->render(
            'UnitedCMSCoreBundle:Organization/User:update.html.twig',
            [
                'organization' => $organization,
                'form' => $form->createView(),
                'member' => $member,
            ]
        );
    }

    /**
     * @Route("/delete/{member}")
     * @Method({"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("member")
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\OrganizationVoter::UPDATE'), organization)")
     *
     * @param Organization $organization
     * @param OrganizationMember $member
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Organization $organization, OrganizationMember $member, Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, ['label' => 'Remove'])->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->remove($member);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute(
                'unitedcms_core_organizationuser_index',
                [
                    'organization' => $organization->getIdentifier(),
                ]
            );
        }

        return $this->render(
            'UnitedCMSCoreBundle:Organization/User:delete.html.twig',
            [
                'organization' => $organization,
                'form' => $form->createView(),
                'member' => $member,
            ]
        );
    }
}
