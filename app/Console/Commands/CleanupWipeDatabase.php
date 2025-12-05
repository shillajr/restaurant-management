<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Connection;

class CleanupWipeDatabase extends Command
{
    protected $signature = 'cleanup:wipe-database
        {--dry-run : Preview the wipe without removing any data}
        {--force : Skip the confirmation prompt and run immediately}
        {--report : Output table-level details while processing}';

    protected $description = 'Delete all table data and reset sequences for the default connection.';

    /**
     * Allowed environments for destructive execution.
     *
     * @var array<int, string>
     */
    protected array $allowedEnvironments = ['local', 'development', 'staging', 'testing'];

    public function handle(): int
    {
        if (App::environment('production')) {
            $this->error('Blocked: production environment detected.');
            Log::warning('cleanup:wipe-database denied in production.', [
                'env' => App::environment(),
            ]);

            return self::FAILURE;
        }

        if (! App::environment($this->allowedEnvironments)) {
            $this->error('Blocked: this command only runs in local, development, staging, or testing environments.');
            Log::warning('cleanup:wipe-database attempted in disallowed environment.', [
                'env' => App::environment(),
            ]);

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $detailed = (bool) $this->option('report');

        if (! $dryRun && ! $force && ! $this->confirm('This will wipe all tables and cannot be undone. Continue?')) {
            $this->comment('Database wipe aborted by user.');
            return self::SUCCESS;
        }

        $connection = DB::connection();
        $tables = $this->tableNames($connection);

        if ($tables->isEmpty()) {
            $this->info('No database tables were found for the default connection.');
            return self::SUCCESS;
        }

        $driver = $connection->getDriverName();
        $summary = collect();

        try {
            if (! $dryRun) {
                $this->disableConstraints($driver);
            }

            foreach ($tables as $table) {
                $count = $connection->table($table)->count();
                $summary->push([
                    'table' => $table,
                    'rows' => $count,
                    'action' => $dryRun ? 'Would delete' : 'Deleted',
                ]);

                if ($detailed) {
                    $this->line(sprintf('%s %s (%d row%s)', $dryRun ? 'Previewing' : 'Wiping', $table, $count, $count === 1 ? '' : 's'));
                }

                if ($dryRun) {
                    Log::info('cleanup:wipe-database table evaluated (dry run).', [
                        'table' => $table,
                        'rows' => $count,
                    ]);
                    continue;
                }

                $this->truncateTable($table, $driver);
                $this->resetSequence($table, $driver);
                Log::info('cleanup:wipe-database table wiped.', [
                    'table' => $table,
                    'rows' => $count,
                ]);
            }

            if (! $dryRun) {
                $this->enableConstraints($driver);
            }
        } catch (\Throwable $exception) {
            if (! $dryRun) {
                $this->enableConstraints($driver);
            }

            $this->error('Database wipe failed: ' . $exception->getMessage());
            Log::error('cleanup:wipe-database failure.', [
                'exception' => $exception,
            ]);

            return self::FAILURE;
        }

        $this->renderSummary($summary, $dryRun);

        Log::info('cleanup:wipe-database completed.', [
            'dry_run' => $dryRun,
            'env' => App::environment(),
            'tables' => $summary->map(fn (array $row) => [
                'table' => $row['table'],
                'rows' => $row['rows'],
                'action' => $row['action'],
            ])->toArray(),
        ]);

        return self::SUCCESS;
    }

    /**
     * Retrieve table names for the connection.
     */
    protected function tableNames(Connection $connection): Collection
    {
        $driver = $connection->getDriverName();

        return match ($driver) {
            'mysql' => $this->mysqlTableNames($connection),
            'pgsql' => $this->postgresTableNames($connection),
            'sqlite' => $this->sqliteTableNames($connection),
            'sqlsrv' => $this->sqlServerTableNames($connection),
            default => collect(),
        };
    }

    protected function mysqlTableNames(Connection $connection): Collection
    {
        $database = $connection->getDatabaseName();
        $results = $connection->select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");

        return collect($results)
            ->map(fn ($row) => (array) $row)
            ->map(fn (array $row) => $row['Tables_in_' . $database] ?? reset($row))
            ->filter()
            ->sort()
            ->values();
    }

    protected function postgresTableNames(Connection $connection): Collection
    {
        $results = $connection->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");

        return collect($results)
            ->map(fn ($row) => (array) $row)
            ->map(fn (array $row) => $row['tablename'] ?? reset($row))
            ->filter()
            ->sort()
            ->values();
    }

    protected function sqliteTableNames(Connection $connection): Collection
    {
        $results = $connection->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");

        return collect($results)
            ->map(fn ($row) => (array) $row)
            ->map(fn (array $row) => $row['name'] ?? reset($row))
            ->filter()
            ->sort()
            ->values();
    }

    protected function sqlServerTableNames(Connection $connection): Collection
    {
        $results = $connection->select('SELECT name FROM sys.tables');

        return collect($results)
            ->map(fn ($row) => (array) $row)
            ->map(fn (array $row) => $row['name'] ?? reset($row))
            ->filter()
            ->sort()
            ->values();
    }

    /**
     * Disable foreign key checks for supported drivers.
     */
    protected function disableConstraints(string $driver): void
    {
        Schema::disableForeignKeyConstraints();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }
    }

    /**
     * Re-enable foreign key checks for supported drivers.
     */
    protected function enableConstraints(string $driver): void
    {
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Truncate a table using driver-specific syntax.
     */
    protected function truncateTable(string $table, string $driver): void
    {
        $quoted = $this->quoteIdentifier($table, $driver);

        match ($driver) {
            'mysql' => DB::statement("TRUNCATE TABLE {$quoted}"),
            'pgsql' => DB::statement("TRUNCATE TABLE {$quoted} RESTART IDENTITY CASCADE"),
            'sqlite' => DB::table($table)->delete(),
            'sqlsrv' => DB::statement("TRUNCATE TABLE {$quoted}"),
            default => DB::table($table)->delete(),
        };
    }

    /**
     * Reset table sequences where the driver requires manual intervention.
     */
    protected function resetSequence(string $table, string $driver): void
    {
        if ($driver === 'sqlite') {
            try {
                DB::statement('DELETE FROM sqlite_sequence WHERE name = ?', [$table]);
            } catch (\Throwable $exception) {
                Log::debug('cleanup:wipe-database sequence reset skipped.', [
                    'table' => $table,
                    'driver' => $driver,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * Output the final summary table.
     */
    protected function renderSummary(Collection $summary, bool $dryRun): void
    {
        $this->newLine();
        $this->components->info(($dryRun ? 'Dry run' : 'Wipe') . ' summary');

        $this->table(
            ['Table', 'Rows', 'Action'],
            $summary->map(fn (array $row) => [
                $row['table'],
                $row['rows'],
                $row['action'],
            ])->toArray()
        );

        $total = $summary->sum(fn (array $row) => $row['rows']);
        $this->line(sprintf('Tables processed: %d | Rows %s: %d', $summary->count(), $dryRun ? 'identified' : 'removed', $total));
    }

    /**
     * Quote identifiers safely per driver.
     */
    protected function quoteIdentifier(string $table, string $driver): string
    {
        return match ($driver) {
            'mysql' => '`' . str_replace('`', '``', $table) . '`',
            'pgsql' => '"' . str_replace('"', '""', $table) . '"',
            'sqlite' => '"' . str_replace('"', '""', $table) . '"',
            'sqlsrv' => '[' . str_replace(']', ']]', $table) . ']',
            default => $table,
        };
    }
}
