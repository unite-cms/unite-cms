<?php

# Include access_control from core bundle depending on a ROUTING_APPROACH env param. This can be "subdomain" or "parameter".
$approach = getenv('ROUTING_APPROACH') ? getenv('ROUTING_APPROACH') : 'subdomain';
$loader->import("@UniteCMSCoreBundle/Resources/config/security.$approach.yml");
