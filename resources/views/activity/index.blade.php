@extends('layouts.app')

@section('title', 'Activity Log')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-10">
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Activity Log</h1>
                <p class="mt-1 text-sm text-gray-600">Track actions across the workspace and review which roles performed them.</p>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('activity-log.index') }}" class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label for="search" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search</label>
                    <input type="text" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Actor, description, event" class="w-full rounded-md border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
                </div>
                <div>
                    <label for="role" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Role</label>
                    <select id="role" name="role" class="w-full rounded-md border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
                        <option value="">All roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ ucfirst($role) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="event" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Event</label>
                    <select id="event" name="event" class="w-full rounded-md border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
                        <option value="">All events</option>
                        @foreach($events as $event)
                            <option value="{{ $event }}" @selected(($filters['event'] ?? '') === $event)>{{ $event }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date_from" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full rounded-md border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
                </div>
                <div>
                    <label for="date_to" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full rounded-md border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
                </div>
                <div class="flex items-end gap-3 lg:col-span-5">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-200">
                        Apply filters
                    </button>
                    <a href="{{ route('activity-log.index') }}" class="text-sm font-medium text-amber-700 hover:text-amber-900">Reset</a>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">When</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Actor</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Role(s)</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Description</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Event</th>
                            <th class="px-4 py-2 text-left font-semibold text-gray-600">Subject</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($activities as $activity)
                            @php
                                $actor = collect($activity->properties['actor'] ?? []);
                                $roles = collect($actor->get('roles', []))->map(fn($role) => ucfirst($role))->implode(', ');
                                $subjectLabel = $activity->subject ? class_basename($activity->subject_type) . ' #' . $activity->subject_id : '—';
                            @endphp
                            <tr class="bg-white">
                                <td class="px-4 py-3 align-top text-gray-600">{{ $activity->created_at?->format('M d, Y h:i A') }}</td>
                                <td class="px-4 py-3 align-top text-gray-900 font-medium">{{ $actor->get('name', 'System') }}</td>
                                <td class="px-4 py-3 align-top text-gray-600">{{ $roles ?: '—' }}</td>
                                <td class="px-4 py-3 align-top text-gray-700">{{ $activity->description }}</td>
                                <td class="px-4 py-3 align-top text-gray-600">{{ $activity->event ?? '—' }}</td>
                                <td class="px-4 py-3 align-top text-gray-600">{{ $subjectLabel }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">No activity recorded for the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 bg-gray-50 px-4 py-3">{{ $activities->links() }}</div>
        </div>
    </div>
</div>
@endsection
