<?php

namespace Knobik\Prompts\Themes\Default;

enum ColumnAlign
{
    case LEFT;
    case CENTER;
    case RIGHT;

    public function toPadding(): int
    {
        return match($this) {
            self::LEFT => STR_PAD_RIGHT,
            self::CENTER => STR_PAD_BOTH,
            self::RIGHT => STR_PAD_LEFT,
        };
    }
}
