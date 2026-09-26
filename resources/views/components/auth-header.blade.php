@props([
    'title',
    'description',
])

<div class="flex w-full flex-col">
    <h1 class="serif text-3xl leading-tight text-ink">{{ $title }}</h1>
    <p class="mt-2 text-sm text-ink/70">{{ $description }}</p>
</div>
