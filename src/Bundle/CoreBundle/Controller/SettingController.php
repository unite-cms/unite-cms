<?php

namespace UniteCMS\CoreBundle\Controller;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Router;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Entity\ContentLogEntry;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Form\FieldableFormBuilder;
use UniteCMS\CoreBundle\ParamConverter\IdentifierNormalizer;
use UniteCMS\CoreBundle\Service\FieldableContentManager;

class SettingController extends AbstractController
{
    /**
     * @Route("/{setting_type}/{locale}", defaults={"locale"=null}, methods={"GET", "POST"})
     * @Entity("settingType", expr="repository.findByIdentifiers(organization, domain, setting_type)")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\SettingVoter::UPDATE'), settingType)")
     *
     * @param SettingType $settingType
     * @param null|string $locale
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(SettingType $settingType, $locale, Request $request, FieldableFormBuilder $fieldableFormBuilder, ValidatorInterface $validator)
    {
        if (!$locale && !empty($settingType->getLocales())) {
            $locale = $settingType->getLocales()[0];
        }

        $setting = $settingType->getSetting($locale);

        if (!$setting) {
            throw $this->createNotFoundException();
        }

        // If this setting was not saved before, do now
        if (!$this->getDoctrine()->getManager()->contains($setting)) {
            $this->getDoctrine()->getManager()->persist($setting);
            $this->getDoctrine()->getManager()->flush();
        }

        $form = $fieldableFormBuilder->createForm(
            $settingType,
            $setting,
            ['attr' => ['class' => 'uk-form-vertical']]
        );
        $form->add('submit', SubmitType::class, ['label' => 'setting.update.submit']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Assign data to content object.
            $fieldableFormBuilder->assignDataToFieldableContent($setting, $form->getData());

            // If content errors were found, map them to the form.
            $violations = $validator->validate($setting);
            if (count($violations) > 0) {
                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

                // If content is valid.
            } else {
                $this->getDoctrine()->getManager()->flush();

                // On form submit, reload the current page so setting gets reloaded from db.
                return $this->redirectToRoute('unitecms_core_setting_index', ['setting' => $setting, 'locale' => $setting->getLocale()]);
            }
        }

        return $this->render(
            '@UniteCMSCore/Setting/index.html.twig',
            [
                'organization' => $settingType->getDomain()->getOrganization(),
                'domain' => $settingType->getDomain(),
                'settingType' => $settingType,
                'setting' => $setting,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{setting_type}/preview/generate", methods={"POST"})
     * @Entity("settingType", expr="repository.findByIdentifiers(organization, domain, setting_type)")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\SettingVoter::UPDATE'), settingType)")
     *
     * @param SettingType $settingType
     * @param Request $request
     * @return Response
     */
    public function previewAction(SettingType $settingType, Request $request, FieldableFormBuilder $fieldableFormBuilder)
    {
        $setting = $settingType->getSetting();
        $response = null;

        $form = $fieldableFormBuilder->createForm($settingType, $setting);
        $form->add('submit', SubmitType::class, ['label' => 'setting.update.submit']);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            // Assign data to content object.
            $fieldableFormBuilder->assignDataToFieldableContent($setting, $form->getData());

            // Create GraphQL Schema
            $domain = $settingType->getDomain();
            $queryType = ucfirst($settingType->getIdentifier()) . 'Setting';
            $query = $request->query->get('query', 'query{type}');
            $schema = $this->container->get('unite.cms.graphql.schema_type_manager')->createSchema($domain, $queryType);
            $result = GraphQL::executeQuery($schema, $query, $setting);
            $response = $this->container->get('jms_serializer')->serialize($result->data, 'json');
        }

        return new Response($response);
    }

    /**
     * @Route("/{setting_type}/revisions/{setting}", methods={"GET"})
     * @Entity("settingType", expr="repository.findByIdentifiers(organization, domain, setting_type)")
     * @Entity("setting")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\SettingVoter::UPDATE'), setting)")
     *
     * @param SettingType $settingType
     * @param Setting $setting
     * @param FieldableContentManager $contentManager
     * @return Response
     */
    public function revisionsAction(SettingType $settingType, Setting $setting, FieldableContentManager $contentManager)
    {
        // Otherwise, a user could update setting, he_she has access to, from another domain.
        if($setting->getSettingType() !== $settingType) {
            throw $this->createNotFoundException();
        }

        return $this->render(
            '@UniteCMSCore/Setting/revisions.html.twig',
            [
                'settingType' => $settingType,
                'setting' => $setting,
                'revisions' => $contentManager->getRevisions($setting),
            ]
        );
    }

    /**
     * @Route("/{setting_type}/revisions/{setting}/revert/{version}", methods={"GET", "POST"})
     * @Entity("settingType", expr="repository.findByIdentifiers(organization, domain, setting_type)")
     * @Entity("setting")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\SettingVoter::UPDATE'), setting)")
     *
     * @param SettingType $settingType
     * @param Setting $setting
     * @param int $version
     * @param Request $request
     * @param FieldableContentManager $contentManager
     * @return Response
     */
    public function revisionsRevertAction(SettingType $settingType, Setting $setting, int $version, Request $request, FieldableContentManager $contentManager)
    {
        // Otherwise, a user could update setting, he_she has access to, from another domain.
        if($setting->getSettingType() !== $settingType) {
            throw $this->createNotFoundException();
        }

        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, ['label' => 'Revert'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $contentManager->revert($setting, $version, true);
            $this->addFlash('success', 'Setting reverted.');
            return $this->redirect($this->generateUrl('unitecms_core_setting_revisions', [$setting]));
        }

        return $this->render(
            '@UniteCMSCore/Setting/revertRevision.html.twig',
            [
                'organization' => $settingType->getDomain()->getOrganization()->getIdentifier(),
                'domain' => $settingType->getDomain()->getIdentifier(),
                'settingType' => $settingType,
                'setting' => $setting,
                'version' => $version,
                'form' => $form->createView(),
            ]
        );
    }
}
