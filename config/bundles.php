<?php

/**
 * For optional bundles that changes the core bundle behaviour, we define separate environments. Example: the
 * registration bundle allows all logged in users to create an organization. The access test for the test env should
 * check that it is not allowed, the same access test for the test_registration env should return true.
 */
$dev_env = ['dev' => true, 'dev_registration' => true];
$test_env = ['test' => true, 'test_registration' => true];

return [

    # unite cms core bundle needs to be registered at first position
    UniteCMS\CoreBundle\UniteCMSCoreBundle::class => ['all' => true],

    # All core bundles
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle::class => ['all' => true],
    JMS\SerializerBundle\JMSSerializerBundle::class => ['all' => true],
    Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle::class => ['all' => true],
    Knp\Bundle\PaginatorBundle\KnpPaginatorBundle::class => ['all' => true],

    # unite cms bundles for standard installation
    UniteCMS\CollectionFieldBundle\UniteCMSCollectionFieldBundle::class => ['all' => true],
    UniteCMS\StorageBundle\UniteCMSStorageBundle::class => ['all' => true],
    UniteCMS\WysiwygFieldBundle\UniteCMSWysiwygFieldBundle::class => ['all' => true],
    UniteCMS\VariantsFieldBundle\UniteCMSVariantsFieldBundle::class => ['all' => true],

    # unite cms bundles that are not part of the standard installation
    UniteCMS\RegistrationBundle\UniteCMSRegistrationBundle::class => ['dev_registration' => true, 'test_registration' => true],
    UniteCMS\RecaptchaFieldBundle\UniteCMSRecaptchaFieldBundle::class => ['all' => true],

    # Dev and Test bundles
    Symfony\Bundle\DebugBundle\DebugBundle::class => ($dev_env + $test_env),
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => $dev_env,
    Symfony\Bundle\WebServerBundle\WebServerBundle::class => $dev_env,
];
