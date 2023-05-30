<?php

namespace Jerodev\DataMapper;

abstract class MapperCommands
{
    public static function clearCache(): void
    {
        (new Mapper())->clearCache();
    }
}
