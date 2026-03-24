@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="OT1 Pro" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md overflow-hidden">
            <x-app-logo-icon class="size-8 object-contain" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="OT1 Pro" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md overflow-hidden">
            <x-app-logo-icon class="size-8 object-contain" />
        </x-slot>
    </flux:brand>
@endif
