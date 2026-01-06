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

namespace EliasHaeussler\TaskRunner;

use Closure;
use Composer\IO;
use ReflectionFunction;
use ReflectionNamedType;
use Symfony\Component\Console;
use Throwable;

/**
 * TaskRunner.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final readonly class TaskRunner
{
    public function __construct(
        private Console\Output\OutputInterface|IO\IOInterface $output,
        private Decorator\ProgressDecorator $progressDecorator = new Decorator\SimpleProgressDecorator(),
    ) {}

    /**
     * @template T
     *
     * @param Closure(RunnerContext): T                   $task
     * @param Console\Output\OutputInterface::VERBOSITY_* $verbosity
     *
     * @return (T is void ? TaskResult : T)
     */
    public function run(
        string $message,
        Closure $task,
        int $verbosity = Console\Output\OutputInterface::VERBOSITY_NORMAL,
    ): mixed {
        $taskOutput = $this->createBufferedOutput();
        $verbosity = $this->mapVerbosityLevel($verbosity);
        $context = new RunnerContext($taskOutput);
        $newLine = false;
        $decoratedMessage = $this->progressDecorator->progress($message, $newLine);

        if ($this->output instanceof Console\Output\ConsoleOutputInterface) {
            $errorOutput = $this->output->getErrorOutput();
        } else {
            $errorOutput = $this->output;
        }

        if ($errorOutput instanceof Console\Output\OutputInterface) {
            $write = $errorOutput->write(...);
        } else {
            $write = $errorOutput->writeError(...);
        }

        $write($decoratedMessage, $newLine, $verbosity);

        try {
            $isVoidReturn = $this->isVoidReturn($task);
            $returnValue = (static fn () => $task($context))();
            $taskResult = TaskResult::fromContext($context);

            if ('' !== ($statusMessage = (string) $context->statusMessage)) {
                $write($statusMessage, true, $verbosity);
            } elseif (TaskResult::Success === $taskResult) {
                $write($this->progressDecorator->done($returnValue), true, $verbosity);
            } else {
                $write($this->progressDecorator->failed(), true, $verbosity);
            }

            if (!$isVoidReturn) {
                return $returnValue;
            }

            return $taskResult;
        } catch (Throwable $exception) {
            $write($this->progressDecorator->failed($exception), true, $verbosity);

            // Early return if exceptions should not be re-thrown
            if (!$context->throwExceptions) {
                return TaskResult::Failure;
            }

            throw $exception;
        } finally {
            $this->output->write($taskOutput->fetch(), false);
        }
    }

    private function createBufferedOutput(): Console\Output\BufferedOutput
    {
        if ($this->output instanceof IO\IOInterface) {
            return new Console\Output\BufferedOutput(
                match (true) {
                    $this->output->isDebug() => Console\Output\OutputInterface::VERBOSITY_DEBUG,
                    $this->output->isVeryVerbose() => Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE,
                    $this->output->isVerbose() => Console\Output\OutputInterface::VERBOSITY_VERBOSE,
                    default => Console\Output\OutputInterface::VERBOSITY_NORMAL,
                },
                $this->output->isDecorated(),
            );
        }

        return new Console\Output\BufferedOutput(
            $this->output->getVerbosity(),
            $this->output->isDecorated(),
            $this->output->getFormatter(),
        );
    }

    private function isVoidReturn(Closure $closure): bool
    {
        $reflection = new ReflectionFunction($closure);
        $returnType = $reflection->getReturnType();

        return $returnType instanceof ReflectionNamedType && 'void' === $returnType->getName();
    }

    /**
     * @param Console\Output\OutputInterface::VERBOSITY_* $verbosity
     *
     * @return Console\Output\OutputInterface::VERBOSITY_*|IO\IOInterface::*
     */
    private function mapVerbosityLevel(int $verbosity): int
    {
        if ($this->output instanceof Console\Output\OutputInterface) {
            return $verbosity;
        }

        return match ($verbosity) {
            Console\Output\OutputInterface::VERBOSITY_DEBUG => IO\IOInterface::DEBUG,
            Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE => IO\IOInterface::VERY_VERBOSE,
            Console\Output\OutputInterface::VERBOSITY_VERBOSE => IO\IOInterface::VERBOSE,
            Console\Output\OutputInterface::VERBOSITY_QUIET => IO\IOInterface::QUIET,
            default => IO\IOInterface::NORMAL,
        };
    }
}
