<?php

namespace UniteCMS\CoreBundle\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Router;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\ParamConverter\IdentifierNormalizer;

class OrganizationApiKeyController extends AbstractController
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
        $apiKeys = $paginator->paginate($organization->getApiKeys(),
            $request->query->getInt('page_keys', 1),
            10,
            ['pageParameterName' => 'page_keys', 'sortDirectionParameterName' => 'sort_keys']
        );

        return $this->render(
            '@UniteCMSCore/Organization/ApiKey/index.html.twig',
            [
                'organization' => $organization,
                'apiKeys' => $apiKeys,
            ]
        );
    }

    /**
     * @Route("/create", methods={"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\OrganizationVoter::UPDATE'), organization)")
     *
     * @param Organization $organization
     * @param Request $request
     * @return Response
     */
    public function createAction(Organization $organization, Request $request)
    {
        $apiKey = new ApiKey();
        $apiKey->setOrganization($organization);
        $apiKey->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='));

        $form = $this->createFormBuilder($apiKey)
            ->add(
                'name',
                TextType::class,
                ['label' => 'organization.api_key.create.form.name', 'required' => true]
            )
            ->add(
                'origin',
                TextType::class,
                ['label' => 'organization.api_key.create.form.origin', 'required' => true]
            )
            ->add('submit', SubmitType::class, ['label' => 'organization.api_key.create.form.submit'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->persist($apiKey);
            $this->getDoctrine()->getManager()->flush();
            return $this->redirect($this->generateUrl('unitecms_core_organizationapikey_index', [$organization]));
        }

        return $this->render(
            '@UniteCMSCore/Organization/ApiKey/create.html.twig',
            [
                'organization' => $organization,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/update/{apiKey}", methods={"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\OrganizationVoter::UPDATE'), organization)")
     * @ParamConverter("apiKey")
     *
     * @param Organization $organization
     * @param ApiKey $apiKey
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateAction(Organization $organization, ApiKey $apiKey, Request $request)
    {
        $form = $this->createFormBuilder($apiKey)
            ->add(
                'name',
                TextType::class,
                ['label' => 'organization.api_key.update.form.name', 'required' => true]
            )
            ->add(
                'origin',
                TextType::class,
                ['label' => 'organization.api_key.create.form.origin', 'required' => true]
            )
            ->add('submit', SubmitType::class, ['label' => 'organization.api_key.update.form.submit'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirect($this->generateUrl('unitecms_core_organizationapikey_index', [$organization]));
        }

        return $this->render(
            '@UniteCMSCore/Organization/ApiKey/update.html.twig',
            [
                'organization' => $organization,
                'form' => $form->createView(),
                'apiKey' => $apiKey,
            ]
        );
    }

    /**
     * @Route("/delete/{apiKey}", methods={"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\OrganizationVoter::UPDATE'), organization)")
     * @ParamConverter("apiKey")
     *
     * @param Organization $organization
     * @param ApiKey $apiKey
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Organization $organization, ApiKey $apiKey, Request $request)
    {
        $form = $this->createFormBuilder()
            ->add(
                'submit',
                SubmitType::class,
                ['label' => 'organization.api_key.delete.form.submit', 'attr' => ['class' => 'uk-button-danger']]
            )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->remove($apiKey);
            $this->getDoctrine()->getManager()->flush();
            return $this->redirect($this->generateUrl('unitecms_core_organizationapikey_index', [$organization]));
        }

        return $this->render(
            '@UniteCMSCore/Organization/ApiKey/delete.html.twig',
            [
                'organization' => $organization,
                'form' => $form->createView(),
                'apiKey' => $apiKey,
            ]
        );
    }
}
