<?php

namespace App\Services\Slack;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use App\Services\Slack\Colour;

use App\Services\Log\Service as LogService;

class Service
{

    private LogService $logger;

    public function __construct(LogService $logger) {
        $this->logger = $logger;
    }

    public function generateProgressBar(int $value, int $maxValue = 100, $segments = 10, Colour $color = Colour::Blue): string 
    {
        $emojiString = "";

        $whiteEmoji = Colour::White;

        // Get the total filled segments
        $nPerSegment = round($maxValue / $segments);
        $filledSegments = intdiv($value, $nPerSegment);

        // Calculate what proportion of unfilled segment is left
        $totalFilledValue = $filledSegments * $nPerSegment;
        $remaining = $value - $totalFilledValue;
        $middleRatio = round($remaining / $nPerSegment * 4);
  
        $ratioFilledString = ':pb-'.str_repeat($color->toEmojiColour(), $middleRatio).str_repeat(Colour::White->toEmojiColour(), (4-$middleRatio)).':';

        // Add the caps
        $startCap = '';
        if($value == 0) {
            $startCap = ':pb-w-a:';
        } else {
            $startCap = ':pb-'.$color->toEmojiColour().'-a:';
        }

        $endCap = ':pb-w-z:';
        if($value == $maxValue) {
            $endCap = ':pb-'.$color->toEmojiColour().'-z:';
        }

        $unfilledSegments = $segments - $filledSegments;
        

        if($middleRatio > 0) {
            $unfilledSegments -= 1;
        }

        if($middleRatio == 0) {
            $ratioFilledString = '';
        }

        // components
        $filledSegmentEmoji = ':pb-'.str_repeat($color->toEmojiColour(), 4).':';
        $unfilledSegmentEmoji = ':pb-'.str_repeat('w', 4).':';

        $emojiString = '';
        $filledString = '';
        $unfilledString = '';

        if($filledSegments > 0) {
            $filledString = str_repeat($filledSegmentEmoji, $filledSegments);
        }

        if($unfilledSegments > 0) {
            $unfilledString = str_repeat($unfilledSegmentEmoji, $unfilledSegments);
        }

        

        $emojiString = sprintf('%s%s%s%s%s',
            $startCap,
            $filledString,
            $ratioFilledString,
            $unfilledString,
            $endCap
        );

        $this->logger->log([
            'startCap' => $startCap,
         'endCap' => $endCap,
         'nPerSegment' => $nPerSegment,
         'filledSegments' => $filledSegments,
         'remaining' => $remaining,
         'middleRatio' => $middleRatio,
         'value' => $value,
         'maxValue' => $maxValue,
         'segments' => $segments,
         'unfilledSegments' => $unfilledSegments,
         'ratioFilledString' => $ratioFilledString,
         'filledSegmentEmoji' => $filledSegmentEmoji,
         'emojiString' => $emojiString
        ]);

        return $emojiString;
    }
}