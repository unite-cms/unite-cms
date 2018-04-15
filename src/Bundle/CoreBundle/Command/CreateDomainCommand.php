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

use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Service\DomainDefinitionParser;

class CreateDomainCommand extends Command
{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    private $validator;

    /**
     * @var \UniteCMS\CoreBundle\Service\DomainDefinitionParser
     */
    private $definiton_parser;

    /**
     * {@inheritdoc}
     */
    public function __construct(EntityManager $em, ValidatorInterface $validator, DomainDefinitionParser $definiton_parser)
    {
        $this->em = $em;
        $this->validator = $validator;
        $this->definiton_parser = $definiton_parser;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('unite:domain:create')
            ->setDescription('Creates a new domain for an organization and saves it to the database.');
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
        $question = new Question('<info>Please insert the domain definition JSON string:</info> ');
        $definition = $helper->ask($input, $output, $question);
        $domain = $this->definiton_parser->parse($definition);
        $domain->setOrganization($organization);

        $output->writeln(['', '', '<info>*****Domain definition*****</info>', '']);
        $output->writeln('Title</>: <comment>'.$domain->getTitle().'</comment>');
        $output->writeln('Identifier: <comment>'.$domain->getIdentifier().'</comment>');
        $output->writeln('Roles: [<comment>'.join(', ', $domain->getRoles()).'</comment>]');
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

        $output->writeln(['', '']);

        $question = new ConfirmationQuestion(
            '<info>Should the domain for the organization: "'.$organization.'" be created</info>? [<comment>Y/n</comment>] ',
            true,
            '/^(y|j)/i'
        );

        if (!$helper->ask($input, $output, $question)) {
            return;
        }

        $errors = $this->validator->validate($domain);
        if(count($errors) > 0) {
            $output->writeln("<error>\n\nThere was an error while creating the domain\n \n$errors\n</error>");
        } else {
            $this->em->persist($domain);
            $this->em->flush();
            $output->writeln('<info>Domain was created successfully!</info>');
        }

        $output->writeln('<info>Domain was created successfully!</info>');
    }

    /**
     * @see: https://github.com/SymfonyContrib/MachineNameFieldBundle/blob/master/Transformer/LabelToMachineNameTransformer.php
     * @param $title
     * @return string
     */
    private function titleToMachineName($title): string
    {
        // Lowercase.
        $name = strtolower($title);
        // Replace spaces, underscores, and dashes with underscores.
        $name = preg_replace('/(\s|_+|-+)+/', '_', $name);
        // Trim underscores from the ends.
        $name = trim($name, '_');
        // Remove all except alpha-numeric and underscore characters.
        $name = preg_replace('/[^a-z0-9_]+/', '', $name);

        return $name;
    }
}
