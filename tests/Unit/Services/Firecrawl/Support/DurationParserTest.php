<?php

use App\Services\Firecrawl\Support\DurationParser;

it('parses persian digits', function () {
    expect(DurationParser::hours('۱۲ ساعت'))->toBe(12);
    expect(DurationParser::hours('۳ ساعت'))->toBe(3);
    expect(DurationParser::hours('24 hour'))->toBe(24);
    expect(DurationParser::hours(null))->toBe(24);
});
