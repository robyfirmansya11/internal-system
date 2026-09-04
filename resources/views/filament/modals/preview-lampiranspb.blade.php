<div>
    @if($record->lampiran)
        @php
            $extension = strtolower(pathinfo($record->lampiran, PATHINFO_EXTENSION));
            $fileUrl = asset('storage/'.$record->lampiran);
            $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
            $isPdf = $extension === 'pdf';
        @endphp

        @if($isImage)
            <img src="{{ $fileUrl }}" alt="Attachment" style="max-width: 100%; height: auto; border-radius: 8px;">
        @elseif($isPdf)
            <iframe
                src="{{ $fileUrl }}"
                style="width: 100%; height: 70vh; border: none; border-radius: 8px;"
            ></iframe>
        @else
            <div style="text-align: center; padding: 24px;">
                    <p style="margin-bottom: 12px; color: #6b7280;">
                        Preview tidak tersedia untuk tipe file ini.
                </p>
                <a href="{{ $fileUrl }}" target="_blank"
                   style="color: #2563eb; text-decoration: underline;">
                    Open / Download File
                </a>
            </div>
        @endif

        <div style="margin-top: 12px; text-align: right;">
            <a href="{{ $fileUrl }}" target="_blank"
               style="font-size: 12px; color: #6b7280;">
                Open in new tab &rarr;
            </a>
        </div>
    @else
        <p style="text-align: center; padding: 24px; color: #6b7280;">
            No attachment available.
        </p>
    @endif
</div>
