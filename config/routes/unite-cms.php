<?php

use Symfony\Component\Routing\RouteCollection;

# Include routes from core bundles depending on a ROUTING_APPROACH env param. This can be "subdomain" or "parameter".
$approach = $_ENV['ROUTING_APPROACH'] ?? 'subdomain';

$routes = new RouteCollection();
$routes->addCollection($loader->import("@UniteCMSCoreBundle/Resources/config/routing.$approach.yml"));
$routes->addCollection($loader->import("@UniteCMSStorageBundle/Resources/config/routing.$approach.yml"));

return $routes;
