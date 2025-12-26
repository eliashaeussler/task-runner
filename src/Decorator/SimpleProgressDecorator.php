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

namespace EliasHaeussler\TaskRunner\Decorator;

use Throwable;

/**
 * SimpleProgressDecorator.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final readonly class SimpleProgressDecorator implements ProgressDecorator
{
    public function progress(string $message, bool &$newLine = false): string
    {
        return $message.'... ';
    }

    public function done(mixed $result = null): string
    {
        return '<info>Done</info>';
    }

    public function failed(?Throwable $exception = null): string
    {
        return '<error>Failed</error>';
    }
}
