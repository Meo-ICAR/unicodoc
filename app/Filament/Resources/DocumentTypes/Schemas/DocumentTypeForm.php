<?php

namespace App\Filament\Resources\DocumentTypes\Schemas;

use Filament\Forms\Components\Grid;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class DocumentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('DocumentTypeTabs')
                    ->tabs([

                        // ── TAB 1: Informazioni Generali ──────────────────────────────────
                        Tab::make('Informazioni Generali')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('Identificazione')
                                    ->description('Dati base del tipo documento, codice e raggruppamento.')
                                    ->icon('heroicon-o-tag')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Nome')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('es. Carta d\'Identità'),

                                        TextInput::make('code')
                                            ->label('Codice breve')
                                            ->maxLength(50)
                                            ->placeholder('es. CI')
                                            ->helperText('Identificatore univoco breve per uso interno/API.'),

                                        TextInput::make('codegroup')
                                            ->label('Gruppo codice')
                                            ->maxLength(50)
                                            ->placeholder('es. IDENTITY'),

                                        TextInput::make('slug')
                                            ->label('Slug URI')
                                            ->required()
                                            ->maxLength(100)
                                            ->placeholder('carta-identita')
                                            ->helperText('Utilizzato come chiave nelle chiamate API.'),

                                        TextInput::make('phase')
                                            ->label('Fase del flusso')
                                            ->maxLength(50)
                                            ->placeholder('es. onboarding, review')
                                            ->helperText('A quale fase appartiene questo documento?'),

                                        TextInput::make('priority')
                                            ->label('Priorità di raccolta')
                                            ->required()
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->helperText('0 = bassa, valori maggiori = priorità crescente.'),
                                    ]),

                                Section::make('Descrizione')
                                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                                    ->schema([
                                        Textarea::make('description')
                                            ->label('Descrizione estesa')
                                            ->rows(3)
                                            ->columnSpanFull()
                                            ->placeholder('Descrizione visibile all\'operatore in fase di raccolta...'),
                                    ]),
                            ]),

                        // ── TAB 2: Applicabilità ──────────────────────────────────────────
                        Tab::make('Applicabilità')
                            ->icon('heroicon-o-users')
                            ->schema([
                                Section::make('A chi si applica questo tipo di documento?')
                                    ->description('Seleziona le entità per le quali questo documento può essere richiesto.')
                                    ->icon('heroicon-o-building-office')
                                    ->columns(3)
                                    ->schema([
                                        Toggle::make('is_person')
                                            ->label('Persone fisiche')
                                            ->inline(false),

                                        Toggle::make('is_company')
                                            ->label('Aziende')
                                            ->inline(false),

                                        Toggle::make('is_employee')
                                            ->label('Dipendenti')
                                            ->inline(false),

                                        Toggle::make('is_agent')
                                            ->label('Agenti')
                                            ->inline(false),

                                        Toggle::make('is_principal')
                                            ->label('Mandanti/Principali')
                                            ->inline(false),

                                        Toggle::make('is_client')
                                            ->label('Clienti')
                                            ->inline(false),

                                        Toggle::make('is_practice')
                                            ->label('Pratiche')
                                            ->inline(false),
                                    ]),
                            ]),

                        // ── TAB 3: Comportamento Documento ───────────────────────────────
                        Tab::make('Comportamento')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('Caratteristiche operative')
                                    ->description('Definisce come il documento deve essere trattato nel sistema.')
                                    ->icon('heroicon-o-adjustments-horizontal')
                                    ->columns(3)
                                    ->schema([
                                        Toggle::make('is_signed')
                                            ->label('Richiede firma')
                                            ->helperText('Il documento deve essere firmato?')
                                            ->inline(false),

                                        Toggle::make('is_monitored')
                                            ->label('Monitorato')
                                            ->helperText('Deve essere ricontrollato periodicamente?')
                                            ->inline(false),

                                        Toggle::make('is_sensible')
                                            ->label('Sensibile/GDPR')
                                            ->helperText('Contiene dati particolari ex Art. 9 GDPR?')
                                            ->inline(false),

                                        Toggle::make('is_template')
                                            ->label('È un template')
                                            ->helperText('Usato come modello per la generazione dinamica.')
                                            ->inline(false),

                                        Toggle::make('is_stored')
                                            ->label('Archiviato')
                                            ->helperText('Viene conservato in archivio a lungo termine?')
                                            ->inline(false),

                                        Toggle::make('is_endmonth')
                                            ->label('Scadenza fine mese')
                                            ->helperText('La scadenza si calcola a fine mese?')
                                            ->inline(false),
                                    ]),

                                Section::make('Metadati di validità')
                                    ->icon('heroicon-o-clock')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('duration')
                                            ->label('Durata validità (giorni)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->placeholder('es. 365')
                                            ->helperText('0 = nessuna scadenza automatica.'),

                                        TextInput::make('emitted_by')
                                            ->label('Ente emittente')
                                            ->maxLength(100)
                                            ->placeholder('es. Comune, Regione, Banca'),
                                    ]),

                                Section::make('Validazione tramite Regex')
                                    ->icon('heroicon-o-code-bracket')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('regex_pattern')
                                            ->label('Pattern Regex (numero doc)')
                                            ->placeholder('es. ^[A-Z]{2}[0-9]{7}$')
                                            ->helperText('Valida il formato del numero documento.'),

                                        TextInput::make('regex')
                                            ->label('Regex alternativa (contenuto)')
                                            ->placeholder('Espressione regolare sul testo estratto'),
                                    ]),
                            ]),

                        // ── TAB 4: AI / OCR ──────────────────────────────────────────────
                        Tab::make('Intelligenza Artificiale')
                            ->icon('heroicon-o-cpu-chip')
                            ->schema([
                                Section::make('Configurazione elaborazione AI')
                                    ->description('Attiva le funzionalità di analisi automatica del documento.')
                                    ->icon('heroicon-o-sparkles')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('is_AiAbstract')
                                            ->label('Abilita AI Abstract')
                                            ->helperText('Genera un riassunto automatico del documento.')
                                            ->inline(false),

                                        Toggle::make('is_AiCheck')
                                            ->label('Abilita AI Validation')
                                            ->helperText('Verifica la conformità tramite AI dopo l\'upload.')
                                            ->inline(false),
                                    ]),

                                Section::make('Prompt di estrazione')
                                    ->icon('heroicon-o-document-text')
                                    ->schema([
                                        Textarea::make('AiPattern')
                                            ->label('Pattern / Prompt AI')
                                            ->rows(5)
                                            ->columnSpanFull()
                                            ->placeholder("Descrivi qui i campi da estrarre e le regole di validazione per questo tipo di documento.\nEs: Estrai: nome, cognome, numero doc, data scadenza. Verifica: la data di scadenza non è passata.")
                                            ->helperText('Prompt da inviare all\'API OCR/LLM per questo tipo di documento.'),
                                    ]),
                            ]),

                    ])
                    ->columnSpanFull(),
            ]);
    }
}
