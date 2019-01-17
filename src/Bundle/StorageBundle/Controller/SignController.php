<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 08.02.18
 * Time: 09:31
 */

namespace UniteCMS\StorageBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\StorageBundle\Form\PreSignFormType;

class SignController extends AbstractController
{


    /**
     * @Route("/content/{content_type}/upload", methods={"POST", "OPTIONS"})
     * @Entity("contentType", expr="repository.findByIdentifiers(organization, domain, content_type)")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\ContentVoter::CREATE'), contentType)")
     *
     * @param ContentType $contentType
     * @param Request $request
     *
     * @return Response
     */
    public function uploadContentTypeAction(ContentType $contentType, Request $request)
    {

        $form = $this->createForm(PreSignFormType::class, null);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $preSignedUrl = $this->container->get(
                    'unite.cms.storage.service'
                )->createPreSignedUploadUrlForFieldPath(
                    $form->getData()['filename'],
                    $contentType,
                    $form->getData()['field']
                );
                $preSignedUrl->sign($this->container->getParameter('kernel.secret'));

                return new JsonResponse($preSignedUrl);

            } catch (\InvalidArgumentException $e) {
                throw new BadRequestHttpException($e->getMessage());
            }
        }

        throw new BadRequestHttpException($form->getErrors(true, true));
    }

    /**
     * @Route("/setting/{setting_type}/upload", methods={"POST", "OPTIONS"})
     * @Entity("settingType", expr="repository.findByIdentifiers(organization, domain, setting_type)")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\SettingVoter::UPDATE'), settingType)")
     *
     * @param SettingType $settingType
     * @param Request $request
     *
     * @return Response
     */
    public function uploadSettingTypeAction(SettingType $settingType, Request $request)
    {

        $form = $this->createForm(PreSignFormType::class, null);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $preSignedUrl = $this->container->get(
                    'unite.cms.storage.service'
                )->createPreSignedUploadUrlForFieldPath(
                    $form->getData()['filename'],
                    $settingType,
                    $form->getData()['field']
                );
                $preSignedUrl->sign($this->container->getParameter('kernel.secret'));

                return new JsonResponse($preSignedUrl);

            } catch (\InvalidArgumentException $e) {
                throw new BadRequestHttpException($e->getMessage());
            }
        }

        throw new BadRequestHttpException($form->getErrors(true, true));
    }
}
