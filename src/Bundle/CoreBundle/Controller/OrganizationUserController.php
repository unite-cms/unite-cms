<?php

namespace UniteCMS\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Form\ChoiceCardsType;
use UniteCMS\CoreBundle\Form\Model\ChoiceCardOption;

class OrganizationUserController extends Controller
{
    /**
     * @Route("/")
     * @Method({"GET"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\OrganizationVoter::UPDATE'), organization)")
     *
     * @param Organization $organization
     * @return Response
     */
    public function indexAction(Organization $organization)
    {
        $users = $this->get('knp_paginator')->paginate($organization->getMembers());

        return $this->render(
            'UniteCMSCoreBundle:Organization/User:index.html.twig',
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
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\OrganizationVoter::UPDATE'), organization)")
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
            new ChoiceCardOption(
                Organization::ROLE_USER,
                'User',
                'Users can only manage content in domains, they are invited to.',
                'user'
            ),
            new ChoiceCardOption(
                Organization::ROLE_ADMINISTRATOR,
                'Administrator',
                'Administrators have access to all domains and can manage users and api keys.',
                'command'
            ),
        ];

        $form = $this->createFormBuilder($member, ['validation_groups' => ['UPDATE'], 'attr' => ['class' => 'uk-form-vertical']])
            ->add('singleRole', ChoiceCardsType::class, [
                'label' => 'organization.user.update.roles.label',
                'multiple' => false,
                'expanded' => true,
                'choices' => $available_roles,
            ])
            ->add('submit', SubmitType::class, ['label' => 'organization.user.update.form.submit'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute(
                'unitecms_core_organizationuser_index',
                [
                    'organization' => $organization->getIdentifier(),
                ]
            );
        }

        return $this->render(
            'UniteCMSCoreBundle:Organization/User:update.html.twig',
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
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\OrganizationVoter::UPDATE'), organization)")
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
            ->add('submit', SubmitType::class, [
                'label' => 'organization.user.delete.form.submit',
                'attr' => ['class' => 'uk-button-danger'
            ]])->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $violations = $this->get('validator')->validate($member, null, ['DELETE']);

            // If there where violation problems.
            if($violations->count() > 0) {

                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

            // if this member is save to delete.
            } else {
                $this->getDoctrine()->getManager()->remove($member);
                $this->getDoctrine()->getManager()->flush();

                return $this->redirectToRoute(
                    'unitecms_core_organizationuser_index',
                    [
                        'organization' => $organization->getIdentifier(),
                    ]
                );
            }
        }

        return $this->render(
            'UniteCMSCoreBundle:Organization/User:delete.html.twig',
            [
                'organization' => $organization,
                'form' => $form->createView(),
                'member' => $member,
            ]
        );
    }
}
