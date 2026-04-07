<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Enums\DocumentStatus;
use App\Enums\SyncStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;


class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
               Section::make('Caricamento e Informazioni Principali')
                    ->schema([
                        // Upload tramite Spatie associato direttamente al disco S3
                        SpatieMediaLibraryFileUpload::make('file')
                            ->collection('documents')
                            ->disk('s3')
                            ->visibility('private') // I documenti DMS di solito sono privati
                            ->required()
                            ->columnSpanFull(),

                        Select::make('status')
                            ->label('Stato Documento')
                            ->options(DocumentStatus::class)
                            ->default(DocumentStatus::PENDING)
                            ->required(),

                        DatePicker::make('expires_at')
                            ->label('Data di Scadenza')
                            ->native(false),

                        Textarea::make('notes')
                            ->label('Note Interne')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Metadati Processati (AI/OCR)')
                    ->schema([
                        // KeyValue o JsonEditor per gestire il campo JSON nel database in modo umano
                        KeyValue::make('metadata')
                            ->label('Dati estratti')
                            ->keyLabel('Attributo')
                            ->valueLabel('Valore')
                            ->addActionLabel('Aggiungi metadato')
                            ->columnSpanFull(),
                    ])

            ]);
    }
}
