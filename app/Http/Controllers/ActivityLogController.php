<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAccess($request->user());

        $filters = $request->validate([
            'role' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'event' => ['nullable', 'string', 'max:255'],
        ]);

        $query = Activity::query()->with(['causer']);

        if (! empty($filters['role'])) {
            $query->whereJsonContains('properties->actor->roles', $filters['role']);
        }

        if (! empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($builder) use ($term) {
                $builder->where('description', 'like', $term)
                    ->orWhere('event', 'like', $term)
                    ->orWhere('properties->actor->name', 'like', $term);
            });
        }

        if (! empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (! empty($filters['date_from'])) {
            $from = Carbon::parse($filters['date_from'])->startOfDay();
            $query->where('created_at', '>=', $from);
        }

        if (! empty($filters['date_to'])) {
            $to = Carbon::parse($filters['date_to'])->endOfDay();
            $query->where('created_at', '<=', $to);
        }

        $activities = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $availableRoles = Role::query()->orderBy('name')->pluck('name');
        $availableEvents = Activity::query()
            ->select('event')
            ->whereNotNull('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        return view('activity.index', [
            'activities' => $activities,
            'roles' => $availableRoles,
            'events' => $availableEvents,
            'filters' => $filters,
        ]);
    }

    private function authorizeAccess($user): void
    {
        if (! $user || ! $user->can('view activity log')) {
            abort(403, 'You are not authorised to view activity history.');
        }
    }
}
