<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Enums\DocumentStatus;
use App\Enums\SyncStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

/**
 * Form di EDIT per il Document.
 * La creazione avviene tramite Wizard in CreateDocument.php
 * Questo form è usato dalla pagina EditDocument.
 */
class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('DocumentEditTabs')
                    ->tabs([

                        // ── TAB 1: File & Classificazione ────────────────────────────────
                        Tab::make('File & Classificazione')
                            ->icon('heroicon-o-document-arrow-up')
                            ->schema([
                                Section::make('File Archiviato')
                                    ->schema([
                                        SpatieMediaLibraryFileUpload::make('file')
                                            ->label('Sostituisci file')
                                            ->collection('documents')
                                            ->disk('s3')
                                            ->visibility('private')
                                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                                            ->maxSize(20480)
                                            ->helperText('Carica un nuovo file solo se vuoi sostituire quello esistente.')
                                            ->columnSpanFull(),

                                        TextInput::make('document_url')
                                            ->label('URL alternativo documento')
                                            ->url()
                                            ->helperText('URL esterno (es. SharePoint) se il file non è su S3.')
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Classificazione')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('document_type_id')
                                            ->label('Tipo Documento')
                                            ->relationship('documentType', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(),

                                        Select::make('status')
                                            ->label('Stato')
                                            ->options(DocumentStatus::class)
                                            ->required(),

                                        Select::make('sync_status')
                                            ->label('Stato Webhook')
                                            ->options(SyncStatus::class),

                                        TextInput::make('spatie_collection')
                                            ->label('Collezione Spatie')
                                            ->helperText('Chiave della collezione media. Default: slug del tipo doc.'),
                                    ]),
                            ]),

                        // ── TAB 2: Dati Documentali ──────────────────────────────────────
                        Tab::make('Dati Documento')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Section::make('Identità documento')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Nome documento')
                                            ->maxLength(255),

                                        TextInput::make('docnumber')
                                            ->label('Numero documento')
                                            ->maxLength(100),

                                        TextInput::make('emitted_by')
                                            ->label('Ente emittente')
                                            ->maxLength(100),

                                        DatePicker::make('emitted_at')
                                            ->label('Data di emissione')
                                            ->native(false)
                                            ->displayFormat('d/m/Y'),

                                        DatePicker::make('expires_at')
                                            ->label('Data di scadenza')
                                            ->native(false)
                                            ->displayFormat('d/m/Y'),

                                        DateTimePicker::make('delivered_at')
                                            ->label('Consegnato il')
                                            ->native(false),

                                        DateTimePicker::make('signed_at')
                                            ->label('Firmato il')
                                            ->native(false),
                                    ]),

                                Section::make('Collegamento Entità Polimorfica')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('documentable_type')
                                            ->label('Tipo entità (FQCN)')
                                            ->placeholder('App\Models\Client')
                                            ->helperText('Classe Eloquent a cui appartiene il documento.'),

                                        TextInput::make('documentable_id')
                                            ->label('ID entità')
                                            ->numeric(),
                                    ]),
                            ]),

                        // ── TAB 3: Flag Comportamento ────────────────────────────────────
                        Tab::make('Comportamento')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('Attributi operativi')
                                    ->columns(3)
                                    ->schema([
                                        Toggle::make('is_signed')
                                            ->label('Richiede / è firmato')
                                            ->inline(false),

                                        Toggle::make('is_template')
                                            ->label('È un template')
                                            ->inline(false),

                                        Toggle::make('is_unique')
                                            ->label('È unico per entità')
                                            ->inline(false),

                                        Toggle::make('is_endMonth')
                                            ->label('Scadenza fine mese')
                                            ->inline(false),
                                    ]),
                            ]),

                        // ── TAB 4: Note ──────────────────────────────────────────────────
                        Tab::make('Note')
                            ->icon('heroicon-o-chat-bubble-left-ellipsis')
                            ->schema([
                                Section::make('Comunicazione')
                                    ->schema([
                                        Textarea::make('description')
                                            ->label('Descrizione pubblica')
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Textarea::make('internal_notes')
                                            ->label('Note interne')
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Textarea::make('rejection_note')
                                            ->label('Motivazione rifiuto')
                                            ->rows(3)
                                            ->columnSpanFull()
                                            ->helperText('Compilare solo in caso di stato "Rifiutato".'),
                                    ]),
                            ]),

                        // ── TAB 5: AI / OCR ──────────────────────────────────────────────
                        Tab::make('Intelligenza Artificiale')
                            ->icon('heroicon-o-cpu-chip')
                            ->schema([
                                Section::make('Dati estratti dall\'elaborazione AI')
                                    ->description('Questi campi vengono popolati automaticamente dal Job asincrono.')
                                    ->schema([
                                        Textarea::make('extracted_text')
                                            ->label('Testo estratto (OCR)')
                                            ->rows(5)
                                            ->columnSpanFull()
                                            ->disabled(),

                                        Textarea::make('ai_abstract')
                                            ->label('Abstract generato dall\'AI')
                                            ->rows(4)
                                            ->columnSpanFull()
                                            ->disabled(),

                                        TextInput::make('ai_confidence_score')
                                            ->label('Score di confidenza AI (%)')
                                            ->numeric()
                                            ->disabled()
                                            ->suffix('%'),
                                    ]),

                                Section::make('Metadati JSON')
                                    ->description('Coppie chiave/valore estratte. Modificabili manualmente.')
                                    ->schema([
                                        KeyValue::make('metadata')
                                            ->label('Metadati strutturati')
                                            ->keyLabel('Campo')
                                            ->valueLabel('Valore estratto')
                                            ->addActionLabel('Aggiungi campo')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                    ])
                    ->columnSpanFull(),
            ]);
    }
}
