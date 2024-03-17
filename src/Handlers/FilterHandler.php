<?php

namespace Knobik\Prompts\Handlers;

use Knobik\Prompts\ExplorerPrompt;

class FilterHandler
{
    public function __invoke(ExplorerPrompt $prompt, string $filter): array
    {
        if ($filter === '') {
            return $prompt->items;
        }

        return collect($prompt->items)
            ->filter(function ($item) use ($prompt, $filter) {
                if (!is_array($item)) {
                    $item = [$item];
                }

                $index = 0;
                foreach ($item as $column) {
                    if ($prompt->getColumnFilterable($index) && str_contains($column, $filter)) {
                        return true;
                    }

                    $index++;
                }

                return false;
            })
            ->toArray();
    }
}
