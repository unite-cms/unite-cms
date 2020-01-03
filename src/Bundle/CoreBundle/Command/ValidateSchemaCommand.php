<?php

namespace UniteCMS\CoreBundle\Command;

use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use GraphQL\Utils\SchemaPrinter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\EventSubscriber\SetCurrentDomainSubscriber;
use UniteCMS\CoreBundle\GraphQL\SchemaManager;
use UniteCMS\CoreBundle\Security\Encoder\FieldableUserPasswordEncoder;
use UniteCMS\CoreBundle\UniteCMSCoreBundle;

class ValidateSchemaCommand extends Command
{
    /**
     * @var SchemaManager $schemaManager
     */
    private $schemaManager;

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var TokenStorageInterface $tokenStorage
     */
    protected $tokenStorage;

    /**
     * @var ValidatorInterface $validator
     */
    protected $validator;

    /**
     * @var FieldableUserPasswordEncoder $passwordEncoder
     */
    protected $passwordEncoder;

    public function __construct(SchemaManager $schemaManager, DomainManager $domainManager, TokenStorageInterface $tokenStorage, ValidatorInterface $validator, FieldableUserPasswordEncoder $passwordEncoder)
    {
        parent::__construct();
        $this->schemaManager = $schemaManager;
        $this->domainManager = $domainManager;
        $this->tokenStorage = $tokenStorage;
        $this->validator = $validator;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('unite:schema:validate')
            ->setDescription('Validate the GraphQL schema of the current domain.')
            ->addOption('print', 'print', InputOption::VALUE_NONE)

            // Will be used in SetCurrentDomainSubscriber
            ->addOption(SetCurrentDomainSubscriber::COMMAND_OPTION, '', InputOption::VALUE_OPTIONAL, 'Specify the unite domain id to set before executing the command.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->tokenStorage->setToken(new AnonymousToken('', 'anon.'));
        $schema = $this->schemaManager->buildExecutableSchema();

        $result = $this->schemaManager->execute('query { unite { _version } }');
        $io = new SymfonyStyle($input, $output);

        if($result->toArray(true) === ['data' => ['unite' => ['_version' => UniteCMSCoreBundle::UNITE_VERSION]]]) {
            if($input->getOption('print')) {
                $io->write(SchemaPrinter::doPrint($schema));
            }
            $io->success('Schema is valid!');
            return 0;
        } else {
            $io->error(array_map(function(Error $error){ return FormattedError::printError($error); }, $result->errors));
            return 1;
        }
    }
}
