<x-layouts.admin :title="$title">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="font-display text-[2rem] leading-tight">{{ $title }}</h1>
        @can('create', $modelClass)
            <a href="{{ route($routeName.'.create') }}" class="btn btn-primary"><x-icon name="plus" /><span>{{ __('Add') }}</span></a>
        @endcan
    </div>

    <div class="mt-6 overflow-x-auto rounded-xl border border-rule bg-card">
        <table class="khata-table">
            <thead><tr><th scope="col">{{ $singular }}</th><th scope="col">{{ __('Details') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col"><span class="sr-only">{{ __('Actions') }}</span></th></tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="font-semibold">{{ $row['title'] }}</td>
                        <td class="text-ink-2">{{ $row['meta'] }}</td>
                        <td><x-admin.status :published="$row['published']" /></td>
                        <td class="text-right whitespace-nowrap">
                            @can('publish', $row['record'])
                                @if ($row['published'])
                                    <form method="POST" action="{{ route($routeName.'.unpublish', $row['record']) }}" class="inline">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm">{{ __('Unpublish') }}</button></form>
                                @else
                                    <form method="POST" action="{{ route($routeName.'.publish', $row['record']) }}" class="inline">@csrf<button class="btn btn-ghost btn-sm text-tulsi">{{ __('Publish') }}</button></form>
                                @endif
                            @endcan
                            <a href="{{ route($routeName.'.edit', $row['record']) }}" class="btn btn-line btn-sm">@can('update', $row['record']){{ __('Edit') }}@else{{ __('View') }}@endcan</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-ink-2">{{ __('Nothing here yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $records->links() }}</div>
</x-layouts.admin>
