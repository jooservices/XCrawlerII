<?php

namespace Modules\Core\Traits;

trait THasProperties
{
    use THasGetter;
    use THasSetter;

    protected array $properties = [];

    public function __set(string $name, mixed $value): void
    {
        $this->setProperty($name, $value);
    }

    public function __get(string $name): mixed
    {
        return $this->getProperty($name);
    }

    public function setProperty(string $name, mixed $value): static
    {
        $methodName = $this->hasSetter($name, $value);
        if ($methodName !== false) {
            $this->{$methodName}($value);

            return $this;
        }

        $this->properties[$name] = $value;

        return $this;
    }

    public function getProperty(string $name, mixed $default = null): mixed
    {
        $methodName = $this->hasGetter($name);
        if ($methodName !== false) {
            $this->{$methodName}();

            return $this;
        }

        return $this->properties[$name] ?? $default;
    }

    public function hasProperty(string $name): bool
    {
        return isset($this->properties[$name]);
    }

    public function removeProperty(string $name): static
    {
        unset($this->properties[$name]);

        return $this;
    }
}
