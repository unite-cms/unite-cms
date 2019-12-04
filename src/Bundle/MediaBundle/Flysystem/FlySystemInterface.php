<?php

namespace UniteCMS\MediaBundle\Flysystem;

interface FlySystemInterface
{
    public function __construct(array $config);
    public function getInstance(): FlySystemInterface;
    public function getPresignedUrl(): string;
}