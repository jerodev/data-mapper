<?php

namespace Jerodev\DataMapper;

interface MapsFromArray
{
    public function mapFromArray(array $data): self;
}
