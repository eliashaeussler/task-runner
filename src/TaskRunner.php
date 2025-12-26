<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/task-runner".
 *
 * Copyright (C) 2025 Elias Häußler <elias@haeussler.dev>
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
        private Console\Output\OutputInterface $output,
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
        $taskOutput = new Console\Output\BufferedOutput(
            $this->output->getVerbosity(),
            $this->output->isDecorated(),
            $this->output->getFormatter(),
        );
        $context = new RunnerContext($taskOutput);
        $newLine = false;
        $decoratedMessage = $this->progressDecorator->progress($message, $newLine);

        $this->output->write($decoratedMessage, $newLine, $verbosity);

        try {
            $isVoidReturn = $this->isVoidReturn($task);
            $returnValue = (static fn () => $task($context))();
            $taskResult = TaskResult::fromContext($context);

            if (TaskResult::Success === $taskResult) {
                $this->output->writeln($this->progressDecorator->done($returnValue), $verbosity);
            } else {
                $this->output->writeln($this->progressDecorator->failed(), $verbosity);
            }

            if (!$isVoidReturn) {
                return $returnValue;
            }

            return $taskResult;
        } catch (Throwable $exception) {
            $this->output->writeln($this->progressDecorator->failed($exception), $verbosity);

            // Early return if exceptions should not be re-thrown
            if (!$context->throwExceptions) {
                return TaskResult::Failure;
            }

            throw $exception;
        } finally {
            $this->output->write($taskOutput->fetch());
        }
    }

    private function isVoidReturn(Closure $closure): bool
    {
        $reflection = new ReflectionFunction($closure);
        $returnType = $reflection->getReturnType();

        return $returnType instanceof ReflectionNamedType && 'void' === $returnType->getName();
    }
}
