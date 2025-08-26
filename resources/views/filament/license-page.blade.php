<x-filament::page>
    <div class="space-y-6">
        <div class="prose max-w-none">
            <h2>License</h2>
            <p>
                Device: {{ app(\GustavoCaiano\Windclient\Windclient::class)->deviceFingerprint() }}
            </p>
            @php($state = app(\GustavoCaiano\Windclient\Windclient::class)->readState())
            <ul class="text-sm">
                <li>Activation ID: {{ $state['activation_id'] ?? '—' }}</li>
                <li>Lease expires at: {{ $state['lease_expires_at'] ?? '—' }}</li>
                <li>Status: {{ app(\GustavoCaiano\Windclient\Windclient::class)->isLicensed() ? 'Active' : 'Not active' }}</li>
            </ul>
        </div>

        <div>
            {{ $this->form }}
            <button type="button" wire:click="submit" class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded">
                Activate
            </button>
            <button type="button" wire:click="$refresh" class="mt-4 ml-2 inline-flex items-center px-4 py-2 bg-gray-200 text-gray-900 rounded">
                Refresh
            </button>
        </div>
    </div>
</x-filament::page>


