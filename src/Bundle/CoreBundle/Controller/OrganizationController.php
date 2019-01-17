<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 05.06.18
 * Time: 14:56
 */

namespace UniteCMS\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Security\Voter\OrganizationVoter;

class OrganizationController extends AbstractController
{
    /**
     * @Route("/", methods={"GET"})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\OrganizationVoter::LIST'), 'UniteCMS\\CoreBundle\\Entity\\Organization')")
     *
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request) {

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

        return $this->render(
            '@UniteCMSCore/Organization/index.html.twig',
            [
                'organizations' => $allowedOrganizations,
            ]
        );
    }

    /**
     * @Route("/create", methods={"GET", "POST"})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\OrganizationVoter::CREATE'), 'UniteCMS\\CoreBundle\\Entity\\Organization')")
     *
     * @param Request $request
     * @return Response
     */
    public function createAction(Request $request) {

        $organization = new Organization();

        $form = $this->createFormBuilder($organization)
            ->add('title', TextType::class, ['label' => 'organizations.create.form.title', 'required' => true])
            ->add('identifier', TextType::class, ['label' => 'organizations.create.form.identifier', 'required' => true])
            ->add('submit', SubmitType::class, ['label' => 'organizations.create.form.submit'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->persist($organization);
            $this->getDoctrine()->getManager()->flush();
            return $this->redirect($this->generateUrl('unitecms_core_domain_index', [$organization]));
        }

        return $this->render('@UniteCMSCore/Organization/create.html.twig', [ 'form' => $form->createView()]);
    }

    /**
     * @Route("/update/{organization}", methods={"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\OrganizationVoter::UPDATE'), organization)")
     *
     * @param Organization $organization
     * @param Request $request
     * @return Response
     */
    public function updateAction(Organization $organization, Request $request) {

        $form = $this->createFormBuilder($organization)
            ->add('title', TextType::class, ['label' => 'organizations.update.form.title', 'required' => true])
            ->add('identifier', TextType::class, ['label' => 'organizations.update.form.identifier', 'required' => true])
            ->add('submit', SubmitType::class, ['label' => 'organizations.update.form.submit'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            return $this->redirect($this->generateUrl('unitecms_core_domain_index', [$organization]));
        }

        return $this->render('@UniteCMSCore/Organization/update.html.twig', [ 'form' => $form->createView(), 'organization' => $organization]);
    }

    /**
     * @Route("/delete/{organization}", methods={"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\OrganizationVoter::DELETE'), organization)")
     *
     * @param Organization $organization
     * @param Request $request
     * @return Response
     */
    public function deleteAction(Organization $organization, Request $request, ValidatorInterface $validator) {

        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, ['label' => 'organizations.delete.form.submit', 'attr' => ['class' => 'uk-button-danger']])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $violations = $validator->validate($organization, null, ['DELETE']);

            if($violations->count() > 0) {
                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }
            } else {
                $this->getDoctrine()->getManager()->remove($organization);
                $this->getDoctrine()->getManager()->flush();
                return $this->redirect($this->generateUrl('unitecms_core_organization_index'));
            }
        }

        return $this->render('@UniteCMSCore/Organization/delete.html.twig', [ 'form' => $form->createView(), 'organization' => $organization]);
    }
}