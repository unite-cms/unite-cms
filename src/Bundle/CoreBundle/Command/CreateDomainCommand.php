<?php

namespace UniteCMS\CoreBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Exception\InvalidDomainConfigurationException;
use UniteCMS\CoreBundle\Exception\MissingDomainException;
use UniteCMS\CoreBundle\Exception\MissingOrganizationException;
use UniteCMS\CoreBundle\Service\DomainConfigManager;

class CreateDomainCommand extends Command
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
     * {@inheritdoc}
     */
    public function __construct(
        EntityManager $em,
        ValidatorInterface $validator,
        DomainConfigManager $domainConfigManager
    ) {
        $this->em = $em;
        $this->validator = $validator;
        $this->domainConfigManager = $domainConfigManager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('unite:domain:create')
            ->setDescription('Creates a new domain for an organization and saves it to the database and to the filesystem.');
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
        $organizations = $this->em->getRepository('UniteCMSCoreBundle:Organization')->findAll();

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            '<info>Please select the organization to create the domain for:</info> ',
            $organizations
        );
        $organization_title = $helper->ask($input, $output, $question);

        /**
         * @var Organization $organization
         */
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
        $domain->setOrganization($organization)->setIdentifier($domainIdentifier);

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
            try {
                $this->domainConfigManager->updateConfigFromDomain($domain);
            } catch (InvalidDomainConfigurationException $e) {
                $output->writeln("<error>\n\nThere was an error while creating the domain. Invalid domain configuration.\n</error>");
            } catch (MissingDomainException $e) {
                $output->writeln("<error>\n\nThere was an error while creating the domain. Domain identifier is missing.\n</error>");
            } catch (MissingOrganizationException $e) {
                $output->writeln("<error>\n\nThere was an error while creating the domain. Organization is missing.\n</error>");
            }

            $this->em->persist($domain);
            $this->em->flush();
            $output->writeln(['', '<info>Database entry was created successfully!</info>', '<info>Domain configuration file was created successfully at path: </info>"' . $this->domainConfigManager->getDomainConfigPath($domain) . '"', '']);
        }
    }
}
