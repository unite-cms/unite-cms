<?php

namespace UniteCMS\CoreBundle\Command;

use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Content\SensitiveFieldData;
use UniteCMS\CoreBundle\ContentType\UserType;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\EventSubscriber\SetCurrentDomainSubscriber;
use UniteCMS\CoreBundle\GraphQL\SchemaManager;
use UniteCMS\CoreBundle\Security\Encoder\FieldableUserPasswordEncoder;

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
            ->setName('unite:user:create')
            ->setDescription('Create a new unite cms user')
            ->addOption('type', 'type', InputOption::VALUE_REQUIRED)
            ->addOption('username', 'user', InputOption::VALUE_REQUIRED)
            ->addOption('password', 'pass', InputOption::VALUE_REQUIRED)
            ->addOption('persist', 'per', InputOption::VALUE_NONE)

            // Will be used in SetCurrentDomainSubscriber
            ->addOption(SetCurrentDomainSubscriber::COMMAND_OPTION, '', InputOption::VALUE_OPTIONAL, 'Specify the unite domain id to set before executing the command.')
        ;
    }

    /**
     * @param Domain $domain
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array
     */
    protected function getOptions(Domain $domain, InputInterface $input, OutputInterface $output) : array {

        $helper = $this->getHelper('question');

        // Get type:
        $type = $input->getOption('type');

        if($type) {
            if(!$domain->getContentTypeManager()->getUserType($type)) {
                throw new InvalidArgumentException(sprintf('No user type "%s" found in the current schema.', $type));
            }

        } else {
            $question = new ChoiceQuestion('Please select a UniteUser type:', array_map(function(UserType $userType){
                return $userType->getId();
            }, $domain->getContentTypeManager()->getUserTypes()));
            $question->setMultiselect(false);
            $type = $helper->ask($input, $output, $question);
        }

        // Get username:
        $username = $input->getOption('username');
        if(!$username) {
            $question = new Question('Please select a username:');
            $question->setValidator(function ($value) {
                if (trim($value) == '') {
                    throw new \Exception('The username cannot be empty');
                }

                return $value;
            });
            $username = $helper->ask($input, $output, $question);
        }

        // Get password:
        $password = $input->getOption('password');
        if(!$password) {
            $question = new Question('Please select a password:');
            $question->setValidator(function ($value) {
                if (trim($value) == '') {
                    throw new \Exception('The password cannot be empty');
                }

                return $value;
            });
            $question->setHidden(true);
            $password = $helper->ask($input, $output, $question);
        }

        return [$type, $username, $password];
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->tokenStorage->setToken(new AnonymousToken('', ''));
        $this->schemaManager->buildCacheableSchema();
        $domain = $this->domainManager->current();

        list($type, $username, $password) = $this->getOptions($domain, $input, $output);
        $userType = $domain->getContentTypeManager()->getUserType($type);
        $passwordField = null;

        foreach ($userType->getDirectives() as $directive) {
            if($directive['name'] === 'passwordAuthenticator') {
                $passwordField = $directive['args']['passwordField'];
            }
        }

        if(!$passwordField) {
            $output->writeln(sprintf(
                '<error>@passwordAuthenticator was not configured for GraphQL user type "%s".</error>',
                $type
            ));
            return 1;
        }

        $user = $domain->getUserManager()->create($domain, $type);
        $domain->getUserManager()->update($domain, $user, [
            'username' => new SensitiveFieldData($username),
            $passwordField => new SensitiveFieldData($this->passwordEncoder->encodePassword($user, $password)),
        ]);

        $errors = $this->validator->validate($user);
        if(count($errors) > 0) {
            $output->writeln(sprintf(
                '<error>@Could not create user because of the following validation errors: %s</error>',
                $errors
            ));
            return 1;
        }

        if($input->getOption('persist')) {
            $domain->getUserManager()->flush($domain);
        }

        $output->writeln([
            '',
            sprintf('<info>%s</info> user with username <info>%s</info> %s.', $user->getType(), $user->getUsername(), $input->getOption('persist') ? 'was created' : 'will be created if you add the --persist option.'),
            '',
        ]);

        return 0;
    }
}
