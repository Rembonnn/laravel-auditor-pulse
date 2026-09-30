<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <x-pulse::card-header
        name="Top Model Changes"
        x-bind:title="`Time: {{ number_format($time) }}ms; Run at: ${formatDate('{{ $runAt }}')};`"
        details="past {{ $this->periodForHumans() }}"
    >
        <x-slot:icon>
            <x-pulse::icons.arrows-left-right />
        </x-slot:icon>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand" wire:poll.5s="">
        @if ($changes->isEmpty())
            <x-pulse::no-results />
        @else
            <x-pulse::table>
                <colgroup>
                    <col width="100%" />
                    <col width="0%" />
                    <col width="0%" />
                </colgroup>
                <x-pulse::thead>
                    <tr>
                        <x-pulse::th>Model</x-pulse::th>
                        <x-pulse::th>Event</x-pulse::th>
                        <x-pulse::th class="text-right">Count</x-pulse::th>
                    </tr>
                </x-pulse::thead>
                <tbody>
                    @foreach ($changes as $row)
                        <tr wire:key="{{ $row->key }}-spacer" class="h-2 first:h-0"></tr>
                        <tr wire:key="{{ $row->key }}-row">
                            <x-pulse::td class="max-w-[1px]">
                                <code class="block text-xs text-gray-900 dark:text-gray-100 truncate" title="{{ $row->type }}">{{ $row->label }}</code>
                            </x-pulse::td>
                            <x-pulse::td class="text-xs text-gray-600 dark:text-gray-400">
                                {{ $row->event }}
                            </x-pulse::td>
                            <x-pulse::td numeric class="text-gray-700 dark:text-gray-300 font-bold">
                                {{ number_format($row->count) }}
                            </x-pulse::td>
                        </tr>
                    @endforeach
                </tbody>
            </x-pulse::table>
        @endif
    </x-pulse::scroll>
</x-pulse::card>
