<x-filament::page>
    <div class="space-y-6">
        <div class="prose max-w-none">
            @php($state = app(\GustavoCaiano\Lakeclient\Lakeclient::class)->readState())
            <ul class="text-sm text-gray-600 dark:text-gray-400">
                <li>Activation ID: {{ $state['activation_id'] ?? '—' }}</li>
                <li>Lease expires at: {{ $state['lease_expires_at'] ?? '—' }}</li>
                <li>Status: {{ app(\GustavoCaiano\Lakeclient\Lakeclient::class)->isLicensed() ? 'Active' : 'Not active' }}</li>
            </ul>
        </div>

        <div>
            {{ $this->form }}
            <div class="flex justify-between">

                <x-filament::button type="button" wire:click="submit" class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded">
                    Activate
                </x-filament::button>
            </div>

        </div>
    </div>
</x-filament::page>


