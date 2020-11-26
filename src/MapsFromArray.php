<?php

namespace Jerodev\DataMapper;

interface MapsFromArray
{
    public static function mapFromArray(array $data): self;
}
