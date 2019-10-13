<?php


namespace UniteCMS\CoreBundle\Command;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use UniteCMS\CoreBundle\Domain\DomainManager;

class GenerateTokenCommand extends Command
{
    /**
     * @var \Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface
     */
    private $tokenManager;

    /**
     * @var \UniteCMS\CoreBundle\Domain\DomainManager
     */
    private $domainManager;

    public function __construct(JWTTokenManagerInterface $tokenManager, DomainManager $domainManager)
    {
        parent::__construct();

        $this->tokenManager  = $tokenManager;
        $this->domainManager = $domainManager;
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
            ->addOption('domain', 'd', InputOption::VALUE_OPTIONAL)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if($input->getOption('domain')) {
            $this->domainManager->setCurrentDomainFromConfigId($input->getOption('domain'));
        }

        $domain = $this->domainManager->current();
        $token = $this->tokenManager->create(
            $domain->getUserManager()->find(
                $domain,
                $input->getArgument('type'),
                $input->getArgument('username')
            )
        );

        $output->writeln([
            '',
            '<info>'.$token.'</info>',
            '',
        ]);
    }
}
