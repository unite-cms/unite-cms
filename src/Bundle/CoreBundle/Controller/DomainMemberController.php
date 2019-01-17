<?php

namespace UniteCMS\CoreBundle\Controller;

use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainAccessor;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\Invitation;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Form\ChoiceCardsType;
use UniteCMS\CoreBundle\Form\FieldableFormBuilder;
use UniteCMS\CoreBundle\Form\Model\ChoiceCardOption;
use UniteCMS\CoreBundle\ParamConverter\IdentifierNormalizer;

class DomainMemberController extends AbstractController
{
    /**
     * @Route("/{member_type}", methods={"GET"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @Entity("memberType", expr="repository.findByIdentifiers(organization.getIdentifier(), domain.getIdentifier(), member_type)")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\DomainVoter::UPDATE'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @param DomainMemberType $memberType
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function indexAction(Organization $organization, Domain $domain, DomainMemberType $memberType, Request $request, PaginatorInterface $paginator)
    {
        $members = $paginator->paginate($memberType->getDomainMembers(),
            $request->query->getInt('page_members', 1),
            10,
            ['pageParameterName' => 'page_members', 'sortDirectionParameterName' => 'sort_members']
        );

        $invites = $paginator->paginate($memberType->getInvites(),
            $request->query->getInt('page_invites', 1),
            10,
            ['pageParameterName' => 'page_invites', 'sortDirectionParameterName' => 'sort_invites']
        );

        return $this->render(
            '@UniteCMSCore/Domain/Member/index.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'memberType' => $memberType,
                'members' => $members,
                'invites' => $invites,
            ]
        );
    }

    /**
     * @Route("/{member_type}/create", methods={"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @Entity("memberType", expr="repository.findByIdentifiers(organization.getIdentifier(), domain.getIdentifier(), member_type)")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\DomainVoter::UPDATE'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @param DomainMemberType $memberType
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param FormFactoryInterface $formFactory
     * @param ValidatorInterface $validator
     * @param \Swift_Mailer $mailer
     * @param string $mailerSender
     * @return Response
     * @throws \Exception
     */
    public function createAction(Organization $organization, Domain $domain, DomainMemberType $memberType, Request $request, TranslatorInterface $translator, FormFactoryInterface $formFactory, ValidatorInterface $validator, \Swift_Mailer $mailer, string $mailerSender)
    {
        // Get a list of ids of all accessors, that are already member of this domain.
        $domain_member_type_members = [];
        foreach ($memberType->getDomainMembers() as $domainMember) {
            $domain_member_type_members[] = $domainMember->getAccessor()->getId();
        }

        // Create choice options for each possible add type.
        $add_types = [];
        foreach([
            'existing_user' => 'user',
            'existing_api_key' => 'lock',
            'invite_user' => 'send',
        ] as $type => $icon) {
            $add_types[] = new ChoiceCardOption(
                $type,
                $translator->trans('domain.member.create.headline.' . $type),
                $translator->trans('domain.member.create.text.' . $type),
                $icon
            );
        }

        // Create the two-step create form.
        $form = $formFactory->createNamedBuilder(
                'create_domain_user',
                FormType::class,
                null,
                ['attr' => ['class' => 'uk-form-vertical']]
            )
            ->add('select_add_type', ChoiceCardsType::class, [
                'label' => false,
                'multiple' => false,
                'expanded' => true,
                'choices' => $add_types,
            ])
            ->add(
                'existing_user',
                EntityType::class,
                [
                    'label' => 'domain.member.create.form.user',
                    'class' => DomainAccessor::class,
                    'choices' => $organization->getMembers()->filter(
                        function(OrganizationMember $organizationMember) use ($domain_member_type_members) {
                            return !in_array($organizationMember->getUser()->getId(), $domain_member_type_members);
                        }
                    )->map(
                        function(OrganizationMember $organizationMember){
                            return $organizationMember->getUser();
                        }
                    )->toArray(),
                ]
            )
            ->add(
                'existing_api_key',
                EntityType::class,
                [
                    'label' => 'domain.member.create.form.api_key',
                    'class' => DomainAccessor::class,
                    'choices' => $organization->getApiKeys()->filter(
                        function(ApiKey $apiKey) use ($domain_member_type_members) {
                            return !in_array($apiKey->getId(), $domain_member_type_members);
                        }
                    )->toArray(),
                ]
            )
            ->add('invite_user', EmailType::class, ['label' => 'domain.member.invite.form.email'])
            ->add('submit', SubmitType::class, ['label' => 'domain.member.create.form.submit'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();
            if($data['select_add_type'] == 'existing_user' || $data['select_add_type'] == 'existing_api_key') {

                $member = new DomainMember();
                $member
                    ->setDomain($domain)
                    ->setDomainMemberType($memberType);

                if($data['select_add_type'] == 'existing_user' && !empty($data['existing_user'])) {
                    $member->setAccessor($data['existing_user']);
                }

                if($data['select_add_type'] == 'existing_api_key' && !empty($data['existing_api_key'])) {
                    $member->setAccessor($data['existing_api_key']);
                }

                $violations = $validator->validate($member);
                if($violations->count() > 0) {
                    $violationMapper = new ViolationMapper();
                    foreach ($violations as $violation) {
                        $violationMapper->mapViolation($violation, ($data['select_add_type'] == 'existing_user' ? $form->get('existing_user') : $form->get('existing_api_key')));
                    }
                } else {
                    $this->getDoctrine()->getManager()->persist($member);
                    $this->getDoctrine()->getManager()->flush();

                    return $this->redirect($this->generateUrl('unitecms_core_domainmember_index', [$memberType]));
                }

            } elseif($data['select_add_type'] == 'invite_user') {

                $invitation = new Invitation();
                $invitation->setToken(rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='));
                $invitation->setRequestedAt(new \DateTime());
                $invitation->setOrganization($organization);
                $invitation->setDomainMemberType($memberType);
                $invitation->setEmail($data['invite_user']);

                $violations = $validator->validate($invitation);
                if($violations->count() > 0) {
                    $violationMapper = new ViolationMapper();
                    foreach ($violations as $violation) {
                        $violationMapper->mapViolation($violation, $form->get('invite_user'));
                    }
                } else {
                    $this->getDoctrine()->getManager()->persist($invitation);
                    $this->getDoctrine()->getManager()->flush();

                    // Send out email using the default mailer.
                    $message = (new \Swift_Message($translator->trans('email.invitation.headline', ['%invitor%' => $this->getUser()])))
                        ->setFrom($mailerSender)
                        ->setTo($invitation->getEmail())
                        ->setBody(
                            $this->renderView(
                                '@UniteCMSCore/Emails/invitation.html.twig',
                                [
                                    'invitation' => $invitation,
                                    'invitation_url' => $this->generateUrl('unitecms_core_profile_acceptinvitation', ['token' => $invitation->getToken()]),
                                ]
                            ),
                            'text/html'
                        );
                    $mailer->send($message);

                    return $this->redirect($this->generateUrl('unitecms_core_domainmember_index', [$memberType]));
                }
            }
        }

        return $this->render(
            '@UniteCMSCore/Domain/Member/create.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'memberType' => $memberType,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{member_type}/update/{member}", methods={"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @Entity("memberType", expr="repository.findByIdentifiers(organization.getIdentifier(), domain.getIdentifier(), member_type)")
     * @ParamConverter("member")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\DomainVoter::UPDATE'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @param DomainMemberType $memberType
     * @param \UniteCMS\CoreBundle\Entity\DomainMember $member
     * @param Request $request
     *
     * @param FieldableFormBuilder $fieldableFormBuilder
     * @param ValidatorInterface $validator
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateAction(Organization $organization, Domain $domain, DomainMemberType $memberType, DomainMember $member, Request $request, FieldableFormBuilder $fieldableFormBuilder, ValidatorInterface $validator)
    {
        $form = $fieldableFormBuilder->createForm(
            $memberType,
            $member,
            ['attr' => ['class' => 'uk-form-vertical']]
        )->add('submit', SubmitType::class, ['label' => 'Update']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $member->setData($form->getData());

            // If member field errors were found, map them to the form.
            $violations = $validator->validate($member);

            if (count($violations) > 0) {
                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

            // If member is valid.
            } else {
                $this->getDoctrine()->getManager()->flush();
                return $this->redirect($this->generateUrl('unitecms_core_domainmember_index', [$memberType]));
            }
        }

        return $this->render(
            '@UniteCMSCore/Domain/Member/update.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'memberType' => $memberType,
                'form' => $form->createView(),
                'member' => $member,
            ]
        );
    }

    /**
     * @Route("/{member_type}/delete/{member}", methods={"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @Entity("memberType", expr="repository.findByIdentifiers(organization.getIdentifier(), domain.getIdentifier(), member_type)")
     * @ParamConverter("member")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\DomainVoter::UPDATE'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @param DomainMemberType $memberType
     * @param \UniteCMS\CoreBundle\Entity\DomainMember $member
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Organization $organization, Domain $domain, DomainMemberType $memberType, DomainMember $member, Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('submit', SubmitType::class, [
                'label' => 'domain.member.delete.form.submit',
                'attr' => ['class' => 'uk-button-danger'],
            ])->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->remove($member);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirect($this->generateUrl('unitecms_core_domainmember_index', [$memberType]));
        }

        return $this->render(
            '@UniteCMSCore/Domain/Member/delete.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'memberType' => $memberType,
                'form' => $form->createView(),
                'member' => $member,
            ]
        );
    }

    /**
     * @Route("/{member_type}/delete-invite/{invite}", methods={"GET", "POST"})
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @Entity("memberType", expr="repository.findByIdentifiers(organization.getIdentifier(), domain.getIdentifier(), member_type)")
     * @ParamConverter("invite")
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\DomainVoter::UPDATE'), domain)")
     *
     * @param Organization $organization
     * @param Domain $domain
     * @param DomainMemberType $memberType
     * @param Invitation $invite
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteInviteAction(
        Organization $organization,
        Domain $domain,
        DomainMemberType $memberType,
        Invitation $invite,
        Request $request
    ) {
        $form = $this->createFormBuilder()
            ->add(
                'submit',
                SubmitType::class,
                ['label' => 'domain.member.delete_invitation.form.submit', 'attr' => ['class' => 'uk-button-danger']]
            )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->remove($invite);
            $this->getDoctrine()->getManager()->flush();
            return $this->redirect($this->generateUrl('unitecms_core_domainmember_index', [$memberType]));
        }

        return $this->render(
            '@UniteCMSCore/Domain/Member/delete_invite.html.twig',
            [
                'organization' => $organization,
                'domain' => $domain,
                'memberType' => $memberType,
                'form' => $form->createView(),
                'invite' => $invite,
            ]
        );
    }
}
