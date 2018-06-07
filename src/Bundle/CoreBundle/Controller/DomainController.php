<?php

namespace UniteCMS\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationList;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Form\WebComponentType;

class DomainController extends Controller
{
    /**
     * @Route("/")
     * @Method({"GET"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\OrganizationVoter::VIEW'), organization)")
     *
     * @param Organization $organization
     * @param Request $request
     * @return Response
     */
    public function indexAction(Organization $organization, Request $request)
    {
        $domains = $organization->getDomains();

        return $this->render(
            'UniteCMSCoreBundle:Domain:index.html.twig',
            ['organization' => $organization, 'domains' => $domains]
        );
    }

    /**
     * @Route("/create")
     * @Method({"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\OrganizationVoter::UPDATE'), organization)")
     *
     * @param Organization $organization
     * @param Request $request
     * @return Response
     */
    public function createAction(Organization $organization, Request $request)
    {
        $domain = new Domain();
        $domain->setTitle('Untitled Domain')->setIdentifier('untitled');
        $form = $this->createFormBuilder(
            [
                'definition' => $this->get('unite.cms.domain_definition_parser')->serialize($domain),
            ]
        )
            ->add(
                'definition',
                WebComponentType::class,
                ['tag' => 'unite-cms-core-domaineditor']
            )->getForm()
            ->add('submit', SubmitType::class, ['label' => 'domain.create.form.submit', 'attr' => ['class' => 'uk-button uk-button-primary']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $domain = null;

            try {
                $domain = $this->get('unite.cms.domain_definition_parser')->parse($form->getData()['definition']);
            } catch (\Exception $e) {
                $form->get('definition')->addError(new FormError('Could not parse domain definition JSON.'));
            }

            if ($domain) {
                $domain->setOrganization($organization);

                $errors = $this->get('validator')->validate($domain);

                if ($errors->count() == 0) {
                    $this->getDoctrine()->getManager()->persist($domain);
                    $this->getDoctrine()->getManager()->flush();

                    return $this->redirectToRoute(
                        'unitecms_core_domain_view',
                        [
                            'organization' => $organization->getIdentifier(),
                            'domain' => $domain->getIdentifier(),
                        ]
                    );
                } else {
                    foreach ($errors as $error) {
                        $this->addFlash('danger', $error->getPropertyPath().': '.$error->getMessage());
                    }
                }
            }
        }

        return $this->render(
            'UniteCMSCoreBundle:Domain:create.html.twig',
            ['organization' => $organization, 'form' => $form->createView()]
        );
    }

    /**
     * @Route("/view/{domain}")
     * @Method({"GET"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\DomainVoter::VIEW'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @return Response
     */
    public function viewAction(Organization $organization, Domain $domain)
    {
        $contentTypes = $domain->getContentTypes();
        $settingTypes = $domain->getSettingTypes();

        return $this->render(
            'UniteCMSCoreBundle:Domain:view.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'contentTypes' => $contentTypes,
                'settingTypes' => $settingTypes,
            ]
        );
    }

    /**
     * @Route("/update/{domain}")
     * @Method({"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\DomainVoter::UPDATE'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @param Request $request
     * @return Response
     */
    public function updateAction(Organization $organization, Domain $domain, Request $request)
    {
        $originalDomain = null;
        $updatedDomain = null;

        $form = $this->createFormBuilder(['definition' => $this->get('unite.cms.domain_definition_parser')->serialize($domain)])
            ->add('definition', WebComponentType::class, ['tag' => 'unite-cms-core-domaineditor'])
            ->add('submit', SubmitType::class, ['label' => 'domain.update.form.submit', 'attr' => ['class' => 'uk-button uk-button-primary']])
            ->add('back', SubmitType::class, ['label' => 'domain.update.form.back', 'attr' => ['class' => 'uk-button']])
            ->add('confirm', SubmitType::class, ['label' => 'domain.update.form.confirm', 'attr' => ['class' => 'uk-button uk-button-primary']])
            ->getForm();
        $form->handleRequest($request);
        $formView = $form->createView();

        if ($form->isSubmitted() && $form->isValid()) {

            try {
                $updatedDomain = $this->get('unite.cms.domain_definition_parser')->parse(
                    $form->getData()['definition']
                );
            } catch (\Exception $e) {
                $form->get('definition')->addError(new FormError('Could not parse domain definition JSON.'));
            }

            if (isset($updatedDomain)) {

                $originalDomain = new Domain();
                $originalDomain->setFromEntity($domain);
                $domain->setFromEntity($updatedDomain);
                $violations = $this->get('validator')->validate($domain);

                // If this config is valid and could be saved.
                if ($violations->count() == 0) {

                    $formView = $form->createView();

                    // Case 1: form was submitted but not confirmed yet.
                    if($form->get('submit')->isClicked()) {
                        $formView->children['definition']->vars['disabled'] = true;
                        $formView->children['submit']->vars['disabled'] = true;
                        $formView->children['back']->vars['disabled'] = false;
                        $formView->children['confirm']->vars['disabled'] = false;
                    }

                    // Case 2: form was submitted and confirmed.
                    else if($form->get('confirm')->isClicked()) {
                        $this->getDoctrine()->getManager()->flush();
                        return $this->redirectToRoute('unitecms_core_domain_view', [
                            'organization' => $organization->getIdentifier(),
                            'domain' => $domain->getIdentifier(),
                        ]);
                    }


                } else {
                    $violationMapper = new ViolationMapper();
                    foreach($violations as $violation) {
                        $violationMapper->mapViolation($violation, $form);
                    }

                    $formView = $form->createView();
                }
            }
        }

        return $this->render('UniteCMSCoreBundle:Domain:update.html.twig', ['form' => $formView, 'originalDomain' => $originalDomain, 'updatedDomain' => $updatedDomain]);
    }

    /**
     * @Route("/delete/{domain}")
     * @Method({"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\DomainVoter::DELETE'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @param Request $request
     * @return Response
     */
    public function deleteAction(Organization $organization, Domain $domain, Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, [
                'label' => 'domain.delete.form.submit',
                'attr' => ['class' => 'uk-button-danger']
            ])->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $violations = $this->get('validator')->validate($domain, null, ['DELETE']);

            // If there where violation problems.
            if($violations->count() > 0) {

                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

            // if this domain is save to delete.
            } else {
                $this->getDoctrine()->getManager()->remove($domain);
                $this->getDoctrine()->getManager()->flush();
                return $this->redirectToRoute('unitecms_core_domain_index', [
                    'organization' => $organization->getIdentifier()
                ]);
            }
        }

        $deletedDomain = new Domain();
        $deletedDomain->setDomainMemberTypes([]);

        return $this->render(
            'UniteCMSCoreBundle:Domain:delete.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'deletedDomain' => $deletedDomain,
                'form' => $form->createView(),
            ]
        );
    }
}
