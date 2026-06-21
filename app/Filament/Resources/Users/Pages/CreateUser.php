<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     if (! empty($data['foto'])) {
    //         $data['foto'] = $this->optimizeFoto($data['foto']);
    //     }

    //     return $data;
    // }

    protected function optimizeFoto(string $path): string
    {
        $fullPath = Storage::disk('public')->path($path);

        Image::read($fullPath)
            ->cover(400, 400)
            ->toJpeg(80) // quality 80%
            ->save($fullPath);

        return $path;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
