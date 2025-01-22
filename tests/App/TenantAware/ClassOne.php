<?php

namespace Tests\App\TenantAware;

class ClassOne
{
    public function __invoke()
    {
        $this->method();
    }

    public function method(): void {}
}
