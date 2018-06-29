<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 23.05.18
 * Time: 17:44
 */

namespace UniteCMS\RegistrationBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Event\RegistrationEvent;
use UniteCMS\RegistrationBundle\Form\Model\RegistrationModel;
use UniteCMS\RegistrationBundle\Form\RegistrationType;

class RegistrationController extends Controller
{

    /**
     * @Route("/registration")
     * @Method({"GET", "POST"})
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function registrationAction(Request $request)
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
                    $this->get('security.password_encoder')->encodePassword(
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

            $organizationViolations = $this->get('validator')->validate($organization);
            $userViolations = $this->get('validator')->validate($user);

            // Show the user registration errors.
            if ($organizationViolations->count() > 0 || $userViolations->count() > 0) {
                $violationMapper = new ViolationMapper();

                foreach ($organizationViolations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

                foreach ($userViolations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

                $this->get('event_dispatcher')->dispatch(RegistrationEvent::REGISTRATION_FAILURE, new RegistrationEvent($registration, 'registration'));
            }

            // If organization and user are valid.
            else {
                $this->get('event_dispatcher')->dispatch(RegistrationEvent::REGISTRATION_SUCCESS, new RegistrationEvent($registration, 'registration'));

                $this->get('doctrine.orm.entity_manager')->persist($organization);
                $this->get('doctrine.orm.entity_manager')->persist($user);
                $this->get('doctrine.orm.entity_manager')->flush();

                // Login the user and redirect him_her to the new organization.
                $userToken = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                $this->container->get('security.token_storage')->setToken($userToken);
                $this->container->get('session')->set('_security_main', serialize($userToken));

                $this->get('event_dispatcher')->dispatch(RegistrationEvent::REGISTRATION_COMPLETE, new RegistrationEvent($registration, 'registration'));
                return $this->redirect($this->generateUrl('unitecms_core_domain_index', ['organization' => $organization->getIdentifier()], Router::ABSOLUTE_URL));
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