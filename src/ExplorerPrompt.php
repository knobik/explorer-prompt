<?php
namespace Knobik\Prompt;

use Knobik\Prompts\Themes\Default\ColumnAlign;
use Knobik\Prompts\Themes\Default\ExplorerPromptRenderer;
use Laravel\Prompts\Concerns\Scrolling;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class ExplorerPrompt extends Prompt
{
    use Scrolling;

    public const KEY_PAGE_UP = "\e[5~";
    public const KEY_PAGE_DOWN = "\e[6~";

    public string|bool $required = false;
    public array $columnOptions = [];
    protected $title;

    public function __construct(
        callable|string|null $title,
        public ?array $header = null,
        public array $items = [],
        public int $scroll = 20,
    ) {
        $this->title = $title ?? '';
        static::$themes['default'][static::class] = ExplorerPromptRenderer::class;

        $this->initializeScrolling(0);

        $this->on('key', fn($key) => match ($key) {
            Key::UP, Key::UP_ARROW, Key::LEFT, Key::LEFT_ARROW, Key::SHIFT_TAB, Key::CTRL_P, Key::CTRL_B, 'k', 'h' => $this->highlight(
                max(0, $this->highlighted - 1)
            ),
            Key::DOWN, Key::DOWN_ARROW, Key::RIGHT, Key::RIGHT_ARROW, Key::TAB, Key::CTRL_N, Key::CTRL_F, 'j', 'l' => $this->highlight(
                min(count($this->items) > 0 ? count($this->items) - 1 : 0, $this->highlighted + 1)
            ),
            Key::oneOf([Key::HOME, Key::CTRL_A], $key) => $this->highlight(0),
            Key::oneOf([Key::END, Key::CTRL_E], $key) => $this->highlight(count($this->items) - 1),
            static::KEY_PAGE_UP => $this->highlight(max(0, $this->highlighted - $this->scroll)),
            static::KEY_PAGE_DOWN => $this->highlight(min(count($this->items) - 1, $this->highlighted + $this->scroll)),
            Key::ENTER => $this->submit(),
            default => null,
        });
    }

    protected function eraseNewLine(): void
    {
        $this->moveCursor(-999, -1);
    }

    public function fullscreen(): static
    {
        $this->scroll = $this->terminal()->lines() - (4 + ($this->header ? 1 : 0));

        return $this;
    }

    public function value(): mixed
    {
        $keys = array_keys($this->items);

        return $keys[$this->highlighted] ?? null;
    }

    /**
     * The currently visible options.
     *
     * @return array<int|string, string>
     */
    public function visible(): array
    {
        return array_slice($this->items, $this->firstVisible, $this->scroll, preserve_keys: true);
    }

    public function getTitle(): callable|string
    {
        return $this->title;
    }

    public function setTitle(callable|string $title): void
    {
        $this->title = $title;
    }

    public function setColumnOptions(int $column, int $width = null, ColumnAlign $align = ColumnAlign::LEFT): self
    {
        $this->columnOptions[$column] = [
            'width' => $width,
            'align' => $align,
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
}
