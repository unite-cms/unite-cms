<?php

namespace UniteCMS\CoreBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Exception\MissingOrganizationException;
use UniteCMS\CoreBundle\Service\DomainConfigManager;

class ImportDomainCommand extends Command
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var DomainConfigManager $domainConfigManager
     */
    private $domainConfigManager;

    /**
     * @var RequestStack $requestStack
     */
    private $requestStack;

    /**
     * @var TokenStorageInterface $tokenStorage
     */
    private $tokenStorage;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        EntityManager $em,
        ValidatorInterface $validator,
        DomainConfigManager $domainConfigManager,
        RequestStack $requestStack,
        TokenStorageInterface $tokenStorage
    ) {
        $this->em = $em;
        $this->validator = $validator;
        $this->domainConfigManager = $domainConfigManager;
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('unite:domain:import')
            ->addArgument('organization', InputArgument::REQUIRED)
            ->addArgument('domain', InputArgument::REQUIRED)
            ->setDescription('Import a domain from an existing domain configuration file.');
    }

    /**
     * Validates the domain and prints a message if not valid.
     *
     * @param OutputInterface $output
     * @param Domain $domain
     * @return bool
     */
    protected function validate(OutputInterface $output, Domain $domain) {
        $errors = $this->validator->validate($domain);
        if (count($errors) > 0) {

            // A later flush would throw an exception, if the organization would include the invalid domain.
            $domain->getOrganization()->getDomains()->removeElement($domain);
            unset($domain);

            $output->writeln("<error>\n\nThere was an error while creating the domain\n \n$errors\n</error>");
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $organization = $this->em->getRepository('UniteCMSCoreBundle:Organization')->findOneBy(['identifier' => $input->getArgument('organization')]);
        $domain_identifier = $input->getArgument('domain');

        if(!$organization) {
            throw new MissingOrganizationException();
        }

        // Some fields like the reference field needs an organization and also checks authorization.
        // Here we inject a a request for this organization and fake authorization.
        $request = new Request();
        $request->attributes->set('organization', $organization->getIdentifier());
        $admin = new User();
        $admin->setRoles([User::ROLE_PLATFORM_ADMIN]);
        $this->tokenStorage->setToken(new UsernamePasswordToken($admin, null, 'main', $admin->getRoles()));
        $this->requestStack->push($request);

        $domain = $organization->getDomains()->filter(
            function (Domain $domain) use ($domain_identifier) {
                return $domain->getIdentifier() == $domain_identifier;
            }
        )->first();

        if(!$domain) {
            $domain = new Domain();
            $domain->setOrganization($organization)->setIdentifier($domain_identifier);
        }

        $this->domainConfigManager->loadConfig($domain, true);

        if(!$this->validate($output, $domain)) { return; }

        $output->writeln(['', '', '<info>*****Domain definition*****</info>', '']);
        $output->writeln('Title</>: <comment>'.$domain->getTitle().'</comment>');
        $output->writeln('Identifier: <comment>'.$domain->getIdentifier().'</comment>');
        $output->writeln('ContentTypes: [');

        foreach ($domain->getContentTypes() as $contentType) {

            $fields = [];
            $views = [];

            foreach ($contentType->getFields() as $field) {
                $fields[] = $field->getTitle();
            }

            foreach ($contentType->getViews() as $view) {
                $views[] = $view->getTitle();
            }

            $output->writeln('    {');
            $output->writeln('      Title: <comment>'.$contentType->getTitle().'</comment>');
            $output->writeln('      Identifier: <comment>'.$contentType->getIdentifier().'</comment>');
            $output->writeln('      Icon: <comment>'.$contentType->getIcon().'</comment>');
            $output->writeln('      Description: <comment>'.$contentType->getDescription().'</comment>');
            $output->writeln('      Fields: [<comment>'.join(', ', $fields).'</comment>]');
            $output->writeln('      Views: [<comment>'.join(', ', $views).'</comment>]');
            $output->writeln('    }');
        }

        $output->writeln([']', '']);

        if($domain->getId()) {
            $question = new ConfirmationQuestion(
                '<info>Should the existing domain for the organization: "'.$organization.'" be updated</info>? [<comment>Y/n</comment>] ',
                true,
                '/^(y|j)/i'
            );

            if (!$helper->ask($input, $output, $question)) {
                $this->em->refresh($domain);
                return;
            }

            if ($this->validate($output, $domain)) {
                $this->em->flush();
                $output->writeln(['', '<info>Domain entry in database was updated successfully!</info>','']);
            }
        } else {
            $question = new ConfirmationQuestion(
                '<info>Should the domain for the organization: "'.$organization.'" be imported</info>? [<comment>Y/n</comment>] ',
                true,
                '/^(y|j)/i'
            );

            if (!$helper->ask($input, $output, $question)) {
                unset($domain);
                return;
            }

            if ($this->validate($output, $domain)) {
                $this->em->persist($domain);
                $this->em->flush();
                $output->writeln(['', '<info>Database entry was created successfully!</info>','']);
            }
        }
    }
}
