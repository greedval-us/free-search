<?php

declare(strict_types=1);

namespace Tests\Unit\MoonShine;

use App\MoonShine\Support\Formatting\ParserRunFormatter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ParserRunFormatterTest extends TestCase
{
    #[Test]
    public function it_formats_statuses_sizes_and_errors_for_the_admin_table(): void
    {
        $formatter = new ParserRunFormatter;

        self::assertSame('success', $formatter->statusColor('completed'));
        self::assertSame('1.5 MB', $formatter->fileSize(1_572_864));
        self::assertSame('-', $formatter->fileSize(null));
        self::assertSame('-', $formatter->errorSummary(null));
        self::assertLessThanOrEqual(143, mb_strlen($formatter->errorSummary(str_repeat('x', 300))));
    }
}
