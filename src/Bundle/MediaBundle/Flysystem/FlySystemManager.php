<?php

namespace UniteCMS\MediaBundle\Flysystem;

use UniteCMS\MediaBundle\Flysystem\S3FlySystem;

class FlySystemManager
{
    public function initialize(string $driver, array $config) {
        switch ($driver) {
            case 's3':
                $fs = new S3FlySystem($config);
                return $fs->getInstance();
            break;
        }
        return null;
    }
}