<?php

namespace Knobik\Prompts;

use Knobik\Prompts\Concerns\TypedValue;
use Knobik\Prompts\Key;
use Knobik\Prompts\Themes\Default\ColumnAlign;
use Knobik\Prompts\Themes\Default\ExplorerPromptRenderer;
use Laravel\Prompts\Concerns\Scrolling;
use Laravel\Prompts\Prompt;

class ExplorerPrompt extends Prompt
{
    use Scrolling;
    use TypedValue;

    public string|bool $required = false;
    public array $columnOptions = [];
    protected bool $filteringEnabled = true;
    protected string $filterTitle = 'filter';
    public int $userScroll = 20;
    protected $title;

    public function __construct(
        public array $items,
        callable|string|null $title = null,
        public ?array $header = null
    ) {
        static::$themes['default'][static::class] = ExplorerPromptRenderer::class;

        $this->title = $title ?? '';
        $this->fullscreen();
        $this->initializeScrolling(0);

        $this->on('key', function ($key) {
            if ($this->inFilteringState()) {
                $this->handleFilterKey($key);
            } else {
                match ($key) {
                    Key::UP, Key::UP_ARROW, Key::LEFT, Key::LEFT_ARROW, Key::SHIFT_TAB, Key::CTRL_P, Key::CTRL_B, 'k', 'h' => $this->keyUp(
                    ),
                    Key::DOWN, Key::DOWN_ARROW, Key::RIGHT, Key::RIGHT_ARROW, Key::TAB, Key::CTRL_N, Key::CTRL_F, 'j', 'l' => $this->keyDown(
                    ),
                    Key::oneOf([Key::HOME, Key::CTRL_A], $key) => $this->keyHome(),
                    Key::oneOf([Key::END, Key::CTRL_E], $key) => $this->keyEnd(),
                    Key::KEY_PAGE_UP => $this->keyPageUp(),
                    Key::KEY_PAGE_DOWN => $this->keyPageDown(),
                    Key::ENTER => $this->keyEnter(),
                    Key::KEY_FORWARD_SLASH => $this->keyForwardSlash(),
                    default => null,
                };
            }
        });
    }

    public function enableFiltering(): self
    {
        return $this->setFilteringEnabled(true);
    }

    public function disableFiltering(): self
    {
        return $this->setFilteringEnabled(false);
    }

    protected function setFilteringEnabled(bool $enabled): self
    {
        $this->filteringEnabled = $enabled;

        return $this;
    }

    public function getFilterTitle(): string
    {
        return $this->filterTitle;
    }

    public function setFilterTitle(string $filterTitle): void
    {
        $this->filterTitle = $filterTitle;
    }

    public function getheader(): ?array
    {
        return $this->header;
    }

    public function setHeader(array $header): self
    {
        $this->header = $header;

        return $this;
    }

    public function inFilteringState(): bool
    {
        return $this->state === 'filtering';
    }

    public function setFilter(string $value): static
    {
        $this->typedValue = $value;
        $this->cursorPosition = mb_strlen($value);
        $this->setVisibleItems($this->userScroll);

        return $this;
    }

    public function setVisibleItems(int $itemsVisible): static
    {
        $this->userScroll = $itemsVisible;
        $this->scroll = $this->userScroll - ($this->showFilterBox() ? $this->filterHeight() : 0);

        return $this;
    }

    public function fullscreen(): static
    {
        $this->setVisibleItems($this->terminal()->lines() - (4 + ($this->header ? 1 : 0)));

        return $this;
    }

    public function value(): mixed
    {
        $keys = array_keys($this->filteredItems());
        if (empty($keys)) {
            return null;
        }

        return array_search($this->filteredItems()[$keys[$this->highlighted]], $this->filteredItems());
    }

    public function filteredItems(): array
    {
        return collect($this->items)
            ->filter(function ($item) {
                if (!is_array($item)) {
                    $item = [$item];
                }

                $index = 0;
                foreach ($item as $row) {
                    if ($this->getColumnFilterable($index) && str_contains($row, $this->typedValue())) {
                        return true;
                    }

                    $index++;
                }

                return false;
            })
            ->toArray();
    }

    /**
     * The currently visible options.
     *
     * @return array<int|string, string>
     */
    public function visible(): array
    {
        return collect($this->filteredItems())
            ->slice($this->firstVisible, $this->scroll)
            ->toArray();
    }

    public function getTitle(): callable|string|null
    {
        return $this->title;
    }

    public function setTitle(callable|string|null $title): self
    {
        $this->title = $title ?? '';

        return $this;
    }

    public function setColumnOptions(
        int $column,
        int $width = null,
        ColumnAlign $align = ColumnAlign::LEFT,
        bool $filterable = true
    ): static {
        $this->columnOptions[$column] = [
            'width' => $width,
            'align' => $align,
            'filterable' => $filterable
        ];

        return $this;
    }

    public function getColumnAlignment(int $column): ColumnAlign
    {
        return $this->columnOptions[$column]['align'] ?? ColumnAlign::LEFT;
    }

    public function getColumnWidth(int $column): ?int
    {
        return $this->columnOptions[$column]['width'] ?? null;
    }

    public function getColumnFilterable(int $column): bool
    {
        return $this->columnOptions[$column]['width'] ?? true;
    }

    /**
     * Get the entered value with a virtual cursor.
     */
    public function valueWithCursor(int $maxWidth): string
    {
        if ($this->typedValue() === '') {
            return $this->dim($this->addCursor('', 0, $maxWidth));
        }

        if ($this->inFilteringState()) {
            return $this->addCursor($this->typedValue(), $this->cursorPosition, $maxWidth);
        } else {
            return $this->dim(
                mb_strlen($this->typedValue()) > $maxWidth ? mb_substr(
                        $this->typedValue(),
                        0,
                        $maxWidth - 3
                    ) . '...' : $this->typedValue()
            );
        }
    }

    public function itemBoxHeight(): int
    {
        return $this->scroll - ($this->showFilterBox() ? $this->filterHeight() : 0);
    }

    public function filterHeight(): int
    {
        return 3;
    }

    public function showFilterBox(): bool
    {
        return $this->inFilteringState() || $this->typedValue() !== '';
    }

    protected function keyUp(): void
    {
        $this->highlight(
            max(0, $this->highlighted - 1)
        );
    }

    protected function keyDown(): void
    {
        $this->highlight(
            min(count($this->filteredItems()) > 0 ? count($this->filteredItems()) - 1 : 0, $this->highlighted + 1)
        );
    }

    protected function keyHome(): void
    {
        $this->highlight(0);
    }

    protected function keyEnd(): void
    {
        $this->highlight(count($this->filteredItems()) - 1);
    }

    protected function keyPageUp(): void
    {
        $this->highlight(max(0, $this->highlighted - $this->scroll));
    }

    protected function keyPageDown(): void
    {
        $this->highlight(min(count($this->filteredItems()) - 1, $this->highlighted + $this->scroll));
    }

    protected function keyEnter(): void
    {
        $this->submit();
    }

    protected function keyForwardSlash()
    {
        if ($this->filteringEnabled) {
            $this->setFilteringState();
        }

        $this->setVisibleItems($this->userScroll);
    }

    protected function setFilteringState(): self
    {
        $this->state = 'filtering';
        $this->setVisibleItems($this->userScroll);

        return $this;
    }

    protected function setActiveState(): self
    {
        $this->state = 'active';

        return $this;
    }

    protected function eraseNewLine(): void
    {
        $this->moveCursor(-999, -1);
    }
}
