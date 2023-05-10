<?php

namespace Jerodev\DataMapper\Tests\_Mocks;

use Jerodev\DataMapper\Attributes\PostMapping;

#[PostMapping('increaseStars')]
#[PostMapping('increaseStars')]
#[PostMapping('increaseStars')]
class SuperUserDto extends UserDto
{
    public bool $canFly = false;
    public int $stars = 0;

    public function increaseStars(): void
    {
        $this->stars++;
    }
}
