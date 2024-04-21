<?php

namespace Knobik\Prompts;

use Knobik\Prompts\Concerns\TypedValue;
use Knobik\Prompts\Handlers\FilterHandler;
use Knobik\Prompts\Key;
use Knobik\Prompts\Themes\Default\ColumnAlign;
use Knobik\Prompts\Themes\Default\ExplorerPromptRenderer;
use Knobik\Prompts\Themes\Default\ViewerPromptRenderer;
use Laravel\Prompts\Concerns\Scrolling;
use Laravel\Prompts\Prompt;

class ViewerPrompt extends Prompt
{
    use Scrolling;

    public string|bool $required = false;

    protected string $hint = '';

    public array $lines = [];
    protected $title;

    public function __construct(string $value, callable|string $title = '')
    {
        $this->lines = explode(PHP_EOL, $this->sanitizeAndWrap($value));
        $this->title = $title;
        $this->scroll = 0;

        static::$themes['default'][static::class] = ViewerPromptRenderer::class;

        $this->fullscreen();
        $this->initializeScrolling(0);

        $this->setupKeyHandling();
    }

    public function sanitizeAndWrap($text): string {

        $text = str_replace(["\t", "\r"], ['    ', ''], $text);

        return wordwrap($text, $this->maxLineWidth(), PHP_EOL, true);
    }

    public function getHint(): string
    {
        return $this->hint;
    }

    public function setHint(string $hint): self
    {
        $this->hint = $hint;
        $this->recalculateScroll();

        return $this;
    }

    public function getHintHeight(): int
    {
        return count(explode("\n", $this->getHint()));
    }

    public function fullscreen(): static
    {
        $this->scroll = $this->terminal()->lines() - 4;

        return $this;
    }

    public function value(): mixed
    {
        return null;
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

    /**
     * Get the entered value with a virtual cursor.
     */
    public function setCustomKeyHandler(string $key, callable $keyHandler): self
    {
        $this->customKeyHandlers[$key] = $keyHandler;

        return $this;
    }

    protected function keyUp(): void
    {
        $this->highlight(max(0, $this->highlighted - 1));
    }

    protected function keyDown(): void
    {
        $this->highlight(
            min(count($this->lines) > 0 ? count($this->lines) - 1 : 0, $this->highlighted + 1)
        );
    }

    protected function keyHome(): void
    {
        $this->highlight(0);
    }

    protected function keyEnd(): void
    {
        $this->highlight(count($this->lines) - 1);
    }

    protected function keyPageUp(): void
    {
        $this->highlight(max(0, $this->highlighted - $this->scroll));
    }

    protected function keyPageDown(): void
    {
        $this->highlight(min(count($this->lines) - 1, $this->highlighted + $this->scroll));
    }

    protected function keyEnter(): void
    {
        $this->submit();
    }

    /**
     * @return void
     */
    protected function setupKeyHandling(): void
    {
        $this->on('key', function ($key) {
            match ($key) {
                Key::UP, Key::UP_ARROW, Key::LEFT, Key::LEFT_ARROW, Key::SHIFT_TAB, Key::CTRL_P, Key::CTRL_B, 'k', 'h' => $this->keyUp(),
                Key::DOWN, Key::DOWN_ARROW, Key::RIGHT, Key::RIGHT_ARROW, Key::TAB, Key::CTRL_N, Key::CTRL_F, 'j', 'l' => $this->keyDown(),
                Key::oneOf([Key::HOME, Key::CTRL_A], $key) => $this->keyHome(),
                Key::oneOf([Key::END, Key::CTRL_E], $key) => $this->keyEnd(),
                Key::KEY_PAGE_UP => $this->keyPageUp(),
                Key::KEY_PAGE_DOWN => $this->keyPageDown(),
                Key::ENTER => $this->keyEnter(),
                default => $this->callCustomKeyHandler($key),
            };
        });
    }

    protected function callCustomKeyHandler(string $key): mixed
    {
        if (isset($this->customKeyHandlers[$key])) {
            return $this->customKeyHandlers[$key]($this, $key);
        }

        return null;
    }

    protected function maxLineWidth(): int
    {
        return $this->terminal()->cols() - 8;
    }
}
