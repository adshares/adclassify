<?php

namespace Adshares\Adclassify\Twig;

use RuntimeException;

class AppVariable extends \Symfony\Bridge\Twig\AppVariable
{
    private string $name;
    private string $version;

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        if (!isset($this->name)) {
            throw new RuntimeException('The "app.name" variable is not available.');
        }

        return $this->name;
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public function getVersion(): string
    {
        if (!isset($this->version)) {
            throw new RuntimeException('The "app.version" variable is not available.');
        }

        return $this->version;
    }
}
