<?php

namespace Jerodev\DataMapper;

interface MapsItself
{
    public static function mapObject($data, Mapper $mapper): self;
}
