<?php

namespace UniteCMS\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Constraints\EqualTo;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Invitation;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Event\CancellationEvent;
use UniteCMS\CoreBundle\Event\RegistrationEvent;
use UniteCMS\CoreBundle\Form\InvitationRegistrationType;
use UniteCMS\CoreBundle\Form\Model\ChangePassword;
use UniteCMS\CoreBundle\Form\Model\InvitationRegistrationModel;
use UniteCMS\CoreBundle\ParamConverter\IdentifierNormalizer;

class ProfileController extends Controller
{

    /**
     * @Route("/update")
     * @Method({"GET", "POST"})
     * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateAction(Request $request)
    {
        $forms = [];
        $user = $this->getUser();
        $changePassword = new ChangePassword();

        // Personal settings form.
        $forms['personal'] = $this->get('form.factory')->createNamedBuilder('user', FormType::class, $user)
            ->add('name', TextType::class, ['label' => 'profile.personal.form.name', 'required' => true])
            ->add('email', EmailType::class, ['label' => 'profile.personal.form.email', 'required' => true])
            ->add('submit', SubmitType::class, ['label' => 'profile.personal.form.submit'])
            ->getForm();

        $forms['personal']->handleRequest($request);

        if ($forms['personal']->isSubmitted() && $forms['personal']->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            return $this->redirect($this->generateUrl('unitecms_core_index', [], Router::ABSOLUTE_URL));
        }


        // Change password form.
        $forms['change_password'] = $this->get('form.factory')->createNamedBuilder('change_password',FormType::class, $changePassword, ['validation_groups' => 'UPDATE'])
            ->add('currentPassword',PasswordType::class, [
                'label' => 'profile.change_password.form.current_password',
                'required' => true,
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'validation.passwords_must_match',
                'required' => true,
                'first_options' => array('label' => 'profile.change_password.form.new_password'),
                'second_options' => array('label' => 'profile.change_password.form.new_password_repeat'),
            ])
            ->add('submit', SubmitType::class, ['label' => 'profile.change_password.form.submit'])
            ->getForm();

        $forms['change_password']->handleRequest($request);

        if ($forms['change_password']->isSubmitted() && $forms['change_password']->isValid()) {
            $this->getUser()->setPassword(
                $this->get('security.password_encoder')->encodePassword($this->getUser(), $changePassword->getNewPassword())
            );

            // Clear plaintext password, so it can't be accessed later in this request.
            $changePassword->eraseCredentials();
            $this->getDoctrine()->getManager()->flush();

            return $this->redirect($this->generateUrl('unitecms_core_index', [], Router::ABSOLUTE_URL));
        }


        // Delete account.
        $forms['delete_account'] = $this->get('form.factory')->createNamedBuilder('delete_account',FormType::class, null)
            ->add('type_email', EmailType::class, [
                'label' => 'profile.delete_account.form.type_email',
                'required' => true,
                'attr' => ['autocomplete' => 'off'],
                'constraints' => [
                    new EqualTo(['value' => $user->getEmail(), 'message' => $this->get('translator')->trans('profile.delete_account.form.type_email.not_equal')]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'profile.delete_account.form.submit',
                'attr' => ['class' => 'uk-button-danger'],
            ])
            ->getForm();

        $forms['delete_account']->handleRequest($request);

        if ($forms['delete_account']->isSubmitted() && $forms['delete_account']->isValid()) {

            $violations = $this->get('validator')->validate($user, null, ['DELETE']);

            // If there where violation problems.
            if($violations->count() > 0) {

                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $forms['delete_account']);
                }

                $this->get('event_dispatcher')->dispatch(CancellationEvent::CANCELLATION_FAILURE, new CancellationEvent($user));

            // if this member is save to delete.
            } else {
                $this->get('event_dispatcher')->dispatch(CancellationEvent::CANCELLATION_SUCCESS, new CancellationEvent($user));

                $this->getDoctrine()->getManager()->remove($user);
                $this->getDoctrine()->getManager()->flush();

                // Clear user session.
                $this->container->get('security.token_storage')->setToken(null);
                $this->container->get('session')->clear();

                $this->get('event_dispatcher')->dispatch(CancellationEvent::CANCELLATION_COMPLETE, new CancellationEvent($user));

                return $this->redirect($this->generateUrl('unitecms_core_index'));
            }
        }

        return $this->render(
            '@UniteCMSCore/Profile/update.html.twig', [
                'forms' => array_map(function($form) { return $form->createView(); }, $forms),
            ]
        );
    }

    /**
     * @Route("/reset-password")
     * @Method({"GET", "POST"})
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resetPasswordAction(Request $request)
    {
        // Redirect the user to / if already authenticated.
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl('unitecms_core_index', [], Router::ABSOLUTE_URL));
        }

        $form = $this->createFormBuilder()
            ->add('username', EmailType::class, ['required' => true, 'label' => 'Email'])
            ->add('submit', SubmitType::class, ['label' => 'Reset'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $email = $form->getData()['username'];

            // check if there is a user for this email address.
            if ($user = $this->getDoctrine()->getRepository('UniteCMSCoreBundle:User')->findOneBy(
                ['email' => $email]
            )) {

                // Show message to user, that a new password is already requested.
                if (!$user->isResetRequestExpired()) {
                    $form->get('username')->addError(new FormError('password_reset.reset_request_not_expired'));

                    // Otherwise create a new password request token.
                } else {

                    // Generate a secure token. This line was taken from https://github.com/FriendsOfSymfony/FOSUserBundle/blob/master/Util/TokenGenerator.php
                    $user->setResetToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='));
                    $user->setResetRequestedAt(new \DateTime());

                    // Create message.
                    $message = (new \Swift_Message($this->get('translator')->trans('email.reset_password.headline')))
                        ->setFrom($this->getParameter('mailer_sender'))
                        ->setTo($user->getEmail())
                        ->setBody(
                            $this->renderView(
                                '@UniteCMSCore/Emails/reset-password.html.twig',
                                [
                                    'reset_url' => $this->generateUrl(
                                        'unitecms_core_profile_resetpasswordconfirm',
                                        [
                                            'token' => $user->getResetToken(),
                                        ],
                                        UrlGeneratorInterface::ABSOLUTE_URL
                                    ),
                                ]
                            ),
                            'text/html'
                        );

                    // Save token.
                    $this->getDoctrine()->getManager()->flush();

                    // Send out message.
                    $this->get('mailer')->send($message);
                }
            }
        }

        return $this->render(
            '@UniteCMSCore/Profile/reset-password.html.twig',
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @Route("/reset-password-confirm")
     * @Method({"GET", "POST"})
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resetPasswordConfirmAction(Request $request)
    {
        // Redirect the user to / if already authenticated.
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl('unitecms_core_index', [], Router::ABSOLUTE_URL));
        }

        $userFound = false;
        $tokenExpired = true;
        $changePasswordForm = null;
        $token = $request->query->get('token');

        // check if there is a user for this token.
        if ($user = $this->getDoctrine()->getRepository('UniteCMSCoreBundle:User')->findOneBy(
            ['resetToken' => $token]
        )) {
            $userFound = true;

            // If token still is valid, we can show the password reset form.
            if (!$user->isResetRequestExpired()) {
                $tokenExpired = false;

                $changePassword = new ChangePassword();
                $changePasswordForm = $this->get('form.factory')->createNamedBuilder(
                    'change_password',
                    FormType::class,
                    $changePassword,
                    ['validation_groups' => 'RESET']
                )
                    ->add(
                        'newPassword',
                        RepeatedType::class,
                        [
                            'type' => PasswordType::class,
                            'invalid_message' => 'validation.passwords_must_match',
                            'required' => true,
                            'first_options' => array('label' => 'New password'),
                            'second_options' => array('label' => 'Repeat new password'),
                        ]
                    )
                    ->add('submit', SubmitType::class, ['label' => 'Update'])
                    ->getForm();

                $changePasswordForm->handleRequest($request);

                if ($changePasswordForm->isSubmitted() && $changePasswordForm->isValid()) {

                    $user->setPassword(
                        $this->get('security.password_encoder')->encodePassword(
                            $user,
                            $changePassword->getNewPassword()
                        )
                    );
                    $user->clearResetToken();

                    // Clear plaintext password, so it can't be accessed later in this request.
                    $changePassword->eraseCredentials();
                    $this->getDoctrine()->getManager()->flush();
                }
            }
        }

        return $this->render(
            '@UniteCMSCore/Profile/reset-password-confirm.html.twig',
            array(
                'userFound' => $userFound,
                'tokenExpired' => $tokenExpired,
                'form' => $changePasswordForm ? $changePasswordForm->createView() : null,
            )
        );
    }

    /**
     * @Route("/accept-invitation")
     * @Method({"GET", "POST"})
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function acceptInvitationAction(Request $request)
    {
        $tokenPresent = false;
        $tokenFound = false;
        $tokenExpired = true;
        $wrongUser = true;
        $token = null;

        /*** @var Invitation $invitation */
        $invitation = null;

        /*** @var User $user */
        $existingUser = null;

        $newUser = true;
        $alreadyMember = false;
        $form = null;

        if (!empty($token = $request->query->get('token'))) {
            $tokenPresent = true;

            if ($invitation = $this->getDoctrine()->getRepository('UniteCMSCoreBundle:Invitation')->findOneBy(
                ['token' => $token]
            )) {
                $tokenFound = true;

                if (!$invitation->isExpired()) {
                    $tokenExpired = false;

                    // If the invited user is already member.
                    if ($existingUser = $this->getDoctrine()->getRepository('UniteCMSCoreBundle:User')->findOneBy(
                        ['email' => $invitation->getEmail()]
                    )) {

                        $newUser = false;

                        // If no user is logged in, redirect the user to the login page
                        if (!$this->getUser()) {

                            $this->addFlash(
                                'success',
                                $this->get('translator')->trans('profile.accept_invitation.please_login')
                            );

                            throw $this->createAccessDeniedException();
                        }

                        if ($existingUser == $this->getUser()) {
                            $wrongUser = false;

                            foreach ($existingUser->getOrganizations() as $orgMember) {
                                if ($orgMember->getOrganization() === $invitation->getOrganization()) {
                                    $alreadyMember = true;
                                }
                            }

                            if (!$alreadyMember) {
                                $form = $this->createFormBuilder()
                                    ->add('accept', SubmitType::class, ['label' => 'profile.accept_invitation.form.accept.button'])
                                    ->add('reject', SubmitType::class, ['label' => 'profile.accept_invitation.form.reject.button', 'attr' => ['class' => 'uk-button-danger']])
                                    ->getForm();

                                $form->handleRequest($request);

                                if ($form->isSubmitted() && $form->isValid()) {
                                    if ($form->get('accept')->isClicked()) {

                                        $organizationMember = new OrganizationMember();
                                        $organizationMember->setOrganization($invitation->getOrganization());
                                        $existingUser->addOrganization($organizationMember);

                                        $domainMember = null;

                                        // If this invitation includes a domain membership, we create it.
                                        if($invitation->getDomainMemberType()) {
                                            $domainMember = new DomainMember();
                                            $domainMember
                                                ->setDomain($invitation->getDomainMemberType()->getDomain())
                                                ->setDomainMemberType($invitation->getDomainMemberType());
                                            $existingUser->addDomain($domainMember);
                                        }

                                        // Validate user.
                                        if (!$this->get('validator')->validate($existingUser)) {
                                            $form->addError(new FormError('invitation.invalid_user'));
                                        } else {

                                            // Delete invitation.
                                            $this->getDoctrine()->getManager()->remove($invitation);

                                            // Save orgMember.
                                            $this->getDoctrine()->getManager()->persist($organizationMember);

                                            if($domainMember) {
                                                $this->getDoctrine()->getManager()->persist($domainMember);
                                            }

                                            // Save changes to database.
                                            $this->getDoctrine()->getManager()->flush();

                                            // Redirect to index.
                                            if($domainMember) {
                                                return $this->redirect(
                                                    $this->generateUrl('unitecms_core_domain_view', [
                                                        'organization' => IdentifierNormalizer::denormalize($organizationMember->getOrganization()->getIdentifier()),
                                                        'domain' => $domainMember->getDomain()->getIdentifier(),
                                                    ], Router::ABSOLUTE_URL)
                                                );
                                            } else {
                                                return $this->redirect(
                                                    $this->generateUrl('unitecms_core_domain_index', [
                                                        'organization' => IdentifierNormalizer::denormalize($organizationMember->getOrganization()->getIdentifier()),
                                                    ], Router::ABSOLUTE_URL)
                                                );
                                            }
                                        }

                                        // If the user rejects the invitation, just delete it.
                                    } elseif ($form->get('reject')->isClicked()) {

                                        // Delete invitation.
                                        $this->getDoctrine()->getManager()->remove($invitation);

                                        // Save changes to database.
                                        $this->getDoctrine()->getManager()->flush();

                                        // Redirect to index.
                                        return $this->redirect($this->generateUrl('unitecms_core_index', [], Router::ABSOLUTE_URL));
                                    }
                                }
                            }
                        }


                        // If the invited user is not already member.
                    } else {

                        $newUser = true;

                        // An invitation for a new user can only be accepted if no user is logged in.
                        if (!$this->getUser()) {
                            $wrongUser = false;

                            $form = $this->createForm(InvitationRegistrationType::class);
                            $registrationModelClass = $form->getConfig()->getDataClass();

                            if(!empty($registrationModelClass) && is_a($registrationModelClass, InvitationRegistrationModel::class, true)) {
                                $registration = new $registrationModelClass();
                            } else {
                                $registration = new InvitationRegistrationModel();
                            }

                            $registration->setEmail($invitation->getEmail());
                            $form->setData($registration);
                            $form->handleRequest($request);

                            if ($form->isSubmitted() && $form->isValid()) {

                                $user = new User();
                                $user
                                    ->setEmail($invitation->getEmail())
                                    ->setName($registration->getName())
                                    ->setPassword(
                                        $this->get('security.password_encoder')->encodePassword(
                                            $user,
                                            $registration->getPassword()
                                        )
                                    );

                                // Create organization membership for this user.
                                $organizationMember = new OrganizationMember();
                                $organizationMember->setOrganization($invitation->getOrganization());
                                $user->addOrganization($organizationMember);

                                $domainMember = null;

                                // If this invitation includes a domain membership, we create it.
                                if($invitation->getDomainMemberType()) {
                                    $domainMember = new DomainMember();
                                    $domainMember
                                        ->setDomain($invitation->getDomainMemberType()->getDomain())
                                        ->setDomainMemberType($invitation->getDomainMemberType());
                                    $user->addDomain($domainMember);
                                }

                                // Clear plaintext password, so it can't be accessed later in this request.
                                $registration->eraseCredentials();

                                $violations = $this->get('validator')->validate($user);

                                // Validate new created user.
                                if ($violations->count() > 0) {
                                    $form->addError(new FormError('invitation.invalid_user'));
                                    $this->get('event_dispatcher')->dispatch(RegistrationEvent::REGISTRATION_FAILURE, new RegistrationEvent($registration));

                                } else {

                                    $this->get('event_dispatcher')->dispatch(RegistrationEvent::REGISTRATION_SUCCESS, new RegistrationEvent($registration));

                                    $this->getDoctrine()->getManager()->persist($user);

                                    $this->getDoctrine()->getManager()->persist($organizationMember);

                                    if($domainMember) {
                                        $this->getDoctrine()->getManager()->persist($domainMember);
                                    }

                                    // Delete invitation.
                                    $this->getDoctrine()->getManager()->remove($invitation);

                                    // Save changes to database.
                                    $this->getDoctrine()->getManager()->flush();

                                    // Login the user and redirect him_her to index.
                                    $userToken = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                                    $this->container->get('security.token_storage')->setToken($userToken);
                                    $this->container->get('session')->set('_security_main', serialize($userToken));

                                    $this->get('event_dispatcher')->dispatch(RegistrationEvent::REGISTRATION_COMPLETE, new RegistrationEvent($registration));

                                    if($domainMember) {
                                        return $this->redirect($this->generateUrl('unitecms_core_domain_view', [
                                            'organization' => IdentifierNormalizer::denormalize($organizationMember->getOrganization()->getIdentifier()),
                                            'domain' => $domainMember->getDomain()->getIdentifier(),
                                        ], Router::ABSOLUTE_URL));
                                    } else {
                                        return $this->redirect($this->generateUrl('unitecms_core_domain_index', [
                                            'organization' => IdentifierNormalizer::denormalize($organizationMember->getOrganization()->getIdentifier()),
                                        ], Router::ABSOLUTE_URL));
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $this->render(
            '@UniteCMSCore/Profile/accept-invitation.html.twig',
            array(
                'tokenPresent' => $tokenPresent,
                'tokenFound' => $tokenFound,
                'tokenExpired' => $tokenExpired,
                'newUser' => $newUser,
                'wrongUser' => $wrongUser,
                'alreadyMember' => $alreadyMember,
                'token' => $token,
                'invitation' => $invitation,
                'form' => $form ? $form->createView() : null,
            )
        );
    }
}
