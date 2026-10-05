<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Enums\DocumentStatus;
use App\Filament\Resources\Documents\DocumentResource;
use App\Models\DocumentType;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard\Step;

class CreateDocument extends CreateRecord
{
    use HasWizard;

    protected static string $resource = DocumentResource::class;

    /**
     * I 3 step del wizard di creazione documento.
     * Step 1: Selezione tipo documento con anteprima dei suoi attributi
     * Step 2: Upload file e dettagli documentali (pre-popolati dal tipo)
     * Step 3: Riepilogo metadati e conferma
     */
    protected function getSteps(): array
    {
        return [

            // ── STEP 1: Selezione Tipo Documento ─────────────────────────────
            Step::make('Tipo Documento')
                ->description('Seleziona il tipo e le proprietà verranno pre-compilate')
                ->icon('heroicon-o-document-magnifying-glass')
                ->schema([
                    Section::make('Classificazione')
                        ->schema([
                            Select::make('document_type_id')
                                ->label('Tipo Documento')
                                ->relationship('documentType', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live() // Aggiorna il form in tempo reale al cambio
                                ->afterStateUpdated(function (?int $state, Set $set) {
                                    if (! $state) {
                                        return;
                                    }

                                    // Carica il tipo documento selezionato
                                    $type = DocumentType::find($state);
                                    if (! $type) {
                                        return;
                                    }

                                    // ── Eredita i flag booleani ──────────────────
                                    $set('is_signed',   $type->is_signed);
                                    $set('is_template', $type->is_template);
                                    $set('is_endMonth', $type->is_endmonth);

                                    // ── Eredita chi emette il documento ──────────
                                    if ($type->emitted_by) {
                                        $set('emitted_by', $type->emitted_by);
                                    }

                                    // ── Calcola la scadenza da duration (giorni) ─
                                    if ($type->duration) {
                                        $expiresAt = Carbon::now()->addDays($type->duration);

                                        // Se is_endmonth, porta la scadenza a fine mese
                                        if ($type->is_endmonth) {
                                            $expiresAt = $expiresAt->endOfMonth();
                                        }

                                        $set('expires_at', $expiresAt->toDateString());
                                    }

                                    // ── Imposta la collection Spatie dal slug ─────
                                    if ($type->slug) {
                                        $set('spatie_collection', $type->slug);
                                    }
                                }),

                            // Anteprima dinamica degli attributi del tipo selezionato
                            Placeholder::make('type_preview')
                                ->label('Attributi ereditati')
                                ->content(function (Get $get): string {
                                    $typeId = $get('document_type_id');
                                    if (! $typeId) {
                                        return 'Seleziona un tipo documento per vedere i dettagli.';
                                    }

                                    $type = DocumentType::find($typeId);
                                    if (! $type) {
                                        return '—';
                                    }

                                    $flags = collect([
                                        'Richiede firma'    => $type->is_signed,
                                        'È un template'     => $type->is_template,
                                        'Dati sensibili'    => $type->is_sensible,
                                        'Monitorato'        => $type->is_monitored,
                                        'AI Abstract'       => $type->is_AiAbstract,
                                        'AI Validation'     => $type->is_AiCheck,
                                        'Fine mese'         => $type->is_endmonth,
                                    ])
                                    ->filter()
                                    ->keys()
                                    ->join(', ');

                                    $duration = $type->duration
                                        ? "Validità: {$type->duration} giorni"
                                        : 'Nessuna scadenza automatica';

                                    $emittedBy = $type->emitted_by
                                        ? "Ente emittente: {$type->emitted_by}"
                                        : '';

                                    return collect([$duration, $emittedBy, $flags ? "Flag: {$flags}" : ''])
                                        ->filter()
                                        ->join(' | ');
                                })
                                ->columnSpanFull(),
                        ]),
                ]),

            // ── STEP 2: Caricamento File e Dettagli ──────────────────────────
            Step::make('Caricamento')
                ->description('Carica il file e verifica i dati pre-compilati')
                ->icon('heroicon-o-cloud-arrow-up')
                ->schema([
                    Section::make('File Documento')
                        ->schema([
                            SpatieMediaLibraryFileUpload::make('file')
                                ->label('File (PDF, immagine)')
                                ->collection('documents')
                                ->disk('s3')
                                ->visibility('private')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                                ->maxSize(20480) // 20 MB
                                ->columnSpanFull(),

                            TextInput::make('document_url')
                                ->label('URL alternativo documento')
                                ->url()
                                ->helperText('Opzionale: URL esterno se il file non è su S3 (es. SharePoint).')
                                ->columnSpanFull(),
                        ]),

                    Section::make('Dettagli Documentali')
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->label('Nome documento')
                                ->maxLength(255)
                                ->placeholder('es. Carta identità Mario Rossi'),

                            TextInput::make('docnumber')
                                ->label('Numero documento')
                                ->maxLength(100)
                                ->placeholder('es. CA00000AB'),

                            Select::make('status')
                                ->label('Stato iniziale')
                                ->options(DocumentStatus::class)
                                ->default(DocumentStatus::PENDING)
                                ->required(),

                            TextInput::make('emitted_by')
                                ->label('Ente emittente')
                                ->maxLength(100)
                                ->helperText('Pre-compilato dal tipo documento, modificabile.'),

                            DatePicker::make('emitted_at')
                                ->label('Data emissione')
                                ->native(false)
                                ->displayFormat('d/m/Y'),

                            DatePicker::make('expires_at')
                                ->label('Data scadenza')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->helperText('Pre-calcolata dalla durata del tipo documento.'),
                        ]),

                    Section::make('Flag Ereditati')
                        ->description('Verificа i flag ereditati dal tipo documento — modificabili.')
                        ->columns(3)
                        ->collapsed() // Collassata di default, ci pensano i default
                        ->schema([
                            Toggle::make('is_signed')
                                ->label('Richiede firma')
                                ->inline(false),

                            Toggle::make('is_template')
                                ->label('È un template')
                                ->inline(false),

                            Toggle::make('is_endMonth')
                                ->label('Scadenza fine mese')
                                ->inline(false),
                        ]),

                    Section::make('Collegamento Entità')
                        ->description('A quale entità del gestionale appartiene questo documento?')
                        ->columns(2)
                        ->schema([
                            TextInput::make('documentable_type')
                                ->label('Tipo entità (FQCN)')
                                ->placeholder('es. App\Models\Client')
                                ->helperText('Classe del modello a cui è collegato il documento.'),

                            TextInput::make('documentable_id')
                                ->label('ID entità')
                                ->numeric()
                                ->placeholder('es. 42'),
                        ]),
                ]),

            // ── STEP 3: Note e Conferma ───────────────────────────────────────
            Step::make('Note & Conferma')
                ->description('Aggiungi note e verifica prima di salvare')
                ->icon('heroicon-o-check-circle')
                ->schema([
                    Section::make('Note e Descrizione')
                        ->schema([
                            Textarea::make('description')
                                ->label('Descrizione pubblica')
                                ->rows(3)
                                ->columnSpanFull(),

                            Textarea::make('internal_notes')
                                ->label('Note interne (non visibili al cliente)')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),

                    Section::make('Metadati iniziali (opzionale)')
                        ->description('Puoi pre-compilare i metadati manualmente; verranno sovrascritti dall\'AI.')
                        ->collapsed()
                        ->schema([
                            KeyValue::make('metadata')
                                ->label('Coppie chiave/valore')
                                ->keyLabel('Attributo')
                                ->valueLabel('Valore')
                                ->addActionLabel('Aggiungi metadato')
                                ->columnSpanFull(),
                        ]),
                ]),
        ];
    }
}
