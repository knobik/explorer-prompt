<?php

namespace Knobik\Prompts;

use Laravel\Prompts\Key as BaseKey;

class Key extends BaseKey
{
    public const KEY_PAGE_UP = "\e[5~";
    public const KEY_PAGE_DOWN = "\e[6~";
    public const KEY_FORWARD_SLASH = "/";
    public const KEY_ESCAPE = "\e";
}
