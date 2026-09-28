<?php

namespace App\Filament\Resources\MealClaims\Tables;

use App\Filament\Resources\MealClaims\MealClaimResource;
use App\Models\MealClaim;
use App\Services\ReceiptOcrService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MealClaimsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('index')->label('No.')->rowIndex(),
                TextColumn::make('claim_date')->label('Submitted')->date('d M Y')->sortable(),
                TextColumn::make('user.name')->label('Employee')->searchable()->sortable(),
                TextColumn::make('department.nama_department')->label('Department')->toggleable(),
                TextColumn::make('company.kode')->label('PT')->badge()->color('gray'),
                TextColumn::make('receipt_count')->label('Receipts')->alignCenter(),
                TextColumn::make('total_amount')->label('Total Amount')->money('IDR')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Submitted' => 'Pending HRD / Finance Verification',
                        'Receipt Received' => 'Original Receipts Received',
                        'Verified' => 'Pending Finance Manager Approval',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                    'Paid', 'Approved' => 'success',
                    'Rejected' => 'danger',
                    'Returned' => 'warning',
                    'Receipt Received', 'Verified' => 'info',
                    'Cancelled' => 'gray',
                    default => 'primary',
                }),
                TextColumn::make('verified_at')->label('Verified At')->dateTime('d M Y, H:i')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paid_at')->label('Paid At')->dateTime('d M Y, H:i')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'Submitted' => 'Submitted',
                    'Receipt Received' => 'Receipt Received',
                    'Verified' => 'Verified',
                    'Approved' => 'Approved',
                    'Paid' => 'Paid',
                    'Returned' => 'Returned',
                    'Rejected' => 'Rejected',
                    'Cancelled' => 'Cancelled',
                ]),
                SelectFilter::make('company')->relationship('company', 'nama')->label('PT / Company'),
                SelectFilter::make('department')->relationship('department', 'nama_department'),
                Filter::make('claim_date')
                    ->label('Submission Date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('claim_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('claim_date', '<=', $date))),
                TrashedFilter::make(),
            ])
            ->actions([
                Action::make('view_receipts')
                    ->label('Receipts')
                    ->icon('heroicon-o-photo')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (MealClaim $record) => view('filament.meal-claims.receipts', ['record' => $record->loadMissing('items')])),
                Action::make('print')
                    ->label('Print PDF')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (MealClaim $record): string => route('meal-claim.pdf', $record))
                    ->openUrlInNewTab(),
                Action::make('edit')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->url(fn (MealClaim $record) => MealClaimResource::getUrl('edit', ['record' => $record]))
                    ->visible(fn (MealClaim $record) => $record->canBeEditedBy(auth()->user())),
                Action::make('scan_receipts')
                    ->label('Scan Receipts')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('OCR reads a draft merchant, date, and total from each receipt. Always verify the result against the photo.')
                    ->form([\Filament\Forms\Components\Toggle::make('overwrite')->label('Replace values already entered manually')->default(false)])
                    ->visible(fn (MealClaim $record): bool => $record->user_id === auth()->id() || $record->canBeManagedBy(auth()->user()))
                    ->action(function (MealClaim $record, array $data): void {
                        $ocr = app(ReceiptOcrService::class);

                        if (! $ocr->isAvailable()) {
                            Notification::make()
                                ->title('Receipt OCR is not available')
                                ->body('Install Tesseract and enable RECEIPT_OCR_ENABLED before scanning receipts.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $summary = $ocr->processClaim($record->load('items'), (bool) ($data['overwrite'] ?? false));

                        Notification::make()
                            ->title('Receipt scan completed')
                            ->body("{$summary['processed']} processed, {$summary['needs_review']} need review, {$summary['failed']} failed.")
                            ->{$summary['failed'] > 0 || $summary['needs_review'] > 0 ? 'warning' : 'success'}()
                            ->send();
                    }),
                Action::make('receive_receipts')
                    ->label('Receive Originals')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalDescription('Confirm that the original physical receipts have been received.')
                    ->visible(fn (MealClaim $record) => $record->status === 'Submitted' && $record->canBeManagedBy(auth()->user()))
                    ->action(fn (MealClaim $record) => self::run($record->receiveReceipts(auth()->user()), 'Original receipts recorded.')),
                Action::make('verify')
                    ->label('Verify')
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->form([Textarea::make('verification_note')->label('Verification Note')->rows(3)])
                    ->visible(fn (MealClaim $record) => in_array($record->status, ['Submitted', 'Receipt Received'], true) && $record->canBeManagedBy(auth()->user()))
                    ->action(fn (MealClaim $record, array $data) => self::run($record->verify(auth()->user(), $data['verification_note'] ?? null), 'Meal claim verified and ready for approval.')),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (MealClaim $record) => $record->canBeApprovedBy(auth()->user()))
                    ->action(fn (MealClaim $record) => self::run($record->approve(auth()->user()), 'Meal claim approved.')),
                Action::make('mark_paid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->form([Textarea::make('payment_note')->label('Payment Note / Reference')->rows(3)])
                    ->visible(fn (MealClaim $record) => $record->canBePaidBy(auth()->user()))
                    ->action(fn (MealClaim $record, array $data) => self::run($record->markPaid(auth()->user(), $data['payment_note'] ?? null), 'Meal claim marked as paid.')),
                Action::make('return')
                    ->label('Return')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->form([Textarea::make('note')->label('Correction Required')->required()->rows(3)])
                    ->visible(fn (MealClaim $record) => $record->canBeManagedBy(auth()->user()) && ! in_array($record->status, ['Approved', 'Paid', 'Rejected', 'Cancelled'], true))
                    ->action(fn (MealClaim $record, array $data) => self::run($record->returnForCorrection(auth()->user(), $data['note']), 'Claim returned to the employee.')),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([Textarea::make('note')->label('Reason for Rejection')->required()->rows(3)])
                    ->visible(fn (MealClaim $record) => ($record->canBeManagedBy(auth()->user()) || $record->canBeApprovedBy(auth()->user())) && ! in_array($record->status, ['Approved', 'Paid', 'Rejected', 'Cancelled'], true))
                    ->action(fn (MealClaim $record, array $data) => self::run($record->rejectClaim(auth()->user(), $data['note']), 'Meal claim rejected.')),
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (MealClaim $record) => $record->canBeCancelledBy(auth()->user()))
                    ->action(fn (MealClaim $record) => self::run($record->cancelClaim(auth()->user()), 'Meal claim cancelled.')),
            ])
            ->bulkActions([
                BulkAction::make('receive_originals')
                    ->label('Receive Selected Originals')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => auth()->user()->isFinanceOperations())
                    ->action(function ($records): void {
                        $count = 0;

                        foreach ($records as $record) {
                            if ($record->receiveReceipts(auth()->user())) {
                                $count++;
                            }
                        }

                        Notification::make()->title("{$count} original receipt(s) recorded.")->success()->send();
                    }),
                BulkAction::make('verify_selected')
                    ->label('Verify Selected')
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => auth()->user()->isFinanceOperations())
                    ->action(function ($records): void {
                        $count = 0;

                        foreach ($records as $record) {
                            if ($record->verify(auth()->user())) {
                                $count++;
                            }
                        }

                        Notification::make()->title("{$count} meal claim(s) verified.")->success()->send();
                    }),
                BulkActionGroup::make([DeleteBulkAction::make(), ForceDeleteBulkAction::make(), RestoreBulkAction::make()]),
            ]);
    }

    private static function run(bool $successful, string $message): void
    {
        Notification::make()
            ->title($successful ? $message : 'Action could not be completed.')
            ->{$successful ? 'success' : 'danger'}()
            ->send();
    }
}
