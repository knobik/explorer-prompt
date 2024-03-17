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
                $item = is_array($item) ? $item : [$item];

                foreach (array_values($item) as $index => $column) {
                    if ($prompt->getColumnFilterable($index) && str_contains($column, $filter)) {
                        return true;
                    }
                }

                return false;
            })
            ->toArray();
    }
}
