<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 26.04.18
 * Time: 16:38
 */

namespace UniteCMS\CoreBundle\Twig;

use Symfony\Component\Asset\Packages;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AssetDump extends AbstractExtension
{
    private $packages;
    private $pathToPublic;

    public function __construct(Packages $packages, string $pathToPublic)
    {
        $this->packages = $packages;
        $this->pathToPublic = $pathToPublic;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new TwigFunction('asset_dump', array($this, 'AssetDump')),
        );
    }

    /**
     * Returns the content of an asset to be included inline.
     *
     * If the package used to generate the path is an instance of
     * UrlPackage, you will always get a URL and not a path.
     *
     * @param string $path        A public path
     * @param string $packageName The name of the asset package to use
     *
     * @return string The content of the asset
     */
    public function AssetDump($path, $packageName = null)
    {
        return file_get_contents($this->pathToPublic.$this->packages->getUrl($path, $packageName));
    }

}