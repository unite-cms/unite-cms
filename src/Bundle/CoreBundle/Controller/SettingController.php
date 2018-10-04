<?php

namespace UniteCMS\CoreBundle\Controller;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Router;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\ParamConverter\IdentifierNormalizer;

class SettingController extends Controller
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
    public function indexAction(SettingType $settingType, $locale, Request $request)
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

        $form = $this->get('unite.cms.fieldable_form_builder')->createForm(
            $settingType,
            $setting,
            ['attr' => ['class' => 'uk-form-vertical']]
        );
        $form->add('submit', SubmitType::class, ['label' => 'setting.update.submit']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            if (isset($data['locale'])) {
                $setting->setLocale($data['locale']);
                unset($data['locale']);
            }

            // Only set data if it has changed
            if ($data != $setting->getData()) {
                $setting->setData($data);
            }

            // If content errors were found, map them to the form.
            $violations = $this->get('validator')->validate($setting);
            if (count($violations) > 0) {
                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

                // If content is valid.
            } else {
                $this->getDoctrine()->getManager()->flush();
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
    public function previewAction(SettingType $settingType, Request $request)
    {
        if(empty($settingType->getPreview())) {
            throw $this->createNotFoundException('No preview defined for this setting type.');
        }

        $data_uri = '';
        $setting = $settingType->getSetting();
        $form = $this->get('unite.cms.fieldable_form_builder')->createForm($settingType, $setting);
        $form->add('submit', SubmitType::class, ['label' => 'setting.update.submit']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            if (isset($data['locale'])) {
                $setting->setLocale($data['locale']);
                unset($data['locale']);
            }

            $setting->setData($data);

            // Create GraphQL Schema
            $schema = $this->container->get('unite.cms.graphql.schema_type_manager')->createSchema($settingType->getDomain(), ucfirst($settingType->getIdentifier()) . 'Setting');
            $result = GraphQL::executeQuery($schema, $settingType->getPreview()->getQuery(), $setting);
            $data_uri = urlencode($this->container->get('jms_serializer')->serialize($result->data, 'json'));
        }

        $preview_url = $settingType->getPreview()->getUrl();
        $param_seperator = strpos($preview_url, '?') === false ? '?' : '&';
        return new Response($preview_url . $param_seperator . 'data=' . $data_uri);
    }

    /**
     * @Route("/{setting_type}/translations/{setting}", methods={"GET"})
     * @Entity("settingType", expr="repository.findByIdentifiers(organization, domain, setting_type)")
     * @Entity("setting")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\SettingVoter::UPDATE'), setting)")
     *
     * @param SettingType $settingType
     * @param Setting $setting
     * @param Request $request
     * @return Response
     */
    public function translationsAction(SettingType $settingType, Setting $setting, Request $request)
    {
        // Otherwise, a user could update setting, he_she has access to, from another domain.
        if($setting->getSettingType() !== $settingType) {
            throw $this->createNotFoundException();
        }

        return $this->render(
            '@UniteCMSCore/Setting/translations.html.twig',
            [
                'settingType' => $settingType,
                'setting' => $setting,
            ]
        );
    }

    /**
     * @Route("/{setting_type}/revisions/{setting}", methods={"GET"})
     * @Entity("settingType", expr="repository.findByIdentifiers(organization, domain, setting_type)")
     * @Entity("setting")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\SettingVoter::UPDATE'), setting)")
     *
     * @param SettingType $settingType
     * @param Setting $setting
     * @param Request $request
     * @return Response
     */
    public function revisionsAction(SettingType $settingType, Setting $setting, Request $request)
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
                'revisions' => $this->getDoctrine()->getManager()->getRepository(
                    'GedmoLoggable:LogEntry'
                )->getLogEntries($setting),
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
     * @return Response
     */
    public function revisionsRevertAction(SettingType $settingType, Setting $setting, int $version, Request $request)
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

            $this->getDoctrine()->getManager()->getRepository('GedmoLoggable:LogEntry')->revert($setting, $version);
            $this->getDoctrine()->getManager()->persist($setting);
            $this->getDoctrine()->getManager()->flush();
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
