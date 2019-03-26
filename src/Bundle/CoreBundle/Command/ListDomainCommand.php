<?php

namespace UniteCMS\CoreBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UniteCMS\CoreBundle\Exception\MissingOrganizationException;
use UniteCMS\CoreBundle\Service\DomainConfigManager;

class ListDomainCommand extends Command
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var DomainConfigManager $domainConfigManager
     */
    private $domainConfigManager;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        EntityManager $em,
        DomainConfigManager $domainConfigManager
    ) {
        $this->em = $em;
        $this->domainConfigManager = $domainConfigManager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('unite:domain:list')
            ->addArgument('organization', InputArgument::REQUIRED)
            ->setDescription('List all domains in an organization.');
    }


    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $organization = $this->em->getRepository('UniteCMSCoreBundle:Organization')->findOneBy(['identifier' => $input->getArgument('organization')]);

        if(!$organization) {
            throw new MissingOrganizationException();
        }

        $table = new Table($output);
        $table->setHeaders(['Identifier', 'Title', 'Persisted?', 'Config in filesystem?']);

        $domains = [];

        foreach($organization->getDomains() as $domain) {
            $domains[$domain->getIdentifier()] = [
                $domain->getIdentifier(),
                $domain->getTitle(),
                '<info>Yes</info>',
                '<error>No</error>',
            ];
        }

        foreach ($this->domainConfigManager->listConfig($organization) as $config) {
            if(!array_key_exists($config, $domains)) {
                $domains[$config] = [
                    $config,
                    '',
                    '<error>No</error>',
                ];
            }
            $domains[$config][3] = '<info>Yes</info>';
        }

        $table->addRows($domains);
        $table->render();
/*
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            '<info>Please select the organization to create the domain for:</info> ',
            $organizations
        );
        $organization_title = $helper->ask($input, $output, $question);

        $organization = null;
        foreach ($organizations as $org) {
            if ($organization_title === $org->getTitle()) {
                $organization = $org;
            }
        }

        $helper = $this->getHelper('question');
        $question = new Question("\n<info>Domain identifier</info> (numbers, lowercase chars and underscore is allowed): ");
        $domainIdentifier = $helper->ask($input, $output, $question);

        $helper = $this->getHelper('question');
        $question = new Question("\n<info>Domain configuration</info> (If left blank, an empty domain will get created and you can update it later.): </info>");
        $domainDefinition = $helper->ask($input, $output, $question);

        $domain = empty($domainDefinition) ? new Domain() : $this->domainConfigManager->parse($domainDefinition);
        $domain
            ->setOrganization($organization)
            ->setIdentifier($domainIdentifier)
            ->setConfig($domainDefinition ?? '');

        if(empty($domain->getTitle())) {
            $domain->setTitle(str_replace('_', ' ', ucfirst($domain->getIdentifier())));
        }

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

        $question = new ConfirmationQuestion(
            '<info>Should the domain for the organization: "'.$organization.'" be created</info>? [<comment>Y/n</comment>] ',
            true,
            '/^(y|j)/i'
        );

        if (!$helper->ask($input, $output, $question)) {
            return;
        }

        if ($this->validate($output, $domain)) {
            $this->em->persist($domain);
            $this->em->flush();
            $output->writeln(['', '<info>Database entry was created successfully!</info>', '<info>Domain configuration file was created successfully at path: </info>"'.$this->domainConfigManager->getDomainConfigPath( $domain ).'"', '']);
        }*/
    }
}
