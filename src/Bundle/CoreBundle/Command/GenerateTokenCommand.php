<?php

namespace UniteCMS\CoreBundle\Command;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use UniteCMS\CoreBundle\EventSubscriber\SetCurrentDomainSubscriber;
use UniteCMS\CoreBundle\Security\DomainUserProvider;

class GenerateTokenCommand extends Command
{
    /**
     * @var \Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface
     */
    private $tokenManager;

    /**
     * @var DomainUserProvider $domainUserProvider
     */
    private $domainUserProvider;

    public function __construct(DomainUserProvider $domainUserProvider, JWTTokenManagerInterface $tokenManager)
    {
        parent::__construct();

        $this->tokenManager  = $tokenManager;
        $this->domainUserProvider = $domainUserProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('unite:jwt:generate-token')
            ->setDescription('Generates a JWT token for a unite cms user.')
            ->addArgument('type', InputArgument::REQUIRED)
            ->addArgument('username', InputArgument::REQUIRED)

            // Will be used in SetCurrentDomainSubscriber
            ->addOption(SetCurrentDomainSubscriber::COMMAND_OPTION, '', InputOption::VALUE_OPTIONAL, 'Specify the unite domain id to set before executing the command.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = $this->domainUserProvider->loadUserByUsernameAndPayload(
            $input->getArgument('username'),
            ['roles' => [sprintf('ROLE_%s', $input->getArgument('type'))]]
        );

        $token = $this->tokenManager->create($user);

        $output->writeln([
            '',
            '<info>'.$token.'</info>',
            '',
        ]);
    }
}
