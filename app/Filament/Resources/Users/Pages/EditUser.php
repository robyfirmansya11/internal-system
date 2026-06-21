<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Hapus foto lama kalau diganti
        if (
            $this->record->foto &&
            isset($data['foto']) &&
            $data['foto'] !== $this->record->foto
        ) {
            Storage::disk('public')->delete($this->record->foto);

            // Optimize foto baru
            $data['foto'] = $this->optimizeFoto($data['foto']);
        }

        return $data;
    }

    protected function optimizeFoto(string $path): string
    {
        $fullPath = Storage::disk('public')->path($path);

        Image::read($fullPath)
            ->cover(400, 400)
            ->toJpeg(80)
            ->save($fullPath);

        return $path;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
