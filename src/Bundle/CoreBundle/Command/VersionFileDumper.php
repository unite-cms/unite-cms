<?php


namespace UniteCMS\CoreBundle\Command;

use App\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class VersionFileDumper extends Command
{
    const VERSION_FILE_NAME = 'VERSION.md';
    const VERSION_FILE_CONTENT = 'version {version}';

    /**
     * @var Kernel $kernel
     */
    protected $kernel;

    /**
     * @var string $projectDir
     */
    protected $projectDir;

    /**
     * {@inheritdoc}
     */
    public function __construct(HttpKernelInterface $kernel, string $projectDir) {
        $this->kernel = $kernel;
        $this->projectDir = $projectDir;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('unite:versionfile:dump')
            ->setDescription('A little helper command to create VERSION.md file in each bundle from unite version variable.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bundleDir = $this->projectDir . '/src/Bundle/';

        foreach($this->kernel->getBundles() as $bundle) {

            // Skip all bundles that are not inside /src/Bundle folder.
            if(substr($bundle->getPath(), 0, strlen($bundleDir)) === $bundleDir) {
                if(defined(get_class($bundle) . '::UNITE_VERSION')) {
                    file_put_contents(
                        $bundle->getPath() . '/' . self::VERSION_FILE_NAME,
                        str_replace('{version}', $bundle::UNITE_VERSION, self::VERSION_FILE_CONTENT)
                    );
                }
            }
        }
    }
}
