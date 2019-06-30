<?php

namespace UniteCMS\CoreBundle\Controller;

use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Exception\InvalidDomainConfigurationException;
use UniteCMS\CoreBundle\Form\WebComponentType;
use UniteCMS\CoreBundle\Security\Voter\OrganizationVoter;
use UniteCMS\CoreBundle\Service\DomainConfigManager;

class DomainController extends AbstractController
{
    /**
     * @Route("/", methods={"GET"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\OrganizationVoter::VIEW'), organization)")
     *
     * @param Organization $organization
     * @param DomainConfigManager $domainConfigManager
     * @param LoggerInterface $logger
     * @return Response
     */
    public function indexAction(Organization $organization, DomainConfigManager $domainConfigManager, LoggerInterface $logger)
    {
        $domains = $organization->getDomains();

        // Load new domain configurations from the filesystem that are not already in the organization.
        if($this->isGranted(OrganizationVoter::UPDATE, $organization)) {
            try {
                $missingIdentifiers = array_diff(
                    $domainConfigManager->listConfig($organization),
                    $organization->getDomains()->map(function(Domain $domain){ return $domain->getIdentifier(); })->toArray()
                );
                $organization->setMissingDomainConfigIdentifiers($missingIdentifiers);
            } catch (\Exception $e) {
                $logger->error($e->getMessage(), ['context' => $e]);
                $this->addFlash('warning', 'Could not load (potential new) configurations from the filesystem.');
            }
        }

        return $this->render('@UniteCMSCore/Domain/index.html.twig', [
            'organization' => $organization,
            'domains' => $domains,
        ]);
    }

    /**
     * @Route("/create", methods={"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\OrganizationVoter::UPDATE'), organization)")
     *
     * @param Organization $organization
     * @param Request $request
     * @param DomainConfigManager $domainConfigManager
     * @param LoggerInterface $logger
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function createAction(Organization $organization, Request $request, DomainConfigManager $domainConfigManager, LoggerInterface $logger, ValidatorInterface $validator)
    {
        $domain = new Domain();
        $import_config = $request->query->has('import');

        if($import_config) {
            try {
                $domain->setOrganization($organization)->setIdentifier($request->query->get('import'));
                $domainConfigManager->loadConfig($domain, true);
            } catch (\Exception $e) {
                $organization->getDomains()->removeElement($domain);
                $logger->error($e->getMessage(), ['context' => $e]);
                $this->addFlash('danger', 'Could not load configuration from the filesystem.');
                return $this->redirectToRoute('unitecms_core_domain_index', [$organization]);
            }
        } else {
            $domain->setTitle('Untitled Domain')->setIdentifier('untitled');
            $domain->setConfig($domainConfigManager->serialize($domain));
        }

        $form = $this->createFormBuilder([
            'domain' => $domain->getConfig(),
        ])
            ->add('domain', WebComponentType::class, ['tag' => 'unite-cms-core-domaineditor', 'compound' => false])
            ->add('submit', SubmitType::class, ['label' => 'domain.create.form.submit', 'attr' => ['class' => 'uk-button uk-button-primary']])
        ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // First unset temporary domain
            $organization->getDomains()->removeElement($domain);
            $domain = null;

            try {
                $domain = $domainConfigManager->parse($form->getData()['domain']);
                $domain->setConfig($form->getData()['domain']);

            } catch (\Exception $e) {
                $form->get('domain')->addError(new FormError('Could not parse domain definition JSON.'));
            }

            if ($domain) {
                $domain->setOrganization($organization);

                $errors = $validator->validate($domain);

                if ($errors->count() == 0) {
                    $this->getDoctrine()->getManager()->persist($domain);
                    $this->getDoctrine()->getManager()->flush();

                    return $this->redirect($this->generateUrl('unitecms_core_domain_view', [$domain]));
                } else {
                    foreach ($errors as $error) {
                        $this->addFlash('danger', $error->getPropertyPath().': '.$error->getMessage());
                    }
                }
            }
        }

        return $this->render('@UniteCMSCore/Domain/create.html.twig', [
            'organization' => $organization,
            'domain' => $domain,
            'form' => $form->createView(),
            'import_config' => $import_config,
        ]);
    }

    /**
     * @Route("/view/{domain}", methods={"GET"})
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
            '@UniteCMSCore/Domain/view.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'contentTypes' => $contentTypes,
                'settingTypes' => $settingTypes,
            ]
        );
    }

    /**
     * @Route("/update/{domain}", methods={"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\DomainVoter::UPDATE'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @param Request $request
     * @param DomainConfigManager $domainConfigManager
     * @param LoggerInterface $logger
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function updateAction(Organization $organization, Domain $domain, Request $request, DomainConfigManager $domainConfigManager, LoggerInterface $logger, ValidatorInterface $validator)
    {
        $outOfSyncPersistedConfig = null;
        $configNotInFilesystem = false;

        // First check if the config file exists in the filesystem.
        try {
            if ($domainConfigManager->configExists($domain)) {

                // Check if filesystem config is equal to database config.
                $originalDomainConfig = $domainConfigManager->serialize($domain);
                $domainConfigManager->loadConfig($domain);
                $fileSystemDomainConfig = $domainConfigManager->serialize($domain);

                // Check if config is different from domain entity.
                if ($originalDomainConfig !== $fileSystemDomainConfig) {
                    $outOfSyncPersistedConfig = $originalDomainConfig;
                }

            // If the file does not exist, we use the config from database.
            } else {

                // Force update of the domain config.
                if(empty($domain->getConfig())) {
                    $domain->setConfig($domainConfigManager->serialize($domain));
                }

                $domain->setConfigChanged();
                $configNotInFilesystem = true;
            }
        } catch (InvalidDomainConfigurationException $e) {
            $logger->error($e->getMessage(), ['context' => $e]);
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('unitecms_core_domain_index', [$organization]);

        } catch (\Exception $e) {
            $logger->error($e->getMessage(), ['context' => $e]);
            $this->addFlash('danger', 'Cannot load config file.');
            return $this->redirectToRoute('unitecms_core_domain_index', [$organization]);
        }

        $originalDomain = null;
        $updatedDomain = null;

        $form = $this->createFormBuilder(['domain' => $domain->getConfig()])
        ->add('domain', WebComponentType::class, [
            'compound' => false,
            'tag' => 'unite-cms-core-domaineditor',
            'error_bubbling' => true,
        ])
        ->add('submit', SubmitType::class, ['label' => 'domain.update.form.submit', 'attr' => ['class' => 'uk-button uk-button-primary']])
        ->add('back', SubmitType::class, ['label' => 'domain.update.form.back', 'attr' => ['class' => 'uk-button']])
        ->add('confirm', SubmitType::class, ['label' => 'domain.update.form.confirm', 'attr' => ['class' => 'uk-button uk-button-primary']])
        ->getForm();

        $form->handleRequest($request);
        $formView = $form->createView();

        if ($form->isSubmitted() && $form->isValid()) {

            try {
                $updatedDomain = $domainConfigManager->parse($form->getData()['domain']);
                $updatedDomain->setConfig($form->getData()['domain']);
            } catch (\Exception $e) {
                $form->get('domain')->addError(new FormError('Could not parse domain definition JSON.'));
                $formView = $form->createView();
            }

            if (isset($updatedDomain)) {

                // In order to avoid persistence conflicts, we create a new domain from serialized domain.
                $originalDomain = $domainConfigManager->parse($domainConfigManager->serialize($domain));
                $domain->setFromEntity($updatedDomain);
                $violations = $validator->validate($domain);

                // If this config is valid and could be saved.
                if ($violations->count() == 0) {

                    $formView = $form->createView();

                    // Case 1: form was submitted but not confirmed yet.
                    if($form->get('submit')->isClicked()) {
                        $formView->children['domain']->vars['disabled'] = true;
                        $formView->children['submit']->vars['disabled'] = true;
                        $formView->children['back']->vars['disabled'] = false;
                        $formView->children['confirm']->vars['disabled'] = false;
                    }

                    // Case 2: form was submitted and confirmed.
                    else if($form->get('confirm')->isClicked()) {

                        $this->getDoctrine()->getManager()->flush();

                        return $this->redirect($this->generateUrl('unitecms_core_domain_view', [$domain]));
                    }


                } else {
                    $violationMapper = new ViolationMapper();

                    /**
                     * @var ConstraintViolation[] $violations
                     */
                    foreach($violations as $violation) {
                        $violationMapper->mapViolation(new ConstraintViolation(
                            $violation->getPropertyPath() .': '.$violation->getMessage(),
                            $violation->getMessageTemplate(),
                            $violation->getParameters(),
                            $violation->getRoot(),
                            null,
                            $violation->getInvalidValue(),
                            $violation->getPlural(),
                            $violation->getCode(),
                            $violation->getConstraint(),
                            $violation->getCause()
                        ), $form);
                    }

                    $domain->setFromEntity($originalDomain);
                    $formView = $form->createView();
                }
            }
        }
        else {
            if($outOfSyncPersistedConfig) {
                $this->addFlash('warning', 'The filesystem config of this domain is different from the current config. If you click "validate" and "Save changes", the updated domain config will be persisted to the database.');
            }

            if($configNotInFilesystem) {
                $this->addFlash('warning', 'This domain configuration comes from the database and not from the file system at the moment. Please save this domain to create a config file in the filesystem.');
            }
        }

        return $this->render('@UniteCMSCore/Domain/update.html.twig', [
            'form' => $formView,
            'originalDomain' => $originalDomain,
            'updatedDomain' => $updatedDomain,
            'originalDomainSerialized' => $originalDomain ? $domainConfigManager->serialize($originalDomain) : '',
            'updatedDomainSerialized' => $updatedDomain ? $domainConfigManager->serialize($updatedDomain) : '',
        ]);
    }

    /**
     * @Route("/delete/{domain}", methods={"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\DomainVoter::DELETE'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @param Request $request
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function deleteAction(Organization $organization, Domain $domain, Request $request, ValidatorInterface $validator)
    {
        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, [
                'label' => 'domain.delete.form.submit',
                'attr' => ['class' => 'uk-button-danger']
            ])->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $violations = $validator->validate($domain, null, ['DELETE']);

            // If there where violation problems.
            if($violations->count() > 0) {

                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

            // if this domain is save to delete.
            } else {
                $this->getDoctrine()->getManager()->remove($domain);
                $this->getDoctrine()->getManager()->flush($domain);
                return $this->redirect($this->generateUrl('unitecms_core_domain_index', [$organization]));
            }
        }

        $deletedDomain = new Domain();
        $deletedDomain->setDomainMemberTypes([]);

        return $this->render(
            '@UniteCMSCore/Domain/delete.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'deletedDomain' => $deletedDomain,
                'form' => $form->createView(),
            ]
        );
    }
}
