<div class="flex flex-wrap items-end gap-3">
    <div class="mr-auto">
        <h1 class="font-display text-[2rem] leading-tight">{{ __('Nursery') }}</h1>
        <p class="text-ink-2">{{ __('Counter sales and stock. Money goes into the Nursery book.') }}</p>
    </div>
    @can('viewAny', \App\Models\Plant::class)
        <a href="{{ route('admin.plants.index') }}" class="btn btn-line btn-sm"><x-icon name="flower-2" />{{ __('Plant catalogue') }}</a>
    @endcan
</div>
<nav class="flex gap-2 mt-4" aria-label="{{ __('Nursery') }}">
    <a href="{{ route('admin.nursery.index') }}" @if ($tab === 'counter') aria-current="page" @endif class="chip h-9 px-3.5 text-sm border {{ $tab === 'counter' ? 'bg-tulsi text-white border-tulsi' : 'border-rule bg-card' }}"><x-icon name="shopping-cart" />{{ __('Counter sale') }}</a>
    <a href="{{ route('admin.nursery.stock') }}" @if ($tab === 'stock') aria-current="page" @endif class="chip h-9 px-3.5 text-sm border {{ $tab === 'stock' ? 'bg-tulsi text-white border-tulsi' : 'border-rule bg-card' }}"><x-icon name="boxes" />{{ __('Stock') }}</a>
</nav>
