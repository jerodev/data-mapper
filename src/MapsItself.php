<?php

namespace Jerodev\DataMapper;

interface MapsItself
{
    public static function mapSelf(mixed $data, Mapper $mapper): self|null;
}
