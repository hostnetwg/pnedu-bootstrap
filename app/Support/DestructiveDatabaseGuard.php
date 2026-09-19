<?php

namespace App\Support;

use Illuminate\Console\Events\CommandStarting;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Blokuje migrate:fresh / refresh / reset i db:wipe na bazach z danymi.
 * Wolno tylko bazie `testing` albo gdy ALLOW_DESTRUCTIVE_DB=true.
 */
class DestructiveDatabaseGuard
{
    public const BLOCKED_COMMANDS = [
        'migrate:fresh',
        'migrate:refresh',
        'migrate:reset',
        'db:wipe',
    ];

    public static function register(): void
    {
        \Illuminate\Support\Facades\Event::listen(
            CommandStarting::class,
            [self::class, 'handle']
        );
    }

    public static function handle(CommandStarting $event): void
    {
        $command = (string) $event->command;
        if (! self::isBlockedCommand($command)) {
            return;
        }

        if (self::isBypassEnabled()) {
            return;
        }

        $database = $event->input instanceof InputInterface
            ? self::targetDatabaseName($event->input)
            : (string) config('database.connections.'.config('database.default').'.database');
        if (! self::isProtectedDatabase($database)) {
            return;
        }

        $event->output?->writeln('');
        $event->output?->writeln('<error>ZABLOKOWANE: '.$command.' na bazie „'.$database.'”.</error>');
        $event->output?->writeln('Ta komenda kasuje tabele. Wolno jej tylko na bazie <comment>testing</comment>.');
        $event->output?->writeln('Testy: <comment>sail test</comment>. Świadomy wyjątek: ALLOW_DESTRUCTIVE_DB=true (nigdy na produkcji).');
        $event->output?->writeln('');

        throw new \RuntimeException(
            'ZABLOKOWANE: '.$command.' na bazie „'.$database.'”. Wolno tylko na bazie testing.'
        );
    }

    public static function isBlockedCommand(string $command): bool
    {
        $command = strtolower(trim($command));
        if (in_array($command, self::BLOCKED_COMMANDS, true)) {
            return true;
        }

        return str_ends_with($command, '\\freshcommand')
            || str_ends_with($command, '\\refreshcommand')
            || str_ends_with($command, '\\resetcommand')
            || str_ends_with($command, '\\wipecommand')
            || str_ends_with($command, 'freshcommand')
            || str_ends_with($command, 'wipecommand');
    }

    public static function isProtectedDatabase(?string $database): bool
    {
        $database = strtolower(trim((string) $database));

        if ($database === '' || $database === 'testing' || $database === ':memory:') {
            return false;
        }

        return true;
    }

    public static function isBypassEnabled(): bool
    {
        return filter_var(env('ALLOW_DESTRUCTIVE_DB', false), FILTER_VALIDATE_BOOLEAN);
    }

    public static function targetDatabaseName(InputInterface $input): string
    {
        $connection = $input->getParameterOption('--database', null, true);
        if (! is_string($connection) || $connection === '') {
            $connection = (string) config('database.default');
        }

        return (string) config('database.connections.'.$connection.'.database', '');
    }
}
