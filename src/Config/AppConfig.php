<?php

declare(strict_types=1);

namespace CrimsonHarvest\Config;

final class AppConfig
{
    public function __construct(
        public readonly array $app,
        public readonly array $event,
    ) {
    }

    public static function fromRoot(string $root): self
    {
        $app = require $root . '/config/app.php';
        $event = require $root . '/config/event.php';
        date_default_timezone_set($app['timezone']);
        return new self($app, $event);
    }
}
