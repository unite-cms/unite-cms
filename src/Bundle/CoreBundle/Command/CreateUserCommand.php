<?php

namespace UniteCMS\CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\EventSubscriber\SetCurrentDomainSubscriber;
use UniteCMS\CoreBundle\GraphQL\SchemaManager;

class CreateUserCommand extends Command
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

    public function __construct(SchemaManager $schemaManager, DomainManager $domainManager, TokenStorageInterface $tokenStorage)
    {
        parent::__construct();
        $this->schemaManager = $schemaManager;
        $this->domainManager = $domainManager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('unite:user:create')
            ->setDescription('Create a new unite cms user')
            ->addArgument('type', InputArgument::REQUIRED)
            ->addArgument('username', InputArgument::REQUIRED)
            ->addOption('persist', InputOption::VALUE_NONE)

            // Will be used in SetCurrentDomainSubscriber
            ->addOption(SetCurrentDomainSubscriber::COMMAND_OPTION, '', InputOption::VALUE_OPTIONAL, 'Specify the unite domain id to set before executing the command.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->tokenStorage->setToken(new AnonymousToken('', ''));
        $this->schemaManager->buildCacheableSchema();
        $domain = $this->domainManager->current();

        if(!$domain->getContentTypeManager()->getUserType($input->getArgument('type'))) {
            $output->writeln(sprintf(
                '<error>No user type "%s" found. Please use one of [%s] or update your schema.</error>',
                $input->getArgument('type'),
                join(', ', array_keys($domain->getContentTypeManager()->getUserTypes()))
            ));
        }

        $user = $domain->getUserManager()->create(
            $domain,
            $input->getArgument('type'),
            [
                'username' =>$input->getArgument('username')
            ],
            !!$input->getOption('persist')
        );

        $output->writeln([
            '',
            sprintf('<info>%s</info> user with username <info>%s</info> %s.', $user->getType(), $user->getUsername(), $input->getOption('persist') ? 'was created' : 'will be created if you add the --persist option.'),
            '',
        ]);
    }
}
