<?php

namespace Knobik\Prompts\Themes\Default;

use Knobik\Prompts\ViewerPrompt;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Concerns\DrawsScrollbars;
use Laravel\Prompts\Themes\Default\Renderer;

/**
 * @property ViewerPrompt $prompt
 */
class ViewerPromptRenderer extends Renderer
{
    use DrawsBoxes;
    use DrawsScrollbars;

    public function __invoke(ViewerPrompt $prompt): string
    {
        $this->minWidth = $this->prompt->terminal()->cols();

        if ($this->prompt->state !== 'submit') {
            $visibleLines = collect($this->prompt->lines)
                ->slice($this->prompt->firstVisible, $this->prompt->scroll);

            $body = $this->scrollbar(
                $visibleLines,
                $this->prompt->firstVisible,
                $this->prompt->scroll,
                count($this->prompt->lines),
                $this->prompt->terminal()->cols() - 6
            );

            $this->box($this->getTitle($this->prompt), $body->implode(PHP_EOL));
            $this->when($this->prompt->getHint(), fn() => $this->hint($this->prompt->getHint()));
        }

        return $this;
    }

    protected function getTitle(): string
    {
        $title = $this->prompt->getTitle();
        if (is_callable($title)) {
            $title = $title($this->prompt);
        }

        return $title;
    }
}
