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

namespace EliasHaeussler\TaskRunner\Tests;

use EliasHaeussler\TaskRunner as Src;
use PHPUnit\Framework;

/**
 * RunnerContextTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\RunnerContext::class)]
final class RunnerContextTest extends Framework\TestCase
{
    private Src\RunnerContext $subject;

    public function setUp(): void
    {
        $this->subject = new Src\RunnerContext();
    }

    #[Framework\Attributes\Test]
    public function markAsSuccessfulUpdatesSuccessState(): void
    {
        self::assertNull($this->subject->successful);

        $this->subject->markAsSuccessful();

        self::assertTrue($this->subject->successful);
    }

    #[Framework\Attributes\Test]
    public function markAsFailedUpdatesSuccessState(): void
    {
        self::assertNull($this->subject->successful);

        $this->subject->markAsFailed();

        self::assertFalse($this->subject->successful);
    }
}
