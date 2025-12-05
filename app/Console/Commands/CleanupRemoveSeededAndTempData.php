<?php

namespace App\Console\Commands;

use App\Models\ChefRequisition;
use App\Models\Item;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupRemoveSeededAndTempData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:remove-seeded-and-temp-data
        {--dry-run : Preview the cleanup work without deleting anything}
        {--force : Skip confirmation prompt and run destructive actions}
        {--report : Display detailed output for each operation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove demo seeded records and clear framework temporary files for a fresh environment.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (App::environment('production')) {
            $this->error('This cleanup command is blocked in the production environment.');
            Log::warning('cleanup:remove-seeded-and-temp-data attempted in production environment.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $detailed = (bool) $this->option('report');

        if (! $dryRun && ! $force && ! $this->confirm('This will permanently delete seeded/demo data and clear temp files. Continue?')) {
            $this->comment('Cleanup aborted by user.');
            return self::SUCCESS;
        }

        $targets = $this->targets();
        $summary = [
            'database' => collect(),
            'filesystem' => collect(),
        ];

        DB::beginTransaction();

        try {
            foreach ($targets['database'] as $target) {
                $result = $this->handleDatabaseTarget($target, $dryRun, $detailed);
                $summary['database']->push($result);
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $exception) {
            DB::rollBack();
            $this->error('Cleanup aborted due to an unexpected error: ' . $exception->getMessage());
            Log::error('cleanup:remove-seeded-and-temp-data failed', [
                'exception' => $exception,
            ]);

            return self::FAILURE;
        }

        foreach ($targets['filesystem'] as $target) {
            $summary['filesystem']->push($this->handleFilesystemTarget($target, $dryRun, $detailed));
        }

        $this->renderSummary($summary, $dryRun);

        Log::info('cleanup:remove-seeded-and-temp-data completed', [
            'dry_run' => $dryRun,
            'database_targets' => $summary['database']->map->only(['label', 'affected'])->toArray(),
            'filesystem_targets' => $summary['filesystem']->map->only(['label', 'files_removed', 'directories_removed'])->toArray(),
        ]);

        return self::SUCCESS;
    }

    /**
     * Build the database and filesystem cleanup targets.
     */
    protected function targets(): array
    {
        return [
            'database' => [
                [
                    'label' => 'Seeded items',
                    'count' => fn () => Item::where('is_seeded', true)->count(),
                    'delete' => fn () => Item::where('is_seeded', true)->delete(),
                ],
                [
                    'label' => 'Demo vendors',
                    'count' => fn () => Vendor::where('email', 'like', '%.example')->count(),
                    'delete' => fn () => Vendor::where('email', 'like', '%.example')->delete(),
                ],
                [
                    'label' => 'Demo chef requisitions',
                    'count' => fn () => ChefRequisition::whereHas('chef', function ($query) {
                        $query->where('email', 'chef@example.com');
                    })->count(),
                    'delete' => fn () => ChefRequisition::whereHas('chef', function ($query) {
                        $query->where('email', 'chef@example.com');
                    })->delete(),
                ],
                [
                    'label' => 'Demo chef & manager users',
                    'count' => fn () => User::whereIn('email', ['chef@example.com', 'manager@example.com'])->count(),
                    'delete' => fn () => User::whereIn('email', ['chef@example.com', 'manager@example.com'])->delete(),
                ],
            ],
            'filesystem' => [
                [
                    'label' => 'Framework cache',
                    'path' => storage_path('framework/cache'),
                ],
                [
                    'label' => 'Framework sessions',
                    'path' => storage_path('framework/sessions'),
                ],
                [
                    'label' => 'Framework views',
                    'path' => storage_path('framework/views'),
                ],
                [
                    'label' => 'Framework testing cache',
                    'path' => storage_path('framework/testing'),
                ],
                [
                    'label' => 'Testing storage',
                    'path' => storage_path('app/testing'),
                ],
                [
                    'label' => 'Application logs',
                    'path' => storage_path('logs'),
                ],
                [
                    'label' => 'Bootstrap cache files',
                    'path' => bootstrap_path('cache'),
                ],
            ],
        ];
    }

    /**
     * Handle a database cleanup target.
     */
    protected function handleDatabaseTarget(array $target, bool $dryRun, bool $detailed): Collection
    {
        $count = $target['count']();

        if ($count === 0) {
            $this->line($target['label'] . ': nothing to remove.');

            return collect([
                'label' => $target['label'],
                'affected' => 0,
            ]);
        }

        if ($dryRun) {
            $this->info($target['label'] . ": {$count} record(s) would be removed.");
        } else {
            $target['delete']();
            $this->info($target['label'] . ": Removed {$count} record(s).");
        }

        if ($detailed) {
            $this->line('  Query scope: ' . $this->describeDatabaseTarget($target));
        }

        return collect([
            'label' => $target['label'],
            'affected' => $count,
        ]);
    }

    /**
     * Prepare a human readable description for the database target.
     */
    protected function describeDatabaseTarget(array $target): string
    {
        return match ($target['label']) {
            'Seeded items' => 'Item::where("is_seeded", true)',
            'Demo vendors' => 'Vendor::where("email", "%.example")',
            'Demo chef requisitions' => 'ChefRequisition::whereHas("chef", email = chef@example.com)',
            'Demo chef & manager users' => 'User::whereIn("email", [chef@example.com, manager@example.com])',
            default => 'Custom query',
        };
    }

    /**
     * Handle a filesystem cleanup target.
     */
    protected function handleFilesystemTarget(array $target, bool $dryRun, bool $detailed): Collection
    {
        $filesystem = app(Filesystem::class);
        $path = $target['path'];

        if (! $filesystem->exists($path)) {
            $this->line($target['label'] . ': path missing - skipped.');

            return collect([
                'label' => $target['label'],
                'path' => $path,
                'files_removed' => 0,
                'directories_removed' => 0,
            ]);
        }

        $allFiles = collect($filesystem->allFiles($path));
        $filesToDelete = $allFiles->reject(function ($file) {
            return in_array($file->getFilename(), ['.gitignore', '.gitkeep'], true);
        });
        $ignoredCount = $allFiles->count() - $filesToDelete->count();

        if ($dryRun) {
            $message = $target['label'] . ': ' . $filesToDelete->count() . ' file(s) would be removed';

            if ($ignoredCount > 0) {
                $message .= " ({$ignoredCount} ignored)";
            }

            $this->info($message . '.');
        } else {
            foreach ($filesToDelete as $file) {
                $filesystem->delete($file->getPathname());
            }

            $message = $target['label'] . ': removed ' . $filesToDelete->count() . ' file(s)';

            if ($ignoredCount > 0) {
                $message .= " ({$ignoredCount} ignored)";
            }

            $this->info($message . '.');
        }

        if ($detailed && $filesToDelete->isNotEmpty()) {
            foreach ($filesToDelete as $file) {
                $this->line('  - ' . $file->getPathname());
            }
        }

        return collect([
            'label' => $target['label'],
            'path' => $path,
            'files_removed' => $filesToDelete->count(),
            'directories_removed' => 0,
        ]);
    }

    /**
     * Render a summary table for the command execution.
     */
    protected function renderSummary(array $summary, bool $dryRun): void
    {
        $this->newLine();
        $this->components->info(($dryRun ? 'Dry run' : 'Cleanup') . ' summary');

        $this->table(
            ['Target', 'Records'],
            $summary['database']->map(fn ($row) => [
                $row['label'],
                $row['affected'],
            ])->toArray()
        );

        $this->table(
            ['Target', 'Files', 'Directories'],
            $summary['filesystem']->map(fn ($row) => [
                $row['label'],
                $row['files_removed'],
                $row['directories_removed'],
            ])->toArray()
        );
    }
}
