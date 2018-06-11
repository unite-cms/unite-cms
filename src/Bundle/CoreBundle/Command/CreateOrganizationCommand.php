<?php

namespace UniteCMS\CoreBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

use UniteCMS\CoreBundle\Entity\Organization;

class CreateOrganizationCommand extends Command
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
     * {@inheritdoc}
     */
    public function __construct(EntityManager $em, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->validator = $validator;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('unite:organization:create')
            ->setDescription('Creates a new organization and saves it to the database.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $helper = $this->getHelper('question');
        $question = new Question('<info>Please enter the title of the organization:</info> ');
        $title = $helper->ask($input, $output, $question);

        $name = $this->titleToMachineName($title);

        $question = new Question(
            '<info>Please enter the identifier of the organization</info> [<comment>'.$name.'</comment>]: ', $name
        );
        $question->setAutocompleterValues([$name]);
        $identifier = $helper->ask($input, $output, $question);

        $organization = new Organization();
        $organization->setTitle($title)->setIdentifier($identifier);

        $question = new ConfirmationQuestion(
            '<info>Should the organization with title: "'.$organization->getTitle(
            ).'" and identifier: "'.$identifier.'" be created</info>? [<comment>Y/n</comment>] ',
            true,
            '/^(y|j)/i'
        );

        if (!$helper->ask($input, $output, $question)) {
            return;
        }

        $errors = $this->validator->validate($organization);
        if (count($errors) > 0) {
            $output->writeln("<error>\n\nThere was an error while creating the organization\n \n$errors\n</error>");
        } else {
            $this->em->persist($organization);
            $this->em->flush();
            $output->writeln('<info>Organization was created successfully!</info>');
        }
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
        $name = preg_replace('/(\s|_+|-+)+/', '-', $name);
        // Trim underscores from the ends.
        $name = trim($name, '_');
        // Remove all except alpha-numeric and underscore characters.
        $name = preg_replace('/[^a-z0-9-]+/', '', $name);

        return $name;
    }
}
