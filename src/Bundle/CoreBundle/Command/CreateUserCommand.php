<?php

namespace UniteCMS\CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Content\SensitiveFieldData;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Event\ContentEvent;
use UniteCMS\CoreBundle\EventSubscriber\SetCurrentDomainSubscriber;
use UniteCMS\CoreBundle\GraphQL\SchemaManager;
use UniteCMS\CoreBundle\GraphQL\Util;

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

    /**
     * @var ValidatorInterface $validator
     */
    protected $validator;

    public function __construct(SchemaManager $schemaManager, DomainManager $domainManager, TokenStorageInterface $tokenStorage, ValidatorInterface $validator)
    {
        parent::__construct();
        $this->schemaManager = $schemaManager;
        $this->domainManager = $domainManager;
        $this->tokenStorage = $tokenStorage;
        $this->validator = $validator;
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
            ->addArgument('password', InputArgument::REQUIRED)
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
        $userType = $domain->getContentTypeManager()->getUserType($input->getArgument('type'));

        if(!$userType) {
            $output->writeln(sprintf(
                '<error>No user type "%s" found. Please use one of [%s] or update your schema.</error>',
                $input->getArgument('type'),
                join(', ', array_keys($domain->getContentTypeManager()->getUserTypes()))
            ));
            return;
        }

        $passwordField = null;

        foreach ($userType->getDirectives() as $directive) {
            if($directive['name'] === 'passwordAuthenticator') {
                $passwordField = $directive['args']['passwordField'];
            }
        }

        if(!$passwordField) {
            $output->writeln(sprintf(
                '<error>@passwordAuthenticator was not configured for GraphQL user type "%s".</error>',
                $input->getArgument('type')
            ));
            return;
        }

        $user = $domain->getUserManager()->create($domain, $input->getArgument('type'));
        $domain->getUserManager()->update($domain, $user, [
            'username' => new SensitiveFieldData($input->getArgument('username')),
            $passwordField => new SensitiveFieldData($input->getArgument('password')),
        ]);

        $errors = $this->validator->validate($user);
        if(count($errors) > 0) {
            $output->writeln(sprintf(
                '<error>@Could not create user because of the following validation errors: %s</error>',
                $errors
            ));
            return;
        }

        if($input->getOption('persist')) {
            $domain->getUserManager()->persist($domain, $user, ContentEvent::CREATE);
        }

        $output->writeln([
            '',
            sprintf('<info>%s</info> user with username <info>%s</info> %s.', $user->getType(), $user->getUsername(), $input->getOption('persist') ? 'was created' : 'will be created if you add the --persist option.'),
            '',
        ]);
    }
}
