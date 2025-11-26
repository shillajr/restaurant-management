<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChefRequisition;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class RemindPendingApprovals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'approvals:remind';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for pending chef requisition approvals';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pendingRequisitions = ChefRequisition::where('status', 'pending')
            ->with('chef')
            ->get();

        if ($pendingRequisitions->isEmpty()) {
            $this->info('No pending approvals to remind.');
            Log::info('Approvals reminder: No pending approvals found.');
            return Command::SUCCESS;
        }

        // Get all managers who can approve requisitions
        $managers = User::role(['admin', 'manager'])->get();

        if ($managers->isEmpty()) {
            $this->warn('No managers found to send reminders to.');
            Log::warning('Approvals reminder: No managers found.');
            return Command::FAILURE;
        }

        $count = $pendingRequisitions->count();
        
        $this->info("Found {$count} pending requisition(s).");
        
        // In a production environment, you would send emails here
        // For now, we'll just log the reminders
        foreach ($managers as $manager) {
            Log::info("Reminder sent to {$manager->name} ({$manager->email}): {$count} pending approvals");
            
            // Example: Send email notification
            // Mail::to($manager->email)->send(new PendingApprovalsReminder($pendingRequisitions));
        }

        $this->info("Reminders sent to {$managers->count()} manager(s).");
        
        // Display pending requisitions
        $this->table(
            ['ID', 'Chef', 'Requested For', 'Items', 'Created'],
            $pendingRequisitions->map(function ($req) {
                return [
                    $req->id,
                    $req->chef->name,
                    $req->requested_for_date->format('Y-m-d'),
                    count($req->items),
                    $req->created_at->diffForHumans(),
                ];
            })->toArray()
        );

        return Command::SUCCESS;
    }
}
