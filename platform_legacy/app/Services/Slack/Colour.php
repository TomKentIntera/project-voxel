<?php

namespace App\Services\Slack;

enum Colour
{
    case Blue;
    case Red;
    case White;

    public function toEmojiColour(): string
    {
        return match($this) 
        {
            Colour::Blue => 'b',   
            Colour::Red => 'r',   
            Colour::White => 'w',   
        };
    }

}