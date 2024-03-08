<?php

namespace Knobik\Prompts\Themes\Default;

use Knobik\Prompts\ExplorerPrompt;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Concerns\DrawsScrollbars;
use Laravel\Prompts\Themes\Default\Renderer;

class ExplorerPromptRenderer extends Renderer
{
    use DrawsBoxes;
    use DrawsScrollbars;

    public function __invoke(ExplorerPrompt $prompt): string
    {
        if ($prompt->state !== 'submit') {
            $visibleItems = collect($prompt->visible())
                ->values()
                ->map(function (array $row) use ($prompt) {
                    return $this->makeColumn($prompt, $row);
                });

            if ($visibleItems->count() < $prompt->scroll) {
                $toAdd = $prompt->scroll - $visibleItems->count();
                for ($i = 0; $i < $toAdd; $i++) {
                    $visibleItems[] = mb_str_pad('', $this->widthToFill($prompt));
                }
            }

            $body = $this->scrollbar(
                $visibleItems,
                $prompt->firstVisible,
                $prompt->scroll,
                count($prompt->items),
                $prompt->terminal()->cols() - 6
            )
                ->map(function ($label, $key) use ($prompt) {
                    $index = $prompt->firstVisible + $key;
                    return $prompt->highlighted === $index ? $this->inverse($label) : $label;
                });

            if ($prompt->header) {
                $body->prepend(
                    $this->makeColumn(
                        $prompt,
                        collect($prompt->header)
                            ->map(fn($item) => strtoupper($item))->toArray()
                    )
                );
            }

            $this->minWidth = $prompt->terminal()->cols();
            $this->box($this->getTitle($prompt), $body->implode(PHP_EOL));
        }

        return $this;
    }

    protected function makeColumn(ExplorerPrompt $prompt, array $values): string
    {
        return collect($values)
            ->values()
            ->map(function ($item, $index) use ($prompt) {
                $width = $this->calculateColumnWidth($prompt, $index);
                return mb_str_pad($item, $width, ' ', $prompt->getColumnAlignment($index)->toPadding());
            })
            ->join(' ');
    }

    protected function getTitle(ExplorerPrompt $prompt): string
    {
        $title = $prompt->getTitle();
        if (is_callable($title)) {
            $title = $title($prompt);
        }

        return $title;
    }

    protected function widthToFill(ExplorerPrompt $prompt): int
    {
        return $prompt->terminal()->cols() - 11;
    }

    protected function calculateColumnWidth(ExplorerPrompt $prompt, int $column): int
    {
        $customColumnWidth = $prompt->getColumnWidth($column);
        if ($customColumnWidth !== null) {
            return $customColumnWidth;
        }

        $columnCount = $this->columnCount($prompt);
        $widthTaken = 0;
        $fixedWidthCount = 0;
        for ($i = 0; $i < $columnCount; $i++) {
            $width = $prompt->getColumnWidth($i);
            if ($width) {
                $widthTaken += $width;
                $fixedWidthCount++;
            }
        }

        $widthToFill = $this->widthToFill($prompt) - $widthTaken;
        return floor($widthToFill / ($columnCount - $fixedWidthCount));
    }

    protected function columnCount(ExplorerPrompt $prompt): int
    {
        if ($prompt->header !== null) {
            return count($prompt->header);
        }

        return count(head($prompt->items));
    }
}
