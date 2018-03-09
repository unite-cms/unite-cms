<?php

namespace UnitedCMS\CoreBundle\Controller;

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
use UnitedCMS\CoreBundle\Entity\Setting;
use UnitedCMS\CoreBundle\Entity\SettingType;

class SettingController extends Controller
{
    /**
     * @Route("/{setting_type}/{locale}", defaults={"locale"=null})
     * @Method({"GET", "POST"})
     * @Entity("settingType", expr="repository.findByIdentifiers(organization, domain, setting_type)")
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\SettingVoter::UPDATE'), settingType)")
     *
     * @param SettingType $settingType
     * @param null|string $locale
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(SettingType $settingType, $locale, Request $request)
    {
        if(!$locale && !empty($settingType->getLocales())) {
            $locale = $settingType->getLocales()[0];
        }

        $setting = $settingType->getSetting($locale);

        if(!$setting) {
            throw $this->createNotFoundException();
        }

        // If this setting was not saved before, do now
        if(!$this->getDoctrine()->getManager()->contains($setting)) {
            $this->getDoctrine()->getManager()->persist($setting);
            $this->getDoctrine()->getManager()->flush();
        }

        $form = $this->get('united.cms.fieldable_form_builder')->createForm($settingType, $setting);
        $form->add('submit', SubmitType::class, ['label' => 'Save']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            if(isset($data['locale'])) {
                $setting->setLocale($data['locale']);
                unset($data['locale']);
            }

            // Only set data if it has changed
            if($data != $setting->getData()) {
                $setting->setData($data);
            }

            // If content errors were found, map them to the form.
            $violations = $this->get('validator')->validate($setting);
            if (count($violations) > 0) {
                $violationMapper = new ViolationMapper();
                foreach($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

            // If content is valid.
            } else {
                $this->getDoctrine()->getManager()->flush();
            }
        }

        return $this->render(
            'UnitedCMSCoreBundle:Setting:index.html.twig',
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
     * @Route("/{setting_type}/translations/{setting}")
     * @Entity("settingType", expr="repository.findByIdentifiers(organization, domain, setting_type)")
     * @Entity("setting")
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\SettingVoter::UPDATE'), setting)")
     *
     * @param SettingType $settingType
     * @param Setting $setting
     * @param Request $request
     * @return Response
     */
    public function translationsAction(SettingType $settingType, Setting $setting, Request $request)
    {
        return $this->render(
            '@UnitedCMSCore/Setting/translations.html.twig',
            [
                'settingType' => $settingType,
                'setting' => $setting,
            ]
        );
    }

    /**
     * @Route("/{setting_type}/revisions/{setting}")
     * @Entity("settingType", expr="repository.findByIdentifiers(organization, domain, setting_type)")
     * @Entity("setting")
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\SettingVoter::UPDATE'), setting)")
     *
     * @param SettingType $settingType
     * @param Setting $setting
     * @param Request $request
     * @return Response
     */
    public function revisionsAction(SettingType $settingType, Setting $setting, Request $request)
    {
        return $this->render(
            '@UnitedCMSCore/Setting/revisions.html.twig',
            [
                'settingType' => $settingType,
                'setting' => $setting,
                'revisions' => $this->getDoctrine()->getManager()->getRepository('GedmoLoggable:LogEntry')->getLogEntries($setting),
            ]
        );
    }

    /**
     * @Route("/{setting_type}/revisions/{setting}/revert/{version}")
     * @Entity("settingType", expr="repository.findByIdentifiers(organization, domain, setting_type)")
     * @Entity("setting")
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\SettingVoter::UPDATE'), setting)")
     *
     * @param SettingType $settingType
     * @param Setting $setting
     * @param int $version
     * @param Request $request
     * @return Response
     */
    public function revisionsRevertAction(SettingType $settingType, Setting $setting, int $version, Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, ['label' => 'Revert'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $this->getDoctrine()->getManager()->getRepository('GedmoLoggable:LogEntry')->revert($setting, $version);
            $this->getDoctrine()->getManager()->persist($setting);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Setting reverted.');

            return $this->redirectToRoute(
                'unitedcms_core_setting_revisions',
                [
                    'organization' => $settingType->getDomain()->getOrganization()->getIdentifier(),
                    'domain' => $settingType->getDomain()->getIdentifier(),
                    'setting_type' => $settingType->getIdentifier(),
                    'setting' => $setting->getId(),
                ]
            );
        }

        return $this->render(
            '@UnitedCMSCore/Setting/revertRevision.html.twig',
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