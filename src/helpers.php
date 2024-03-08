<?php
namespace Knobik\Prompts;

function explorer(callable|string|null $title, ?array $header = null, array $items = [], int $scroll = 20): mixed
{
    return (new ExplorerPrompt(...func_get_args()))->prompt();
}