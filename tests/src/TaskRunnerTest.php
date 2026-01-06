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

use Composer\IO;
use EliasHaeussler\TaskRunner as Src;
use Exception;
use Generator;
use PHPUnit\Framework;
use Symfony\Component\Console;
use Throwable;

use function trim;

/**
 * TaskRunnerTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\TaskRunner::class)]
final class TaskRunnerTest extends Framework\TestCase
{
    private Console\Output\BufferedOutput $output;
    private Src\TaskRunner $subject;

    public function setUp(): void
    {
        $this->output = new Console\Output\BufferedOutput();
        $this->subject = new Src\TaskRunner($this->output);
    }

    #[Framework\Attributes\Test]
    public function runReturnsReturnValueFromTask(): void
    {
        $task = static fn () => 'Hello World!';

        $actual = $this->subject->run('Let\'s go', $task);

        /* @phpstan-ignore staticMethod.alreadyNarrowedType */
        self::assertSame('Hello World!', $actual);
    }

    /**
     * @return Generator<string, array{bool|null, Src\TaskResult}>
     */
    public static function runReturnsTaskResultOnVoidReturnDataProvider(): Generator
    {
        yield 'initial state' => [null, Src\TaskResult::Success];
        yield 'on failure' => [false, Src\TaskResult::Failure];
        yield 'on success' => [true, Src\TaskResult::Success];
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('runReturnsTaskResultOnVoidReturnDataProvider')]
    public function runReturnsTaskResultOnVoidReturn(?bool $successful, Src\TaskResult $expected): void
    {
        $task = static function (Src\RunnerContext $context) use ($successful): void {
            $context->successful = $successful;
        };

        $actual = $this->subject->run('Let\'s go', $task);

        self::assertSame($expected, $actual);
    }

    #[Framework\Attributes\Test]
    public function runReturnsNullOnVoidReturnWithoutDeclaredReturnType(): void
    {
        $task = static function () {
            // Intentionally left blank.
        };

        $actual = $this->subject->run('Let\'s go', $task);

        /* @phpstan-ignore staticMethod.impossibleType */
        self::assertNull($actual);
    }

    #[Framework\Attributes\Test]
    public function runDisplaysMessageAndShowsDoneMessageOnSuccessfulTaskExecution(): void
    {
        $task = static fn () => 'Hello World!';

        $this->subject->run('Let\'s go', $task);

        self::assertSame('Let\'s go... Done', trim($this->output->fetch()));
    }

    #[Framework\Attributes\Test]
    public function runDisplaysMessageAndShowsFailedMessageIfExceptionIsThrown(): void
    {
        $exception = new Exception('Something went wrong');
        $task = static fn () => throw $exception;

        $actual = null;

        try {
            $this->subject->run('Let\'s go', $task);
        } catch (Throwable $actual) {
        }

        self::assertSame($exception, $actual);
        self::assertSame('Let\'s go... Failed', trim($this->output->fetch()));
    }

    #[Framework\Attributes\Test]
    public function runDisplaysMessageAndShowsCustomStatusMessageIfConfigured(): void
    {
        $task = static function (Src\RunnerContext $context) {
            $context->statusMessage = 'Skipped';
        };

        $this->subject->run('Let\'s go', $task);

        self::assertSame('Let\'s go... Skipped', trim($this->output->fetch()));
    }

    #[Framework\Attributes\Test]
    public function runDoesNotThrowExceptionIfExplicitlyDisabledViaRunnerContext(): void
    {
        $exception = new Exception('Something went wrong');
        $task = static function (Src\RunnerContext $context) use ($exception) {
            $context->throwExceptions = false;

            throw $exception;
        };

        $actual = $this->subject->run('Let\'s go', $task);

        self::assertSame(Src\TaskResult::Failure, $actual);
        self::assertSame('Let\'s go... Failed', trim($this->output->fetch()));
    }

    #[Framework\Attributes\Test]
    public function runAppendsTaskOutputToProgressOutput(): void
    {
        $task = static function (Src\RunnerContext $context) {
            $context->output->writeln('Hello World!');
        };

        $this->subject->run('Let\'s go', $task);

        self::assertSame('Let\'s go... Done'.PHP_EOL.'Hello World!', trim($this->output->fetch()));
    }

    #[Framework\Attributes\Test]
    public function runAppliesTaskResultFromPassedVariables(): void
    {
        $task = static function (Src\RunnerContext $context) {
            $context->successful = false;
        };

        $this->subject->run('Let\'s go', $task);

        self::assertSame('Let\'s go... Failed', trim($this->output->fetch()));
    }

    #[Framework\Attributes\Test]
    public function runDoesNotDisplayMessageIfSeverityDoesNotMatch(): void
    {
        $task = static fn () => 'Hello World!';

        $this->subject->run('Let\'s go', $task, Console\Output\OutputInterface::VERBOSITY_VERBOSE);

        self::assertSame('', $this->output->fetch());
    }

    #[Framework\Attributes\Test]
    public function runSupportsComposerIO(): void
    {
        $output = new IO\BufferIO();
        $subject = new Src\TaskRunner($output);
        $task = static fn () => 'Hello World!';

        $subject->run('Let\'s go', $task);

        self::assertSame('Let\'s go... Done', trim($output->getOutput()));
    }

    /**
     * @return Generator<string, array{Console\Output\OutputInterface::VERBOSITY_*}>
     */
    public static function runSupportsComposerIOAndMapsVerbosityLevelDataProvider(): Generator
    {
        yield 'debug' => [Console\Output\OutputInterface::VERBOSITY_DEBUG];
        yield 'very verbose' => [Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE];
        yield 'verbose' => [Console\Output\OutputInterface::VERBOSITY_VERBOSE];
        yield 'normal' => [Console\Output\OutputInterface::VERBOSITY_NORMAL];
        yield 'quiet' => [Console\Output\OutputInterface::VERBOSITY_QUIET];
    }

    /**
     * @param Console\Output\OutputInterface::VERBOSITY_* $verbosity
     */
    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('runSupportsComposerIOAndMapsVerbosityLevelDataProvider')]
    public function runSupportsComposerIOAndMapsVerbosityLevel(int $verbosity): void
    {
        $output = new IO\BufferIO('', $verbosity);
        $subject = new Src\TaskRunner($output);
        $task = static fn () => 'Hello World!';

        $subject->run('Let\'s go', $task, $verbosity);

        self::assertSame('Let\'s go... Done', trim($output->getOutput()));
    }
}
