@if ($record->lampiran_surat)
    <div class="space-y-4">
        <iframe
            src="{{ asset('storage/' . $record->lampiran_surat) }}"
            width="100%"
            height="500px"
            class="rounded-lg border">
        </iframe>

        <a href="{{ asset('storage/' . $record->lampiran_surat) }}"
           target="_blank"
           class="text-primary-600 underline">
            Open in New Tab
        </a>
    </div>
@else
    <p>No attachment available.</p>
@endif
