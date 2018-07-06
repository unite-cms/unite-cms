<?php

namespace UniteCMS\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use UniteCMS\CoreBundle\Entity\Invitation;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Form\ChoiceCardsType;
use UniteCMS\CoreBundle\Form\Model\ChoiceCardOption;
use UniteCMS\CoreBundle\ParamConverter\IdentifierNormalizer;

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
        $invites = $this->get('knp_paginator')->paginate($organization->getInvites());

        return $this->render(
            '@UniteCMSCore/Organization/User/index.html.twig',
            [
                'organization' => $organization,
                'users' => $users,
                'invites' => $invites,
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

            return $this->redirect($this->generateUrl(
                'unitecms_core_organizationuser_index',
                [
                    'organization' => IdentifierNormalizer::denormalize($organization->getIdentifier()),
                ], Router::ABSOLUTE_URL
            ));
        }

        return $this->render(
            '@UniteCMSCore/Organization/User/update.html.twig',
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

                return $this->redirect($this->generateUrl(
                    'unitecms_core_organizationuser_index',
                    [
                        'organization' => IdentifierNormalizer::denormalize($organization->getIdentifier()),
                    ],
                    Router::ABSOLUTE_URL
                ));
            }
        }

        return $this->render(
            '@UniteCMSCore/Organization/User/delete.html.twig',
            [
                'organization' => $organization,
                'form' => $form->createView(),
                'member' => $member,
            ]
        );
    }

    /**
     * @Route("/create-invite")
     * @Method({"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\OrganizationVoter::UPDATE'), organization)")
     *
     * @param Organization $organization
     * @param Request $request
     * @return Response
     */
    public function createInviteAction(Organization $organization, Request $request)
    {
        // create invite form.
        $form = $this->get('form.factory')->createNamedBuilder('create_organization_invite', FormType::class, null, ['attr' => ['class' => 'uk-form-vertical']])
            ->add('user_email', EmailType::class, ['label' => 'organization.user.create_invite.form.email', 'required' => true])
            ->add('submit', SubmitType::class, ['label' => 'organization.user.create_invite.form.submit'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            $invitation = new Invitation();
            $invitation->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='));
            $invitation->setRequestedAt(new \DateTime());
            $invitation->setOrganization($organization);
            $invitation->setEmail($data['user_email']);

            $violations = $this->get('validator')->validate($invitation);
            if($violations->count() > 0) {
                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }
            } else {

                $this->getDoctrine()->getManager()->persist($invitation);
                $this->getDoctrine()->getManager()->flush();

                // Send out email using the default mailer.
                $message = (new \Swift_Message($this->get('translator')->trans('email.invitation.headline', ['%invitor%' => $this->getUser()])))
                    ->setFrom($this->getParameter('mailer_sender'))
                    ->setTo($invitation->getEmail())
                    ->setBody(
                        $this->renderView(
                            '@UniteCMSCore/Emails/invitation.html.twig',
                            [
                                'invitation' => $invitation,
                                'invitation_url' => $this->generateUrl(
                                    'unitecms_core_profile_acceptinvitation',
                                    [
                                        'token' => $invitation->getToken(),
                                    ],
                                    UrlGeneratorInterface::ABSOLUTE_URL
                                ),
                            ]
                        ),
                        'text/html'
                    );
                $this->get('mailer')->send($message);
                return $this->redirect($this->generateUrl('unitecms_core_organizationuser_index', ['organization' => IdentifierNormalizer::denormalize($organization->getIdentifier())], Router::ABSOLUTE_URL));
            }
        }

        return $this->render(
            '@UniteCMSCore/Organization/User/create_invite.html.twig',
            [
                'organization' => $organization,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/delete-invite/{invite}")
     * @Method({"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("invite")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\OrganizationVoter::UPDATE'), organization)")
     *
     * @param Organization $organization
     * @param Invitation $invite
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteInviteAction(Organization $organization, Invitation $invite, Request $request) {
        $form = $this->createFormBuilder()
            ->add(
                'submit',
                SubmitType::class,
                ['label' => 'organization.user.delete_invitation.form.submit', 'attr' => ['class' => 'uk-button-danger']]
            )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->remove($invite);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirect($this->generateUrl('unitecms_core_organizationuser_index', [
                'organization' => IdentifierNormalizer::denormalize($organization->getIdentifier()),
            ], Router::ABSOLUTE_URL));
        }

        return $this->render(
            '@UniteCMSCore/Organization/User/delete_invite.html.twig',
            [
                'organization' => $organization,
                'form' => $form->createView(),
                'invite' => $invite,
            ]
        );
    }
}
