<?php

namespace UniteCMS\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormError;
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
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\OrganizationVoter::VIEW'), organization)")
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
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\OrganizationVoter::UPDATE'), organization)")
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
            ->add('submit', SubmitType::class, ['attr' => ['class' => 'uk-button uk-button-primary']]);
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
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\DomainVoter::VIEW'), domain)")
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
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\DomainVoter::UPDATE'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @param Request $request
     * @return Response
     */
    public function updateAction(Organization $organization, Domain $domain, Request $request)
    {
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
            ->add('submit', SubmitType::class, ['attr' => ['class' => 'uk-button uk-button-primary']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {
                $updatedDomain = $this->get('unite.cms.domain_definition_parser')->parse(
                    $form->getData()['definition']
                );
            } catch (\Exception $e) {
                $form->get('definition')->addError(new FormError('Could not parse domain definition JSON.'));
            }

            if (isset($updatedDomain)) {
                $errors = new ConstraintViolationList();

                // For all content types that would be deleted, check validation
                foreach ($domain->getContentTypesDiff($updatedDomain, true) as $contentType) {
                    $errors->addAll($this->get('validator')->validate($contentType, null, ['DELETE']));
                }

                // For all setting types that would be deleted, check validation
                foreach ($domain->getSettingTypesDiff($updatedDomain, true) as $settingType) {
                    $errors->addAll($this->get('validator')->validate($settingType, null, ['DELETE']));
                }

                $domain->setFromEntity($updatedDomain);
                $errors->addAll($this->get('validator')->validate($domain));

                if ($errors->count() == 0) {
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

        return $this->render('UniteCMSCoreBundle:Domain:update.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/delete/{domain}")
     * @Method({"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\DomainVoter::DELETE'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @param Request $request
     * @return Response
     */
    public function deleteAction(Organization $organization, Domain $domain, Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, ['label' => 'Delete'])->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->get('validator')->validate($domain, null, ['DELETE'])->count() == 0) {
                $this->getDoctrine()->getManager()->remove($domain);
                $this->getDoctrine()->getManager()->flush();

                return $this->redirectToRoute(
                    'unitecms_core_domain_index',
                    [
                        'organization' => $organization->getIdentifier(),
                    ]
                );
            } else {
                $form->addError(new FormError('Domain could not be deleted.'));
            }
        }

        return $this->render(
            'UniteCMSCoreBundle:Domain:delete.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'form' => $form->createView(),
            ]
        );
    }
}
