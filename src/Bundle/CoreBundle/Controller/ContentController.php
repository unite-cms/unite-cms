<?php

namespace UniteCMS\CoreBundle\Controller;

use Doctrine\ORM\EntityManager;
use GraphQL\GraphQL;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Entity\ContentLogEntry;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Form\FieldableFormBuilder;
use UniteCMS\CoreBundle\Form\ReferenceType;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\View\ViewTypeManager;

class ContentController extends AbstractController
{
    /**
     * @Route("/{content_type}/{view}", methods={"GET"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\ContentVoter::LIST'), view.getContentType())")
     *
     * @param View $view
     * @return Response
     */
    public function indexAction(View $view, ViewTypeManager $viewTypeManager, CsrfTokenManagerInterface $csrfTokenManager)
    {
        $template = null;
        $parameters = null;

        try {
            $template = $viewTypeManager->getViewType($view->getType())::getTemplate();
            $parameters = $viewTypeManager->getTemplateRenderParameters($view);
            $parameters->setCsrfToken($csrfTokenManager->getToken('fieldable_form'));
        }
        catch (\Symfony\Component\Config\Definition\Exception\Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->render(
            '@UniteCMSCore/Content/index.html.twig',
            [
                'organization' => $view->getContentType()->getDomain()->getOrganization(),
                'domain' => $view->getContentType()->getDomain(),
                'view' => $view,
                'template' => $template,
                'templateParameters' => $parameters,
            ]
        );
    }

    /**
     * @Route("/{content_type}/{view}/create", methods={"GET", "POST"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\ContentVoter::CREATE'), view.getContentType())")
     *
     * @param View $view
     * @param Request $request
     * @param FieldableFormBuilder $fieldableFormBuilder
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function createAction(View $view, Request $request, FieldableFormBuilder $fieldableFormBuilder, ValidatorInterface $validator)
    {
        $content = new Content();

        // Allow to set locale and translation of via GET parameters.
        if ($request->query->has('locale')) {
            $content->setLocale($request->query->get('locale'));
        }

        // Allow to pre-fill content data from query parameter.
        if ($request->query->has('data')) {
            $content->setData($request->query->get('data'));
        }

        if ($request->query->has('translation_of')) {
            $translationOf = $this->getDoctrine()->getRepository('UniteCMSCoreBundle:Content')->find(
                $request->query->get('translation_of')
            );
            if ($translationOf) {
                $content->setTranslationOf($translationOf);
            }
        }

        $form = $fieldableFormBuilder->createForm(
            $view->getContentType(),
            $content,
            ['attr' => ['class' => 'uk-form-vertical']]
        );
        $form->add('submit', SubmitType::class, ['label' => 'content.create.submit']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Assign data to content object.
            $content->setContentType($view->getContentType());
            $fieldableFormBuilder->assignDataToFieldableContent($content, $form->getData());

            // If content errors were found, map them to the form.
            $violations = $validator->validate($content);
            if (count($violations) > 0) {
                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

                // If content is valid.
            } else {
                $this->getDoctrine()->getManager()->persist($content);
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('success', 'Content created.');

                return $this->redirect($this->generateUrl('unitecms_core_content_index', [$view]));
            }
        }

        return $this->render(
            '@UniteCMSCore/Content/create.html.twig',
            [
                'organization' => $view->getContentType()->getDomain()->getOrganization(),
                'domain' => $view->getContentType()->getDomain(),
                'view' => $view,
                'contentType' => $view->getContentType(),
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{content_type}/{view}/update/{content}", methods={"GET", "POST"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @Entity("content")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\ContentVoter::UPDATE'), content)")
     *
     * @param View $view
     * @param Content $content
     * @param Request $request
     * @param FieldableFormBuilder $fieldableFormBuilder
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function updateAction(View $view, Content $content, Request $request, FieldableFormBuilder $fieldableFormBuilder, ValidatorInterface $validator)
    {
        // Otherwise, a user could update content, he_she has access to, from another domain.
        if($content->getContentType() !== $view->getContentType()) {
            throw $this->createNotFoundException();
        }

        $form = $fieldableFormBuilder->createForm(
            $view->getContentType(),
            $content,
            ['attr' => ['class' => 'uk-form-vertical']]
        );
        $form->add('submit', SubmitType::class, ['label' => 'content.update.submit']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Assign data to content object.
            $fieldableFormBuilder->assignDataToFieldableContent($content, $form->getData());

            // If content errors were found, map them to the form.
            $violations = $validator->validate($content);
            if (count($violations) > 0) {
                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

                // If content is valid.
            } else {
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('success', 'Content updated.');

                return $this->redirect($this->generateUrl('unitecms_core_content_index', [$view]));
            }
        }

        return $this->render(
            '@UniteCMSCore/Content/update.html.twig',
            [
                'organization' => $view->getContentType()->getDomain()->getOrganization(),
                'domain' => $view->getContentType()->getDomain(),
                'view' => $view,
                'contentType' => $view->getContentType(),
                'content' => $content,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{content_type}/{view}/preview/generate", methods={"POST"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\ContentVoter::LIST'), view.getContentType())")
     *
     * @param View $view
     * @param Request $request
     * @param FieldableFormBuilder $fieldableFormBuilder
     * @return Response
     */
    public function previewAction(View $view, Request $request, FieldableFormBuilder $fieldableFormBuilder)
    {
        // User must have create or update permissions for this content type.
        $content = new Content($request->query->get('id', null));
        $content->setContentType($view->getContentType());
        $hasAccess = ($this->isGranted(ContentVoter::CREATE, $view->getContentType()) || $this->isGranted(ContentVoter::UPDATE, $content));

        if(!$hasAccess) {
            throw $this->createAccessDeniedException();
        }

        $response = null;

        $form = $fieldableFormBuilder->createForm($view->getContentType(), $content);
        $form->add('submit', SubmitType::class, ['label' => 'content.update.submit']);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            // Assign data to content object.
            $fieldableFormBuilder->assignDataToFieldableContent($content, $form->getData());

            // Create GraphQL Schema
            $domain = $view->getContentType()->getDomain();
            $queryType = ucfirst($view->getContentType()->getIdentifier()) . 'Content';
            $query = $request->query->get('query', 'query{type}');
            $schema = $this->container->get('unite.cms.graphql.schema_type_manager')->createSchema($domain, $queryType);
            $result = GraphQL::executeQuery($schema, $query, $content);
            $response = $this->container->get('jms_serializer')->serialize($result->data, 'json');
        }

        return new Response($response);
    }

    /**
     * @Route("/{content_type}/{view}/delete/{content}", methods={"GET", "POST"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @Entity("content")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\ContentVoter::DELETE'), content)")
     *
     * @param View $view
     * @param Content $content
     * @param Request $request
     * @param ValidatorInterface $validator
     * @return Response
     */
    public function deleteAction(View $view, Content $content, Request $request, ValidatorInterface $validator)
    {
        // Otherwise, a user could update content, he_she has access to, from another domain.
        if($content->getContentType() !== $view->getContentType()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createFormBuilder()
            ->add(
                'submit',
                SubmitType::class,
                ['label' => 'content.delete.submit', 'attr' => ['class' => 'uk-button-danger']]
            )
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // If content errors were found, map them to the form.
            $violations = $validator->validate($content, null, ['DELETE']);
            if (count($violations) > 0) {
                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

                // If content is valid.
            } else {
                $this->getDoctrine()->getManager()->remove($content);
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('success', 'Content deleted.');

                return $this->redirect($this->generateUrl('unitecms_core_content_index', [$view]));
            }
        }

        return $this->render(
            '@UniteCMSCore/Content/delete.html.twig',
            [
                'organization' => $view->getContentType()->getDomain()->getOrganization(),
                'domain' => $view->getContentType()->getDomain(),
                'view' => $view,
                'contentType' => $view->getContentType(),
                'content' => $content,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{content_type}/{view}/delete-definitely/{content}", methods={"GET", "POST"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @param View $view
     * @param string $content
     * @param Request $request
     * @param ValidatorInterface $validator
     * @return Response
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteDefinitelyAction(View $view, string $content, Request $request, ValidatorInterface $validator)
    {

        $em = $this->getDoctrine()->getManager();

        if ($em instanceof EntityManager) {
            $em->getFilters()->disable('gedmo_softdeleteable');
        }

        $content = $em->getRepository('UniteCMSCoreBundle:Content')->findOneBy(
            [
                'id' => $content,
                'contentType' => $view->getContentType(),
            ]
        );

        if ($em instanceof EntityManager) {
            $em->getFilters()->enable('gedmo_softdeleteable');
        }

        if (!$content) {
            throw $this->createNotFoundException();
        }

        if (!$this->isGranted(ContentVoter::UPDATE, $content)) {
            throw $this->createAccessDeniedException();
        }

        if ($content->getDeleted() == null) {
            throw $this->createNotFoundException();
        }

        $form = $this->createFormBuilder()
            ->add(
                'submit',
                SubmitType::class,
                ['label' => 'content.delete_definitely.submit', 'attr' => ['class' => 'uk-button-danger']]
            )
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // If content errors were found, map them to the form.
            $violations = $validator->validate($content, null, ['DELETE']);
            if (count($violations) > 0) {
                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

                // If content is valid.
            } else {

                // Delete content item.
                $em->remove($content);
                $em->flush();

                $this->addFlash('success', 'Content deleted.');

                return $this->redirect($this->generateUrl('unitecms_core_content_index', [$view]));
            }
        }

        return $this->render(
            '@UniteCMSCore/Content/deleteDefinitely.html.twig',
            [
                'organization' => $view->getContentType()->getDomain()->getOrganization(),
                'domain' => $view->getContentType()->getDomain(),
                'view' => $view,
                'contentType' => $view->getContentType(),
                'content' => $content,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{content_type}/{view}/recover/{content}", methods={"GET", "POST"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @param View $view
     * @param string $content
     * @param Request $request
     * @return Response
     */
    public function recoverAction(View $view, string $content, Request $request, ValidatorInterface $validator)
    {
        $em = $this->getDoctrine()->getManager();

        if ($em instanceof EntityManager) {
            $em->getFilters()->disable('gedmo_softdeleteable');
        }

        $content = $em->getRepository('UniteCMSCoreBundle:Content')->findOneBy(
            [
                'id' => $content,
                'contentType' => $view->getContentType(),
            ]
        );

        if ($em instanceof EntityManager) {
            $em->getFilters()->enable('gedmo_softdeleteable');
        }

        if (!$content) {
            throw $this->createNotFoundException();
        }

        if (!$this->isGranted(ContentVoter::UPDATE, $content)) {
            throw $this->createAccessDeniedException();
        }

        if ($content->getDeleted() == null) {
            throw $this->createNotFoundException();
        }

        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, ['label' => 'content.recover.submit'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // If content errors were found, map them to the form.
            $violations = $validator->validate($content, null, ['DELETE']);
            if (count($violations) > 0) {
                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

                // If content is valid.
            } else {
                $content->recoverDeleted();
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', 'Deleted content was restored.');

                return $this->redirect($this->generateUrl('unitecms_core_content_index', [$view]));
            }
        }

        return $this->render(
            '@UniteCMSCore/Content/recover.html.twig',
            [
                'organization' => $view->getContentType()->getDomain()->getOrganization(),
                'domain' => $view->getContentType()->getDomain(),
                'view' => $view,
                'contentType' => $view->getContentType(),
                'content' => $content,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{content_type}/{view}/translations/{content}", methods={"GET"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @Entity("content")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\ContentVoter::UPDATE'), content)")
     *
     * @param View $view
     * @param Content $content
     * @param Request $request
     * @return Response
     */
    public function translationsAction(View $view, Content $content, Request $request)
    {
        // Otherwise, a user could update content, he_she has access to, from another domain.
        if($content->getContentType() !== $view->getContentType()) {
            throw $this->createNotFoundException();
        }

        if (!empty($content->getTranslationOf())) {
            // Check if the translationOf content was soft deleted.
            if (!$this->getDoctrine()->getRepository('UniteCMSCoreBundle:Content')->findOneBy(
                ['id' => $content->getTranslationOf()->getId()]
            )) {
                $this->addFlash(
                    'warning',
                    'You cannot manage translations for this content, because it is a translation of soft-deleted content.'
                );

                return $this->redirect($this->generateUrl('unitecms_core_content_index', [$view]));
            }
        }

        return $this->render(
            '@UniteCMSCore/Content/translations.html.twig',
            [
                'view' => $view,
                'contentType' => $view->getContentType(),
                'content' => $content,
            ]
        );
    }

    /**
     * @Route("/{content_type}/{view}/translations/{content}/add/{locale}", methods={"GET", "POST"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @Entity("content")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\ContentVoter::UPDATE'), content)")
     *
     * @param View $view
     * @param Content $content
     * @param String $locale
     * @param Request $request
     * @param FieldTypeManager $fieldTypeManager
     * @return Response
     */
    public function addTranslationAction(View $view, Content $content, String $locale, Request $request, FieldTypeManager $fieldTypeManager, ValidatorInterface $validator)
    {
        // Otherwise, a user could update content, he_she has access to, from another domain.
        if($content->getContentType() !== $view->getContentType()) {
            throw $this->createNotFoundException();
        }

        $virtualField = new ContentTypeField();
        $virtualField->setTitle('Translation');
        $virtualField->getSettings()->view = $view->getIdentifier();
        $virtualField->getSettings()->content_type = $view->getContentType()->getIdentifier();
        $virtualField->getSettings()->domain = $view->getContentType()->getDomain()->getIdentifier();

        $form = $this->createFormBuilder()
            ->add(
                'translation',
                ReferenceType::class,
                $fieldTypeManager->getFieldType('reference')->getFormOptions($virtualField)
            )
            ->add('submit', SubmitType::class, ['label' => 'content.translations.add_existing.submit'])->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if(!empty($form->getData()['translation'])) {
                $translation = $this->getDoctrine()->getRepository('UniteCMSCoreBundle:Content')->findOneBy(
                    [
                        'id' => $form->getData()['translation']['content'],
                        'translationOf' => null,
                    ]
                );

                if (!$translation) {
                    $form->get('translation')->addError(new FormError($this->get('translator')->trans(
                        'translation_content_not_found',
                        [],
                        'validators'
                    ), 'translation_content_not_found'));
                } else {
                    $content->addTranslation($translation);

                    // If content errors were found, map them to the form.
                    $violations = $validator->validate($content);
                    if (count($violations) > 0) {
                        $violationMapper = new ViolationMapper();
                        foreach ($violations as $violation) {
                            $violationMapper->mapViolation($violation, $form->get('translation'));
                        }

                        // If content is valid.
                    } else {
                        $this->getDoctrine()->getManager()->flush();
                        $this->addFlash('success', 'Translation added.');

                        return $this->redirect($this->generateUrl('unitecms_core_content_translations', [$view, $content]));
                    }
                }
            }
        }

        return $this->render(
            '@UniteCMSCore/Content/addTranslation.html.twig',
            [
                'organization' => $view->getContentType()->getDomain()->getOrganization(),
                'domain' => $view->getContentType()->getDomain(),
                'view' => $view,
                'contentType' => $view->getContentType(),
                'content' => $content,
                'locale' => $locale,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{content_type}/{view}/translations/{content}/remove/{locale}", methods={"GET", "POST"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @Entity("content")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\ContentVoter::UPDATE'), content)")
     *
     * @param View $view
     * @param Content $content
     * @param String $locale
     * @param Request $request
     * @return Response
     */
    public function removeTranslationAction(View $view, Content $content, String $locale, Request $request)
    {
        // Otherwise, a user could update content, he_she has access to, from another domain.
        if($content->getContentType() !== $view->getContentType()) {
            throw $this->createNotFoundException();
        }

        $translations = $content->getTranslations()->filter(
            function (Content $content) use ($locale) {
                return $content->getLocale() == $locale;
            }
        );

        if (empty($translations)) {
            throw $this->createNotFoundException();
        }

        /**
         * @var Content $translation
         */
        $translation = $translations->first();

        $form = $this->createFormBuilder()
            ->add(
                'submit',
                SubmitType::class,
                ['label' => 'content.translations.remove.submit', 'attr' => ['class' => 'uk-button-danger']]
            )
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $translation->setTranslationOf(null);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Translation removed.');

            return $this->redirect($this->generateUrl('unitecms_core_content_translations', [$view, $content]));
        }

        return $this->render(
            '@UniteCMSCore/Content/removeTranslation.html.twig',
            [
                'organization' => $view->getContentType()->getDomain()->getOrganization(),
                'domain' => $view->getContentType()->getDomain(),
                'view' => $view,
                'contentType' => $view->getContentType(),
                'content' => $content,
                'translation' => $translation,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{content_type}/{view}/revisions/{content}", methods={"GET"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @Entity("content")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\ContentVoter::UPDATE'), content)")
     *
     * @param View $view
     * @param Content $content
     * @param Request $request
     * @return Response
     */
    public function revisionsAction(View $view, Content $content, Request $request)
    {
        // Otherwise, a user could update content, he_she has access to, from another domain.
        if($content->getContentType() !== $view->getContentType()) {
            throw $this->createNotFoundException();
        }

        return $this->render(
            '@UniteCMSCore/Content/revisions.html.twig',
            [
                'view' => $view,
                'contentType' => $view->getContentType(),
                'content' => $content,
                'revisions' => $this->getDoctrine()->getManager()->getRepository(ContentLogEntry::class)->getLogEntries($content),
            ]
        );
    }

    /**
     * @Route("/{content_type}/{view}/revisions/{content}/revert/{version}", methods={"GET", "POST"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @Entity("content")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\ContentVoter::UPDATE'), content)")
     *
     * @param View $view
     * @param Content $content
     * @param int $version
     * @param Request $request
     * @return Response
     */
    public function revisionsRevertAction(View $view, Content $content, int $version, Request $request)
    {
        // Otherwise, a user could update content, he_she has access to, from another domain.
        if($content->getContentType() !== $view->getContentType()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, ['label' => 'content.revisions.revert.submit'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $this->getDoctrine()->getManager()->getRepository(ContentLogEntry::class)->revert($content, $version);
            $this->getDoctrine()->getManager()->persist($content);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Content reverted.');

            return $this->redirect($this->generateUrl('unitecms_core_content_revisions', [$view, $content]));
        }

        return $this->render(
            '@UniteCMSCore/Content/revertRevision.html.twig',
            [
                'organization' => $view->getContentType()->getDomain()->getOrganization()->getIdentifier(),
                'domain' => $view->getContentType()->getDomain()->getIdentifier(),
                'view' => $view,
                'contentType' => $view->getContentType(),
                'content' => $content,
                'version' => $version,
                'form' => $form->createView(),
            ]
        );
    }
}
