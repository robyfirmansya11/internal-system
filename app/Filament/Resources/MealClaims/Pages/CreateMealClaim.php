<?php

namespace App\Filament\Resources\MealClaims\Pages;

use App\Filament\Resources\MealClaims\MealClaimResource;
use App\Services\ReceiptOcrService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateMealClaim extends CreateRecord
{
    protected static string $resource = MealClaimResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $department = $user->departments->first();

        if (! $department) {
            Notification::make()
                ->title('Meal Claim cannot be submitted')
                ->body('Your account is not assigned to a department.')
                ->danger()
                ->send();
            $this->halt();
        }

        return array_merge($data, [
            'user_id' => $user->id,
            'department_id' => $department->id,
            'status' => 'Submitted',
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $ocr = app(ReceiptOcrService::class);

        if (! $ocr->isAvailable()) {
            return;
        }

        $summary = $ocr->processClaim($this->record->load('items'));

        Notification::make()
            ->title('Receipt scan completed')
            ->body("{$summary['processed']} processed, {$summary['needs_review']} need review, {$summary['failed']} failed.")
            ->{$summary['failed'] > 0 || $summary['needs_review'] > 0 ? 'warning' : 'success'}()
            ->send();
    }
}
