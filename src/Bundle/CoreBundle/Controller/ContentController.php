<?php

namespace UniteCMS\CoreBundle\Controller;

use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\Translator;
use UniteCMS\CoreBundle\View\ViewTypeInterface;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Form\WebComponentType;
use UniteCMS\CoreBundle\Security\ContentVoter;

class ContentController extends Controller
{
    /**
     * @Route("/{content_type}/{view}")
     * @Method({"GET"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\ContentVoter::LIST'), view.getContentType())")
     *
     * @param View $view
     * @return Response
     */
    public function indexAction(View $view)
    {
        return $this->render(
            'UniteCMSCoreBundle:Content:index.html.twig',
            [
                'organization' => $view->getContentType()->getDomain()->getOrganization(),
                'domain' => $view->getContentType()->getDomain(),
                'view' => $view,
                'template' => $this->get('unite.cms.view_type_manager')->getViewType(
                    $view->getType()
                )::getTemplate(),
                'templateParameters' => $this->get('unite.cms.view_type_manager')->getTemplateRenderParameters(
                    $view
                )->setCsrfToken($this->get('security.csrf.token_manager')->getToken('fieldable_form')),
            ]
        );
    }

    /**
     * @Route("/{content_type}/{view}/create")
     * @Method({"GET", "POST"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\ContentVoter::CREATE'), view.getContentType())")
     *
     * @param View $view
     * @param Request $request
     * @return Response
     */
    public function createAction(View $view, Request $request)
    {
        $content = new Content();

        // Allow to set locale and translation of via GET parameters.
        if ($request->query->has('locale')) {
            $content->setLocale($request->query->get('locale'));
        }

        if ($request->query->has('translation_of')) {
            $translationOf = $this->getDoctrine()->getRepository('UniteCMSCoreBundle:Content')->find(
                $request->query->get('translation_of')
            );
            if ($translationOf) {
                $content->setTranslationOf($translationOf);
            }
        }

        $form = $this->get('unite.cms.fieldable_form_builder')->createForm(
            $view->getContentType(),
            $content,
            ['attr' => ['class' => 'uk-form-vertical']]
        );
        $form->add('submit', SubmitType::class, ['label' => 'content.create.submit']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if (isset($data['locale'])) {
                $content->setLocale($data['locale']);
                unset($data['locale']);
            }

            $content
                ->setContentType($view->getContentType())
                ->setData($data);

            // If content errors were found, map them to the form.
            $violations = $this->get('validator')->validate($content);
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

                return $this->redirectToRoute(
                    'unitecms_core_content_index',
                    [
                        'organization' => $view->getContentType()->getDomain()->getOrganization()->getIdentifier(),
                        'domain' => $view->getContentType()->getDomain()->getIdentifier(),
                        'content_type' => $view->getContentType()->getIdentifier(),
                        'view' => $view->getIdentifier(),
                    ]
                );
            }
        }

        return $this->render(
            'UniteCMSCoreBundle:Content:create.html.twig',
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
     * @Route("/{content_type}/{view}/update/{content}")
     * @Method({"GET", "POST"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @Entity("content")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\ContentVoter::UPDATE'), content)")
     *
     * @param View $view
     * @param Content $content
     * @param Request $request
     * @return Response
     */
    public function updateAction(View $view, Content $content, Request $request)
    {
        $form = $this->get('unite.cms.fieldable_form_builder')->createForm(
            $view->getContentType(),
            $content,
            ['attr' => ['class' => 'uk-form-vertical']]
        );
        $form->add('submit', SubmitType::class, ['label' => 'content.update.submit']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            if (isset($data['locale'])) {
                $content->setLocale($data['locale']);
                unset($data['locale']);
            }

            $content->setData($data);

            // If content errors were found, map them to the form.
            $violations = $this->get('validator')->validate($content);
            if (count($violations) > 0) {
                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

                // If content is valid.
            } else {
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('success', 'Content updated.');

                return $this->redirectToRoute(
                    'unitecms_core_content_index',
                    [
                        'organization' => $view->getContentType()->getDomain()->getOrganization()->getIdentifier(),
                        'domain' => $view->getContentType()->getDomain()->getIdentifier(),
                        'content_type' => $view->getContentType()->getIdentifier(),
                        'view' => $view->getIdentifier(),
                    ]
                );
            }
        }

        return $this->render(
            'UniteCMSCoreBundle:Content:update.html.twig',
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
     * @Route("/{content_type}/{view}/delete/{content}")
     * @Method({"GET", "POST"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @Entity("content")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\ContentVoter::DELETE'), content)")
     *
     * @param View $view
     * @param Content $content
     * @param Request $request
     * @return Response
     */
    public function deleteAction(View $view, Content $content, Request $request)
    {

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
            $violations = $this->get('validator')->validate($content, null, ['DELETE']);
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

                return $this->redirectToRoute(
                    'unitecms_core_content_index',
                    [
                        'organization' => $view->getContentType()->getDomain()->getOrganization()->getIdentifier(),
                        'domain' => $view->getContentType()->getDomain()->getIdentifier(),
                        'content_type' => $view->getContentType()->getIdentifier(),
                        'view' => $view->getIdentifier(),
                    ]
                );
            }
        }

        return $this->render(
            'UniteCMSCoreBundle:Content:delete.html.twig',
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
     * @Route("/{content_type}/{view}/delete-definitely/{content}")
     * @Method({"GET", "POST"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @param View $view
     * @param string $content
     * @param Request $request
     * @return Response
     */
    public function deleteDefinitelyAction(View $view, string $content, Request $request)
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
            $violations = $this->get('validator')->validate($content, null, ['DELETE']);
            if (count($violations) > 0) {
                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

                // If content is valid.
            } else {

                // Get log entries and delete them.
                foreach ($em->getRepository('GedmoLoggable:LogEntry')->getLogEntries($content) as $logEntry) {
                    $em->remove($logEntry);
                }

                // Delete content item.
                $em->remove($content);
                $em->flush();

                $this->addFlash('success', 'Content deleted.');

                return $this->redirectToRoute(
                    'unitecms_core_content_index',
                    [
                        'organization' => $view->getContentType()->getDomain()->getOrganization()->getIdentifier(),
                        'domain' => $view->getContentType()->getDomain()->getIdentifier(),
                        'content_type' => $view->getContentType()->getIdentifier(),
                        'view' => $view->getIdentifier(),
                    ]
                );
            }
        }

        return $this->render(
            'UniteCMSCoreBundle:Content:deleteDefinitely.html.twig',
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
     * @Route("/{content_type}/{view}/recover/{content}")
     * @Method({"GET", "POST"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @param View $view
     * @param string $content
     * @param Request $request
     * @return Response
     */
    public function recoverAction(View $view, string $content, Request $request)
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
            $violations = $this->get('validator')->validate($content, null, ['DELETE']);
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

                return $this->redirectToRoute(
                    'unitecms_core_content_index',
                    [
                        'organization' => $view->getContentType()->getDomain()->getOrganization()->getIdentifier(),
                        'domain' => $view->getContentType()->getDomain()->getIdentifier(),
                        'content_type' => $view->getContentType()->getIdentifier(),
                        'view' => $view->getIdentifier(),
                    ]
                );
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
     * @Route("/{content_type}/{view}/translations/{content}")
     * @Method({"GET", "POST"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @Entity("content")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\ContentVoter::UPDATE'), content)")
     *
     * @param View $view
     * @param Content $content
     * @param Request $request
     * @return Response
     */
    public function translationsAction(View $view, Content $content, Request $request)
    {

        if (!empty($content->getTranslationOf())) {
            // Check if the translationOf content was soft deleted.
            if (!$this->getDoctrine()->getRepository('UniteCMSCoreBundle:Content')->findOneBy(
                ['id' => $content->getTranslationOf()->getId()]
            )) {
                $this->addFlash(
                    'warning',
                    'You cannot manage translations for this content, because it is a translation of soft-deleted content.'
                );

                return $this->redirectToRoute(
                    'unitecms_core_content_index',
                    [
                        'organization' => $view->getContentType()->getDomain()->getOrganization()->getIdentifier(),
                        'domain' => $view->getContentType()->getDomain()->getIdentifier(),
                        'content_type' => $view->getContentType()->getIdentifier(),
                        'view' => $view->getIdentifier(),
                    ]
                );
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
     * @Route("/{content_type}/{view}/translations/{content}/add/{locale}")
     * @Method({"GET", "POST"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @Entity("content")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\ContentVoter::UPDATE'), content)")
     *
     * @param View $view
     * @param Content $content
     * @param String $locale
     * @param Request $request
     * @return Response
     */
    public function addTranslationAction(View $view, Content $content, String $locale, Request $request)
    {

        $form = $this->createFormBuilder()
            ->add(
                'translation',
                WebComponentType::class,
                [
                    'tag' => 'unite-cms-core-reference-field',
                    'empty_data' => [
                        'domain' => $view->getContentType()->getDomain()->getIdentifier(),
                        'content_type' => $view->getContentType()->getIdentifier(),
                    ],
                    'attr' => [
                        'base-url' => '/'.$view->getContentType()->getDomain()->getOrganization().'/',
                        'content-label' => '#{id}',
                        'modal-html' => $this->render(
                            $this->get('unite.cms.view_type_manager')->getViewType($view->getType())::getTemplate(),
                            [
                                'view' => $view,
                                'parameters' => $this->get('unite.cms.view_type_manager')
                                    ->getTemplateRenderParameters($view, ViewTypeInterface::SELECT_MODE_SINGLE)
                                    ->setCsrfToken(
                                        $this->get('security.csrf.token_manager')->getToken('fieldable_form')
                                    ),
                            ]
                        ),
                    ],
                ]
            )
            ->add('submit', SubmitType::class, ['label' => 'content.translations.add_existing.submit'])->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($form->getData() as $key => $translation_identifier) {
                if (!empty($translation_identifier['content'])) {
                    $translation = $this->getDoctrine()->getRepository('UniteCMSCoreBundle:Content')->findOneBy(
                        [
                            'id' => $translation_identifier['content'],
                            'translationOf' => null,
                        ]
                    );
                    if (!$translation) {

                        $form->addError(
                            new FormError(
                                'validation.content_not_found',
                                'validation.content_not_found'
                            )
                        );

                    } else {
                        $content->addTranslation($translation);

                        // If content errors were found, map them to the form.
                        $violations = $this->get('validator')->validate($content);
                        if (count($violations) > 0) {
                            $violationMapper = new ViolationMapper();
                            foreach ($violations as $violation) {
                                $violationMapper->mapViolation($violation, $form);
                            }

                            // If content is valid.
                        } else {
                            $this->getDoctrine()->getManager()->flush();
                            $this->addFlash('success', 'Translation added.');

                            return $this->redirectToRoute(
                                'unitecms_core_content_translations',
                                [
                                    'organization' => $view->getContentType()->getDomain()->getOrganization(
                                    )->getIdentifier(),
                                    'domain' => $view->getContentType()->getDomain()->getIdentifier(),
                                    'content_type' => $view->getContentType()->getIdentifier(),
                                    'view' => $view->getIdentifier(),
                                    'content' => $content->getId(),
                                ]
                            );
                        }
                    }
                }
            }
        }

        return $this->render(
            'UniteCMSCoreBundle:Content:addTranslation.html.twig',
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
     * @Route("/{content_type}/{view}/translations/{content}/remove/{locale}")
     * @Method({"GET", "POST"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @Entity("content")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\ContentVoter::UPDATE'), content)")
     *
     * @param View $view
     * @param Content $content
     * @param String $locale
     * @param Request $request
     * @return Response
     */
    public function removeTranslationAction(View $view, Content $content, String $locale, Request $request)
    {
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

            return $this->redirectToRoute(
                'unitecms_core_content_translations',
                [
                    'organization' => $view->getContentType()->getDomain()->getOrganization()->getIdentifier(),
                    'domain' => $view->getContentType()->getDomain()->getIdentifier(),
                    'content_type' => $view->getContentType()->getIdentifier(),
                    'view' => $view->getIdentifier(),
                    'content' => $content->getId(),
                ]
            );
        }

        return $this->render(
            'UniteCMSCoreBundle:Content:removeTranslation.html.twig',
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
     * @Route("/{content_type}/{view}/revisions/{content}")
     * @Method({"GET"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @Entity("content")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\ContentVoter::UPDATE'), content)")
     *
     * @param View $view
     * @param Content $content
     * @param Request $request
     * @return Response
     */
    public function revisionsAction(View $view, Content $content, Request $request)
    {
        return $this->render(
            '@UniteCMSCore/Content/revisions.html.twig',
            [
                'view' => $view,
                'contentType' => $view->getContentType(),
                'content' => $content,
                'revisions' => $this->getDoctrine()->getManager()->getRepository(
                    'GedmoLoggable:LogEntry'
                )->getLogEntries($content),
            ]
        );
    }

    /**
     * @Route("/{content_type}/{view}/revisions/{content}/revert/{version}")
     * @Method({"GET", "POST"})
     * @Entity("view", expr="repository.findByIdentifiers(organization, domain, content_type, view)")
     * @Entity("content")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\ContentVoter::UPDATE'), content)")
     *
     * @param View $view
     * @param Content $content
     * @param int $version
     * @param Request $request
     * @return Response
     */
    public function revisionsRevertAction(View $view, Content $content, int $version, Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, ['label' => 'content.revisions.revert.submit'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $this->getDoctrine()->getManager()->getRepository('GedmoLoggable:LogEntry')->revert($content, $version);
            $this->getDoctrine()->getManager()->persist($content);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Content reverted.');

            return $this->redirectToRoute(
                'unitecms_core_content_revisions',
                [
                    'organization' => $view->getContentType()->getDomain()->getOrganization()->getIdentifier(),
                    'domain' => $view->getContentType()->getDomain()->getIdentifier(),
                    'content_type' => $view->getContentType()->getIdentifier(),
                    'view' => $view->getIdentifier(),
                    'content' => $content->getId(),
                ]
            );
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
