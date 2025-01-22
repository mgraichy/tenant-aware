<?php

namespace Tests\App\TenantAware;

use Tests\App\Models\User;

class ClassTwo
{
    protected User $user;

    public function __construct(
        protected array $arr = [],
        protected string $str = '',
        ?string $user = '',
    ) {
        if (! empty($user)) {
            $this->user = new $user;
        }
    }

    public function __invoke(string $class): void
    {
        $classOne = new $class;
        $this->someMethod($classOne);
    }

    protected function someMethod(ClassOne $classOne): ClassOne
    {
        return $classOne;
    }
}
