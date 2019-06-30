<?php

namespace UniteCMS\CoreBundle\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use UniteCMS\CoreBundle\Entity\Invitation;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Form\ChoiceCardsType;
use UniteCMS\CoreBundle\Form\Model\ChoiceCardOption;

class OrganizationUserController extends AbstractController
{
    /**
     * @Route("/", methods={"GET"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\OrganizationVoter::UPDATE'), organization)")
     *
     * @param Organization $organization
     * @param Request $request
     * @return Response
     */
    public function indexAction(Organization $organization, Request $request, PaginatorInterface $paginator)
    {
        $users = $paginator->paginate($organization->getMembers(),
            $request->query->getInt('page_users', 1),
            10,
            ['pageParameterName' => 'page_users', 'sortDirectionParameterName' => 'sort_users']
        );

        $invites = $paginator->paginate($organization->getInvites(),
            $request->query->getInt('page_invites', 1),
            10,
            ['pageParameterName' => 'page_invites', 'sortDirectionParameterName' => 'sort_invites']
        );

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
     * @Route("/update/{member}", methods={"GET", "POST"})
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
                [
                    'description' => 'Users can only manage content in domains, they are invited to.',
                ],
                'user'
            ),
            new ChoiceCardOption(
                Organization::ROLE_ADMINISTRATOR,
                'Administrator',
                [
                    'description' => 'Administrators have access to all domains and can manage users and api keys.',
                ],
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
            return $this->redirect($this->generateUrl('unitecms_core_organizationuser_index', [$organization]));
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
     * @Route("/delete/{member}", methods={"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("member")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\OrganizationVoter::UPDATE'), organization)")
     *
     * @param Organization $organization
     * @param OrganizationMember $member
     * @param Request $request
     *
     * @param ValidatorInterface $validator
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Organization $organization, OrganizationMember $member, Request $request, ValidatorInterface $validator)
    {
        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, [
                'label' => 'organization.user.delete.form.submit',
                'attr' => ['class' => 'uk-button-danger'
            ]])->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $violations = $validator->validate($member, null, ['DELETE']);

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
                return $this->redirect($this->generateUrl('unitecms_core_organizationuser_index', [$organization]));
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
     * @Route("/create-invite", methods={"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\OrganizationVoter::UPDATE'), organization)")
     *
     * @param Organization $organization
     * @param Request $request
     * @param FormFactoryInterface $formFactory
     * @param ValidatorInterface $validator
     * @param TranslatorInterface $translator
     * @param \Swift_Mailer $mailer
     * @param string $mailerSender
     * @return Response
     * @throws \Exception
     */
    public function createInviteAction(Organization $organization, Request $request, FormFactoryInterface $formFactory, ValidatorInterface $validator, TranslatorInterface $translator, \Swift_Mailer $mailer, string $mailerSender)
    {
        // create invite form.
        $form = $formFactory->createNamedBuilder('create_organization_invite', FormType::class, null, ['attr' => ['class' => 'uk-form-vertical']])
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

            $violations = $validator->validate($invitation);
            if($violations->count() > 0) {
                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }
            } else {

                $this->getDoctrine()->getManager()->persist($invitation);
                $this->getDoctrine()->getManager()->flush();

                // Send out email using the default mailer.
                $message = (new \Swift_Message($translator->trans('email.invitation.headline', ['%invitor%' => $this->getUser()])))
                    ->setFrom($mailerSender)
                    ->setTo($invitation->getEmail())
                    ->setBody(
                        $this->renderView(
                            '@UniteCMSCore/Emails/invitation.html.twig',
                            [
                                'invitation' => $invitation,
                                'invitation_url' => $this->generateUrl('unitecms_core_profile_acceptinvitation', ['token' => $invitation->getToken()]),
                            ]
                        ),
                        'text/html'
                    );
                $mailer->send($message);
                return $this->redirect($this->generateUrl('unitecms_core_organizationuser_index', [$organization]));
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
     * @Route("/delete-invite/{invite}", methods={"GET", "POST"})
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

            return $this->redirect($this->generateUrl('unitecms_core_organizationuser_index', [$organization]));
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
