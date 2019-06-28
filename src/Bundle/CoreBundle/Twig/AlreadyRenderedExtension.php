<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 26.04.18
 * Time: 16:38
 */

namespace UniteCMS\CoreBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AlreadyRenderedExtension extends AbstractExtension
{
    private $renderedKeys = [];

    public function getFunctions()
    {
        return [
            new TwigFunction('alreadyRendered', [$this, 'alreadyRendered']),
        ];
    }

    /**
     * Returns true, if this method was already called with this key. This is useful to render twig blocks exactly once
     * on a page. We use this in form theme blocks to include css and js files only once.
     *
     * @param string $key
     * @return bool
     */
    public function alreadyRendered(string $key) : bool {

        if(!isset($this->renderedKeys[$key])) {
            $this->renderedKeys[$key] = true;
            return false;
        }

        return true;
    }

}