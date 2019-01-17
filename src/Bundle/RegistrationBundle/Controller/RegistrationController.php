<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 23.05.18
 * Time: 17:44
 */

namespace UniteCMS\RegistrationBundle\Controller;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Event\RegistrationEvent;
use UniteCMS\RegistrationBundle\Form\Model\RegistrationModel;
use UniteCMS\RegistrationBundle\Form\RegistrationType;

class RegistrationController extends AbstractController
{

    /**
     * @Route("/registration", methods={"GET", "POST"})
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param ValidatorInterface $validator
     * @param EventDispatcherInterface $eventDispatcher
     * @return \Symfony\Component\HttpFoundation\Response
     *
     */
    public function registrationAction(Request $request, UserPasswordEncoderInterface $passwordEncoder, ValidatorInterface $validator, EventDispatcherInterface $eventDispatcher)
    {

        // Redirect the user to / if logged in
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl('unitecms_core_index', [], Router::ABSOLUTE_URL));
        }

        $form = $this->createForm(RegistrationType::class);
        $registrationModelClass = $form->getConfig()->getDataClass();

        if(!empty($registrationModelClass) && is_a($registrationModelClass, RegistrationModel::class, true)) {
            $registration = new $registrationModelClass();
        } else {
            $registration = new RegistrationModel();
        }

        $form->setData($registration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = new User();
            $user
                ->setName($registration->getName())
                ->setEmail($registration->getEmail())
                ->setName($registration->getName())
                ->setPassword(
                    $passwordEncoder->encodePassword(
                        $user,
                        $registration->getPassword()
                    )
                );

            $organization = new Organization();
            $organization
                ->setTitle($registration->getOrganizationTitle())
                ->setIdentifier($registration->getOrganizationIdentifier());

            $organizationMember = new OrganizationMember();
            $organizationMember->setOrganization($organization)->setSingleRole(Organization::ROLE_ADMINISTRATOR);
            $user->addOrganization($organizationMember);

            // Clear plaintext password, so it can't be accessed later in this request.
            $registration->eraseCredentials();

            $organizationViolations = $validator->validate($organization);
            $userViolations = $validator->validate($user);

            // Show the user registration errors.
            if ($organizationViolations->count() > 0 || $userViolations->count() > 0) {
                $violationMapper = new ViolationMapper();

                foreach ($organizationViolations as $violation) {
                    $violationMapper->mapViolation($violation, $form->get('organizationIdentifier'));
                }

                foreach ($userViolations as $violation) {
                    $violationMapper->mapViolation($violation, $form->get('email'));
                }

                $eventDispatcher->dispatch(RegistrationEvent::REGISTRATION_FAILURE, new RegistrationEvent($registration, 'registration'));
            }

            // If organization and user are valid.
            else {
                $eventDispatcher->dispatch(RegistrationEvent::REGISTRATION_SUCCESS, new RegistrationEvent($registration, 'registration'));

                $this->getDoctrine()->getManager()->persist($organization);
                $this->getDoctrine()->getManager()->persist($user);
                $this->getDoctrine()->getManager()->flush();

                // Login the user and redirect him_her to the new organization.
                $userToken = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                $this->container->get('security.token_storage')->setToken($userToken);
                $this->container->get('session')->set('_security_main', serialize($userToken));

                $eventDispatcher->dispatch(RegistrationEvent::REGISTRATION_COMPLETE, new RegistrationEvent($registration, 'registration'));
                return $this->redirect($this->generateUrl('unitecms_core_domain_index', [$organization]));
            }
        }

        return $this->render(
            '@UniteCMSRegistration/Registration/registration.html.twig',
            array(
                'form' => $form->createView(),
            )
        );
    }
}