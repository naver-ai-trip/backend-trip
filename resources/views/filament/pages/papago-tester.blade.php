<x-filament-panels::page>
    <div>
        {{ $this->form }}
    </div>

    @if($result)
        <div class="mt-6 bg-gray-50 dark:bg-gray-900 p-4 rounded-lg overflow-x-auto">
            <pre class="text-xs">{{ $result }}</pre>
        </div>
    @endif
</x-filament-panels::page>
