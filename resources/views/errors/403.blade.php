@extends('layouts.app')

@section('title', __('common.errors.forbidden.title'))

@section('content')
@php
    $backUrl = url()->previous();
    if (! $backUrl || $backUrl === url()->current()) {
        $backUrl = route('dashboard');
    }
@endphp
<div class="flex min-h-[60vh] items-center justify-center px-4 py-16 sm:px-6 lg:px-8">
    <div class="w-full max-w-xl space-y-8 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100 text-red-600">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376a2.25 2.25 0 001.935 3.374h14.736a2.25 2.25 0 001.936-3.374L13.936 4.126a2.25 2.25 0 00-3.872 0L2.697 16.126zM12 15.75h.007v.007H12v-.007z" />
            </svg>
        </div>
        <div class="space-y-3">
            <p class="text-sm font-semibold uppercase tracking-wide text-red-500">403</p>
            <h1 class="text-3xl font-semibold text-gray-900">{{ __('common.errors.forbidden.title') }}</h1>
            <p class="text-base text-gray-600">{{ __('common.errors.forbidden.description') }}</p>
        </div>
        <div>
            <a href="{{ $backUrl }}"
               class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300">
                {{ __('common.actions.back') }}
            </a>
        </div>
    </div>
</div>
@endsection
