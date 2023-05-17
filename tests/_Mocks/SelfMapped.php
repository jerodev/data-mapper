<?php

namespace Jerodev\DataMapper\Tests\_Mocks;

use Jerodev\DataMapper\Mapper;
use Jerodev\DataMapper\MapsItself;

class SelfMapped implements MapsItself
{
    public array $users;

    public static function mapSelf(mixed $data, Mapper $mapper): MapsItself
    {
        $self = new self();
        $self->users = $data['data'];

        return $self;
    }
}
