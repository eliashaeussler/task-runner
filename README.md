<div align="center">

# Task Runner

[![Coverage](https://img.shields.io/coverallsCoverage/github/eliashaeussler/task-runner?logo=coveralls)](https://coveralls.io/github/eliashaeussler/task-runner)
[![CGL](https://img.shields.io/github/actions/workflow/status/eliashaeussler/task-runner/cgl.yaml?label=cgl&logo=github)](https://github.com/eliashaeussler/task-runner/actions/workflows/cgl.yaml)
[![Tests](https://img.shields.io/github/actions/workflow/status/eliashaeussler/task-runner/tests.yaml?label=tests&logo=github)](https://github.com/eliashaeussler/task-runner/actions/workflows/tests.yaml)
[![Supported PHP Versions](https://img.shields.io/packagist/dependency-v/eliashaeussler/task-runner/php?logo=php)](https://packagist.org/packages/eliashaeussler/task-runner)

</div>

*Task Runner* is a simple PHP library targeted for CLI-oriented applications
and projects. It provides a progress helper for long-running CLI tasks, based
on *Symfony Console*.

## 🔥 Installation

[![Packagist](https://img.shields.io/packagist/v/eliashaeussler/task-runner?label=version&logo=packagist)](https://packagist.org/packages/eliashaeussler/task-runner)
[![Packagist Downloads](https://img.shields.io/packagist/dt/eliashaeussler/task-runner?color=brightgreen)](https://packagist.org/packages/eliashaeussler/task-runner)

```bash
composer require eliashaeussler/task-runner
```

## ⚡️ Usage

### Basic usage

A common use case is to wrap a long-running process in a
[`TaskRunner`](src/TaskRunner.php) instance, which handles progress
messages and the overall task execution:

```php
use EliasHaeussler\TaskRunner;
use Symfony\Component\Console;

// 1. Create a new TaskRunner instance
$output = new Console\Output\ConsoleOutput();
$taskRunner = new TaskRunner\TaskRunner($output);

// 2. Run the task
$taskRunner->run(
    'Downloading some large files from the internet',
    static function () {
        // Do some long-running work here...
    },
);
```

This will output the following progress message:

```
Downloading some large files from the internet... Done
```

### Progress message decorators

User-oriented progress messages are decorated by a
[`ProgressDecorator`](src/Decorator/ProgressDecorator.php) instance. By
default, a simple progress decorator is used. It displays the various steps
like follows:

* Progress start: `Downloading some large files from the internet...`
* Task successful: `Downloading some large files from the internet... Done`
* Task failed: `Downloading some large files from the internet... Failed`

It is possible to pass a customized progress decorator to the `TaskRunner`
instance:

```php
use EliasHaeussler\TaskRunner;
use Symfony\Component\Console;

// 1. Create a custom progress decorator
$decorator = new class implements TaskRunner\Decorator\ProgressDecorator
{
    public function progress(string $message, bool &$newLine = false): string
    {
        return '⏳ '.$message.'... ';
    }

    public function done(mixed $result = null): string
    {
        return '🥳'
    }

    public function failed(?Throwable $exception = null): string
    {
        return '💥';
    }
}

// 2. Create a new TaskRunner instance
$output = new Console\Output\ConsoleOutput();
$taskRunner = new TaskRunner\TaskRunner($output, $decorator);

// 3. Run the task
$taskRunner->run(
    'Downloading some large files from the internet',
    static function () {
        // Do some long-running work here...
    },
);
```

The above example will output the following progress messages:

* Progress start: `⏳ Downloading some large files from the internet...`
* Task successful: `⏳ Downloading some large files from the internet... 🥳`
* Task failed: `⏳ Downloading some large files from the internet... 💥`

### Custom output

Some long-running tasks may also produce output that should be displayed
to the user. For this usecase, the executing task receives a `RunnerContext`
instance, which holds a buffered output stream. This can be used to write
output to the console:

```php
use EliasHaeussler\TaskRunner;

$taskRunner->run(
    'Downloading some large files from the internet',
    static function (TaskRunner\RunnerContext $context) {
        // Do some long-running work here...

        $context->output->writeln('This content is displayed to the user.');
    },
);
```

The additional output will be displayed after the progress message:

```
Downloading some large files from the internet... Done
This content is displayed to the user.
```

### Error Handling

If an error occurs during the execution of the task, the `TaskRunner` will
automatically catch it, mark the test execution as failed, and by default
throw the catched exception afterwards:

```
Downloading some large files from the internet... Failed
PHP Fatal error:  Uncaught RuntimeException: Downloading failed.
```

This behavior can be customized in various ways.

#### Avoid throwing an exception

In some cases, a thrown exception may not be relevant for the user and hence
should not avoid further execution of the application. This behavior can be
controlled with the passed `RunnerContext` instance:

```php
use EliasHaeussler\TaskRunner;

$taskRunner->run(
    'Downloading some large files from the internet',
    static function (TaskRunner\RunnerContext $context) {
        $context->throwExceptions = false;

        // Do some long-running work here...

        if ($downloadFailed) {
            throw new RuntimeException('Downloading failed.');
        }
    },
);
```

In the above example, the task execution is still marked as failed, but the
exception is not thrown afterwards:

```
Downloading some large files from the internet... Failed
```

#### Control task result

In addition, it is also possible to control the task result manually, even if
no exception was thrown:

```php
use EliasHaeussler\TaskRunner;

$taskRunner->run(
    'Downloading some large files from the internet',
    static function (TaskRunner\RunnerContext $context) {
        // Do some long-running work here...

        if ($downloadFailed) {
            $context->markAsFailed();
        }
    },
);
```

In the above example, the task execution is now manually marked as failed:

```
Downloading some large files from the internet... Failed
```

#### Receive task result

If the provided task is successful, the `TaskRunner` returns the result of
the executed task:

```php
use EliasHaeussler\TaskRunner;

$files = $taskRunner->run(
    'Downloading some large files from the internet',
    static function (TaskRunner\RunnerContext $context) {
        // Do some long-running work here...

        return $files;
    },
);

// $files is now the result of the task
```

If a task does not provide a result, `null` will be returned instead:

```php
use EliasHaeussler\TaskRunner;

$files = $taskRunner->run(
    'Downloading some large files from the internet',
    static function (TaskRunner\RunnerContext $context) {
        // Do some long-running work here...
    },
);

// $files is always NULL
```

This, however, can be slightly adapted, if the task is declared as returning
`void`. In this case, the `TaskRunner` will return a dedicated
[`TaskResult`](src/TaskResult.php) object instead:

```php
use EliasHaeussler\TaskRunner;

$result = $taskRunner->run(
    'Downloading some large files from the internet',
    static function (TaskRunner\RunnerContext $context): void {
        // Do some long-running work here...
    },
);

// $result is now a TaskResult instance, e.g. TaskResult::Success
```

In case a task throws an exception and the passed `RunnerContext` was modified
to avoid throwing exceptions, the `TaskResult` will return `TaskResult::Failed`
instead:

```php
use EliasHaeussler\TaskRunner;

$result = $taskRunner->run(
    'Downloading some large files from the internet',
    static function (TaskRunner\RunnerContext $context) {
        $context->throwExceptions = false;

        // Do some long-running work here...

        if ($downloadFailed) {
            $context->markAsFailed();
        }
    },
);

// $result is now a TaskResult::Failed instance
```

## 🧑‍💻 Contributing

Please have a look at [`CONTRIBUTING.md`](CONTRIBUTING.md).

## ⭐ License

This project is licensed under [GNU General Public License 3.0 (or later)](LICENSE).
