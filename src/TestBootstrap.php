<?php

if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] == "test") {

    passthru(sprintf(
      'php "%s/../bin/console" doctrine:schema:drop --force --quiet',
      __DIR__,
      $_ENV['APP_ENV']
    ));

    passthru(sprintf(
      'php "%s/../bin/console" doctrine:schema:update --force --quiet',
      __DIR__,
      $_ENV['APP_ENV']
    ));

    passthru(sprintf(
      'php "%s/../bin/console" cache:clear --env=%s --no-warmup --quiet',
      __DIR__,
      $_ENV['APP_ENV']
    ));

}

require __DIR__.'/../vendor/autoload.php';