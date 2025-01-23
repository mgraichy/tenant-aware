<?php

namespace Tests\App\TenantAware;

class ClassOne
{
    public function __invoke(): void
    {
        $this->method();
    }

    public function method(): void {}
}
