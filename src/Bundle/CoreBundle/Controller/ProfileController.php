<?php

namespace UnitedCMS\CoreBundle\Controller;

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
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use UnitedCMS\CoreBundle\Entity\DomainMember;
use UnitedCMS\CoreBundle\Entity\User;
use UnitedCMS\CoreBundle\Form\Model\ChangePassword;
use UnitedCMS\CoreBundle\Form\Model\Registration;

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
        /**
         * @var \UnitedCMS\CoreBundle\Entity\User $user
         */
        $user = $this->getUser();
        $form = $this->get('form.factory')->createNamedBuilder('user', FormType::class, $user)
            ->add('firstname', TextType::class, ['label' => 'Firstname', 'required' => true])
            ->add('lastname', TextType::class, ['label' => 'Lastname', 'required' => true])
            ->add('email', EmailType::class, ['label' => 'Email', 'required' => true])
            ->add('submit', SubmitType::class, ['label' => 'Update'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('unitedcms_core_organizations');
        }

        $changePassword = new ChangePassword();
        $changePasswordForm = $this->get('form.factory')->createNamedBuilder(
            'change_password',
            FormType::class,
            $changePassword,
            ['validation_groups' => 'UPDATE']
        )
            ->add('currentPassword', PasswordType::class, ['label' => 'Password', 'required' => true])
            ->add(
                'newPassword',
                RepeatedType::class,
                [
                    'type' => PasswordType::class,
                    'invalid_message' => 'validation.passwords_must_match',
                    'required' => true,
                    'first_options' => array('label' => 'Password'),
                    'second_options' => array('label' => 'Repeat Password'),
                ]
            )
            ->add('submit', SubmitType::class, ['label' => 'Update'])
            ->getForm();

        $changePasswordForm->handleRequest($request);

        if ($changePasswordForm->isSubmitted() && $changePasswordForm->isValid()) {
            $user->setPassword(
                $this->get('security.password_encoder')->encodePassword($user, $changePassword->getNewPassword())
            );

            // Clear plaintext password, so it can't be accessed later in this request.
            $changePassword->eraseCredentials();
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('unitedcms_core_organizations');
        }

        return $this->render(
            'UnitedCMSCoreBundle:Profile:update.html.twig',
            [
                'form' => $form->createView(),
                'change_password_form' => $changePasswordForm->createView(),
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
            return $this->redirectToRoute('unitedcms_core_organizations');
        }

        $form = $this->createFormBuilder()
            ->add('username', EmailType::class, ['required' => true, 'label' => 'Email'])
            ->add('submit', SubmitType::class, ['label' => 'Reset'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $email = $form->getData()['username'];

            // check if there is a user for this email address.
            if ($user = $this->getDoctrine()->getRepository('UnitedCMSCoreBundle:User')->findOneBy(
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
                    $this->getDoctrine()->getManager()->flush();

                    // Send out email using the default mailer.
                    $message = (new \Swift_Message('Reset Password'))
                        ->setFrom($this->getParameter('mailer_sender'))
                        ->setTo($user->getEmail())
                        ->setBody(
                            $this->renderView(
                                '@UnitedCMSCore/Emails/reset-password.html.twig',
                                [
                                    'reset_url' => $this->generateUrl(
                                        'unitedcms_core_profile_resetpasswordconfirm',
                                        [
                                            'token' => $user->getResetToken(),
                                        ],
                                        UrlGeneratorInterface::ABSOLUTE_URL
                                    ),
                                ]
                            ),
                            'text/html'
                        );
                    $this->get('mailer')->send($message);
                }
            }
        }

        return $this->render(
            'UnitedCMSCoreBundle:Profile:reset-password.html.twig',
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
            return $this->redirectToRoute('unitedcms_core_organizations');
        }

        $userFound = false;
        $tokenExpired = true;
        $changePasswordForm = null;
        $token = $request->query->get('token');

        // check if there is a user for this token.
        if ($user = $this->getDoctrine()->getRepository('UnitedCMSCoreBundle:User')->findOneBy(
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
            'UnitedCMSCoreBundle:Profile:reset-password-confirm.html.twig',
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
        $invitation = null;
        $newUser = true;
        $alreadyMember = false;
        $form = null;

        if (!empty($token = $request->query->get('token'))) {
            $tokenPresent = true;

            if ($invitation = $this->getDoctrine()->getRepository('UnitedCMSCoreBundle:DomainInvitation')->findOneBy(
                ['token' => $token]
            )) {
                $tokenFound = true;

                if (!$invitation->isExpired()) {
                    $tokenExpired = false;

                    // If the invited user is already member.
                    if ($existingUser = $this->getDoctrine()->getRepository('UnitedCMSCoreBundle:User')->findOneBy(
                        ['email' => $invitation->getEmail()]
                    )) {

                        $newUser = false;

                        // If no user is logged in, redirect the user to the login page
                        if(!$this->getUser()) {
                            throw $this->createAccessDeniedException();
                        }

                        if ($existingUser == $this->getUser()) {
                            $wrongUser = false;

                            foreach ($existingUser->getDomains() as $domainMember) {
                                if ($domainMember->getDomain() === $invitation->getDomain()) {
                                    $alreadyMember = true;
                                }
                            }

                            if (!$alreadyMember) {
                                $form = $this->createFormBuilder()
                                    ->add('accept', SubmitType::class, ['label' => 'invitation.accept'])
                                    ->add('reject', SubmitType::class, ['label' => 'invitation.reject'])
                                    ->getForm();

                                $form->handleRequest($request);

                                if ($form->isSubmitted() && $form->isValid()) {
                                    if ($form->get('accept')->isClicked()) {
                                        $domainMember = new DomainMember();
                                        $domainMember
                                            ->setDomain($invitation->getDomain())
                                            ->setRoles($invitation->getRoles());
                                        $existingUser->addDomain($domainMember);

                                        // Validate user.
                                        if (!$this->get('validator')->validate($existingUser)) {
                                            $form->addError(new FormError('invitation.invalid_user'));
                                        } else {

                                            // Delete invitation.
                                            $this->getDoctrine()->getManager()->remove($invitation);

                                            // Save domainMember.
                                            $this->getDoctrine()->getManager()->persist($domainMember);

                                            // Save changes to database.
                                            $this->getDoctrine()->getManager()->flush();
                                        }

                                        // If the user rejects the invitation, just delete it.
                                    } elseif ($form->get('reject')->isClicked()) {

                                        // Delete invitation.
                                        $this->getDoctrine()->getManager()->remove($invitation);

                                        // Save changes to database.
                                        $this->getDoctrine()->getManager()->flush();
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

                            $registration = new Registration();
                            $form = $this->get('form.factory')->createNamedBuilder(
                                'registration',
                                FormType::class,
                                $registration
                            )
                                ->add(
                                    'firstname',
                                    TextType::class,
                                    ['label' => 'Firstname', 'required' => true, 'attr' => ['autocomplete' => 'off']]
                                )
                                ->add(
                                    'lastname',
                                    TextType::class,
                                    ['label' => 'Lastname', 'required' => true, 'attr' => ['autocomplete' => 'off']]
                                )
                                ->add(
                                    'password',
                                    RepeatedType::class,
                                    [
                                        'type' => PasswordType::class,
                                        'invalid_message' => 'validation.passwords_must_match',
                                        'required' => true,
                                        'first_options' => array('label' => 'Password'),
                                        'second_options' => array('label' => 'Repeat password'),
                                        'attr' => ['autocomplete' => 'off'],
                                    ]
                                )
                                ->add('submit', SubmitType::class, ['label' => 'invitation.accept'])
                                ->getForm();

                            $form->handleRequest($request);

                            if ($form->isSubmitted() && $form->isValid()) {
                                $user = new User();
                                $domainMember = new DomainMember();
                                $domainMember
                                    ->setDomain($invitation->getDomain())
                                    ->setRoles($invitation->getRoles());
                                $user
                                    ->setEmail($invitation->getEmail())
                                    ->setLastname($registration->getLastname())
                                    ->setFirstname($registration->getFirstname())
                                    ->addDomain($domainMember)
                                    ->setPassword(
                                        $this->get('security.password_encoder')->encodePassword(
                                            $user,
                                            $registration->getPassword()
                                        )
                                    );

                                // Clear plaintext password, so it can't be accessed later in this request.
                                $registration->eraseCredentials();

                                // Validate new created user.
                                if (!$this->get('validator')->validate($user)) {
                                    $form->addError(new FormError('invitation.invalid_user'));
                                } else {
                                    $this->getDoctrine()->getManager()->persist($domainMember);
                                    $this->getDoctrine()->getManager()->persist($user);

                                    // Delete invitation.
                                    $this->getDoctrine()->getManager()->remove($invitation);

                                    // Save changes to database.
                                    $this->getDoctrine()->getManager()->flush();
                                }
                            }
                        }
                    }
                }
            }
        }

        return $this->render(
            'UnitedCMSCoreBundle:Profile:accept-invitation.html.twig',
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
