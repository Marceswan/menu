<?php

namespace Spatie\Menu\Helpers;

class Arr
{
    public static function map(array $array, callable $callback)
    {
        $keys = array_keys($array);

        $items = array_map($callback, $array, $keys);

        return array_combine($keys, $items);
    }
}
