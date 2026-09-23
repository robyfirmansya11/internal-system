<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait AuthorizesDocumentAccess
{
    protected function authorizeDocumentAccess(Model $record): void
    {
        /** @var User $viewer */
        $viewer = auth()->user();
        $owner = $record->user;

        $mayView = $viewer
            && ($record->user_id === $viewer->id
                || $viewer->isAdmin()
                || $viewer->isSuperadmin()
                || $owner?->profile?->atasan_id === $viewer->id
                || (method_exists($record, 'canBeApprovedBy') && $record->canBeApprovedBy($viewer)));

        abort_unless($mayView, 403);
    }
}
