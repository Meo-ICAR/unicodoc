<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DocumentStatus: string implements HasLabel, HasColor, HasIcon
{
    case UPLOADED = 'uploaded';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
    case REVOKED = 'revoked';
    case PENDING = 'pending';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::UPLOADED => 'Caricato',
            self::VERIFIED => 'Approvato',
            self::REJECTED => 'Rifiutato',
            self::EXPIRED => 'Scaduto',
            self::REVOKED => 'Revocato',
            self::PENDING => 'In attesa',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::UPLOADED => 'warning',  // Mostra un badge giallo in Filament
            self::VERIFIED => 'success',  // Mostra un badge verde in Filament
            self::REJECTED => 'danger',  // Mostra un badge rosso in Filament
            self::EXPIRED => 'gray',  // Mostra un badge grigio in Filament
            self::REVOKED => 'gray',  // Mostra un badge grigio in Filament
            self::PENDING => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::UPLOADED => 'heroicon-m-clock',
            self::VERIFIED => 'heroicon-m-check-badge',
            self::REJECTED => 'heroicon-m-x-circle',
            self::EXPIRED => 'heroicon-m-exclamation-triangle',
            self::REVOKED => 'heroicon-m-x-circle',
            self::PENDING => 'heroicon-m-clock',
        };
    }
}
