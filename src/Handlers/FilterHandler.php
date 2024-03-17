<?php

namespace Knobik\Prompts\Handlers;

use Knobik\Prompts\ExplorerPrompt;

class FilterHandler
{
    public function __invoke(ExplorerPrompt $prompt, string $filter): array
    {
        return collect($prompt->items)
            ->filter(function ($item) use ($prompt, $filter) {
                if ($filter === '') {
                    return true;
                }

                if (!is_array($item)) {
                    $item = [$item];
                }

                $index = 0;
                foreach ($item as $row) {
                    if ($prompt->getColumnFilterable($index) && str_contains($row, $filter)) {
                        return true;
                    }

                    $index++;
                }

                return false;
            })
            ->toArray();
    }
}
