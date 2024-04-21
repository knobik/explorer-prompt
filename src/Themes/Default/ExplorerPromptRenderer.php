<?php

namespace Knobik\Prompts\Themes\Default;

use Knobik\Prompts\ExplorerPrompt;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Concerns\DrawsScrollbars;
use Laravel\Prompts\Themes\Default\Renderer;

/**
 * @property ExplorerPrompt $prompt
 */
class ExplorerPromptRenderer extends Renderer
{
    use DrawsBoxes;
    use DrawsScrollbars;

    public function __invoke(ExplorerPrompt $prompt): string
    {
        $this->minWidth = $this->prompt->terminal()->cols();

        if ($this->prompt->state !== 'submit') {
            $visibleItems = collect($this->prompt->visible())
                ->values()
                ->map(function ($row) {
                    if (!is_array($row)) {
                        $row = [$row];
                    }
                    return $this->makeColumn($row);
                });

            if ($visibleItems->count() < $this->prompt->scroll) {
                $toAdd = $this->prompt->scroll - $visibleItems->count();
                for ($i = 0; $i < $toAdd; $i++) {
                    $visibleItems[] = mb_str_pad('', $this->widthToFill($this->prompt));
                }
            }

            $body = $this->scrollbar(
                $visibleItems,
                $this->prompt->firstVisible,
                $this->prompt->scroll,
                count($this->prompt->filteredItems()),
                $this->prompt->terminal()->cols() - 6
            )
                ->map(function ($label, $key) {
                    if (count($this->prompt->filteredItems()) === 0) {
                        return $label;
                    }

                    $index = $this->prompt->firstVisible + $key;
                    return $this->prompt->highlighted === $index ? $this->inverse($label) : $label;
                });

            if ($this->prompt->header) {
                $body->prepend(
                    $this->makeColumn(
                        collect($this->prompt->header)
                            ->map(fn($item) => strtoupper($item))->toArray()
                    )
                );
            }

            $this->when(
                $this->prompt->showFilterBox(),
                fn() => $this->box(
                    $this->cyan($this->truncate($this->prompt->getFilterTitle(), $prompt->terminal()->cols() - 6)),
                    $this->prompt->valueWithCursor($this->minWidth - 6),
                )
            );
            $this->box($this->getTitle($this->prompt), $body->implode(PHP_EOL));
            $this->when($this->prompt->getHint(), fn() => $this->hint($this->prompt->getHint()));
        }

        return $this;
    }

    protected function makeColumn(array $values): string
    {
        return mb_substr(
            collect($values)
                ->values()
                ->map(function ($item, $index) {
                    $width = $this->calculateColumnWidth($index);
                    return mb_str_pad($item ?? '', $width, ' ', $this->prompt->getColumnAlignment($index)->toPadding());
                })
                ->join(' '),
            0,
            $this->widthToFill()
        );
    }

    protected function getTitle(): string
    {
        $title = $this->prompt->getTitle();
        if (is_callable($title)) {
            $title = $title($this->prompt);
        }

        return $title;
    }

    protected function widthToFill(): int
    {
        return $this->prompt->terminal()->cols() - 8;
    }

    protected function calculateColumnWidth(int $column): int
    {
        $customColumnWidth = $this->prompt->getColumnWidth($column);
        if ($customColumnWidth !== null) {
            return $customColumnWidth;
        }

        $widthTaken = $this->prompt->getColumnWidthTaken();
        $widthToFill = $this->widthToFill($this->prompt) - $widthTaken;
        $columnsWithCustomWidth = $this->prompt->countColumnsWithFixedWidth();
        $widthPerColumn = floor($widthToFill / ($this->columnCount($this->prompt) - $columnsWithCustomWidth));

        return $this->prompt->getColumnMinWidth($column) + ($widthPerColumn > 0 ? $widthPerColumn : 0);
    }

    protected function columnCount(): int
    {
        if ($this->prompt->header !== null) {
            return count($this->prompt->header);
        }

        $item = head($this->prompt->items);

        return is_array($item) ? count($item) : 1;
    }
}
