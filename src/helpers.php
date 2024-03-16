<?php
namespace Knobik\Prompts;

function explorer(array $items, callable|string|null $title = null, ?array $header = null): ExplorerPrompt
{
    return new ExplorerPrompt(...func_get_args());
}
