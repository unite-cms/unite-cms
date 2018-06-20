<?php

if (isset($_ENV['BOOTSTRAP_CLEAR_CACHE_ENV'])) {

    passthru(sprintf(
      'php "%s/../bin/console" doctrine:schema:drop --force',
      __DIR__,
      $_ENV['BOOTSTRAP_CLEAR_CACHE_ENV']
    ));

    passthru(sprintf(
      'php "%s/../bin/console" doctrine:schema:update --force',
      __DIR__,
      $_ENV['BOOTSTRAP_CLEAR_CACHE_ENV']
    ));

    passthru(sprintf(
      'php "%s/../bin/console" cache:clear --env=%s --no-warmup',
      __DIR__,
      $_ENV['BOOTSTRAP_CLEAR_CACHE_ENV']
    ));

}

require __DIR__.'/../vendor/autoload.php';