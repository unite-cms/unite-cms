<?php

use Symfony\Component\Routing\RouteCollection;

$approach = getenv('ROUTING_APPROACH') ? getenv('ROUTING_APPROACH') : 'subdomain';

$routes = new RouteCollection();
$routes->addCollection($loader->import("@UniteCMSRegistrationBundle/Resources/config/routing.$approach.yml"));

return $routes;
