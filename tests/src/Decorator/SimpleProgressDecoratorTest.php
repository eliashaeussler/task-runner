<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/task-runner".
 *
 * Copyright (C) 2025-2026 Elias Häußler <elias@haeussler.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace EliasHaeussler\TaskRunner\Tests\Decorator;

use EliasHaeussler\TaskRunner as Src;
use PHPUnit\Framework;

/**
 * SimpleProgressDecoratorTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Decorator\SimpleProgressDecorator::class)]
final class SimpleProgressDecoratorTest extends Framework\TestCase
{
    private Src\Decorator\SimpleProgressDecorator $subject;

    public function setUp(): void
    {
        $this->subject = new Src\Decorator\SimpleProgressDecorator();
    }

    #[Framework\Attributes\Test]
    public function progressReturnsMessageWithAppendedEllipsis(): void
    {
        self::assertSame(
            'Do something... ',
            $this->subject->progress('Do something'),
        );
    }

    #[Framework\Attributes\Test]
    public function doneReturnsDecoratedDoneMessage(): void
    {
        self::assertSame(
            '<info>Done</info>',
            $this->subject->done(),
        );
    }

    #[Framework\Attributes\Test]
    public function failedReturnsDecoratedFailedMessage(): void
    {
        self::assertSame(
            '<error>Failed</error>',
            $this->subject->failed(),
        );
    }
}
