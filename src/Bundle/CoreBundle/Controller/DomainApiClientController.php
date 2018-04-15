<?php

namespace UniteCMS\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UniteCMS\CoreBundle\Entity\ApiClient;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;

class DomainApiClientController extends Controller
{
    /**
     * @Route("/")
     * @Method({"GET"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\DomainVoter::UPDATE'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @return Response
     */
    public function indexAction(Organization $organization, Domain $domain)
    {
        $clients = $this->get('knp_paginator')->paginate($domain->getApiClients());

        return $this->render(
            'UniteCMSCoreBundle:Domain/ApiClient:index.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'clients' => $clients,
            ]
        );
    }

    /**
     * @Route("/create")
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
    public function createAction(Organization $organization, Domain $domain, Request $request)
    {
        $apiClient = new ApiClient();
        $apiClient->setDomain($domain);
        $apiClient->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='));

        $form = $this->createFormBuilder($apiClient)
            ->add(
                'name',
                TextType::class,
                ['label' => 'domain.api_client.create.form.name', 'required' => true]
            )
            ->add(
                'roles',
                ChoiceType::class,
                [
                    'label' => 'domain.api_client.create.form.roles',
                    'multiple' => true,
                    'choices' => $domain->getAvailableRolesAsOptions(true),
                ]
            )
            ->add('submit', SubmitType::class, ['label' => 'domain.api_client.create.form.submit'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->persist($apiClient);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute(
                'unitecms_core_domainapiclient_index',
                [
                    'organization' => $organization->getIdentifier(),
                    'domain' => $domain->getIdentifier(),
                ]
            );
        }

        return $this->render(
            'UniteCMSCoreBundle:Domain/ApiClient:create.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/update/{client}")
     * @Method({"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @ParamConverter("client")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\DomainVoter::UPDATE'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @param ApiClient $client
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateAction(Organization $organization, Domain $domain, ApiClient $client, Request $request)
    {
        $form = $this->createFormBuilder($client)
            ->add(
                'name',
                TextType::class,
                ['label' => 'domain.api_client.update.form.name', 'required' => true]
            )
            ->add(
                'roles',
                ChoiceType::class,
                [
                    'label' => 'domain.api_client.update.form.roles',
                    'multiple' => true,
                    'choices' => $domain->getAvailableRolesAsOptions(true),
                ]
            )
            ->add('submit', SubmitType::class, ['label' => 'domain.api_client.update.form.submit'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute(
                'unitecms_core_domainuser_index',
                [
                    'organization' => $organization->getIdentifier(),
                    'domain' => $domain->getIdentifier(),
                ]
            );
        }

        return $this->render(
            'UniteCMSCoreBundle:Domain/ApiClient:update.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'form' => $form->createView(),
                'client' => $client,
            ]
        );
    }

    /**
     * @Route("/delete/{client}")
     * @Method({"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @ParamConverter("member")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\DomainVoter::UPDATE'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @param ApiClient $client
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Organization $organization, Domain $domain, ApiClient $client, Request $request)
    {
        $form = $this->createFormBuilder()
            ->add(
                'submit',
                SubmitType::class,
                ['label' => 'domain.api_client.delete.form.submit', 'attr' => ['class' => 'uk-button-danger']]
            )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->remove($client);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute(
                'unitecms_core_domainapiclient_index',
                [
                    'organization' => $organization->getIdentifier(),
                    'domain' => $domain->getIdentifier(),
                ]
            );
        }

        return $this->render(
            'UniteCMSCoreBundle:Domain/ApiClient:delete.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'form' => $form->createView(),
                'client' => $client,
            ]
        );
    }
}
