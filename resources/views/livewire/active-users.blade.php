<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header
        name="Most Active Users (audited)"
        x-bind:title="`Time: {{ number_format($time) }}ms; Run at: ${formatDate('{{ $runAt }}')};`"
        details="past {{ $this->periodForHumans() }}"
    >
        <x-slot:icon>
            <x-pulse::icons.cursor-arrow-rays />
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
        @if ($users->isEmpty())
            <x-pulse::no-results />
        @else
            <div class="grid grid-cols-1 @lg:grid-cols-2 @3xl:grid-cols-3 @6xl:grid-cols-4 gap-2">
                @foreach ($users as $row)
                    <x-pulse::user-card wire:key="{{ $row->key }}" :user="$row->user" :name="$row->name" :extra="$row->extra">
                        <x-slot:stats>{{ number_format($row->count) }}</x-slot:stats>
                    </x-pulse::user-card>
                @endforeach
            </div>
        @endif
    </x-pulse::scroll>
</x-pulse::card>
