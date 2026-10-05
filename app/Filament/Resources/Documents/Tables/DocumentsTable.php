<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Enums\DocumentStatus;
use App\Enums\SyncStatus;
use App\Models\Document;
use App\Models\DocumentType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // ── Identificazione ───────────────────────────────────────────
                TextColumn::make('name')
                    ->label('Documento')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn($record): string => $record->docnumber ?? ''),
                TextColumn::make('documentType.name')
                    ->label('Tipo')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),
                // ── Stato con badge colorato dall'Enum ────────────────────────
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->sortable(),
                TextColumn::make('sync_status')
                    ->label('Webhook')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                // ── Date ──────────────────────────────────────────────────────
                TextColumn::make('emitted_at')
                    ->label('Emesso il')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('expires_at')
                    ->label('Scadenza')
                    ->date('d/m/Y')
                    ->sortable()
                    // Colora in rosso i documenti scaduti, giallo quelli in prossima scadenza
                    ->color(fn($record) => match (true) {
                        $record->expires_at === null => null,
                        $record->expires_at->isPast() => 'danger',
                        $record->expires_at->diffInDays(now()) <= 30 => 'warning',
                        default => null,
                    }),
                // ── Flag compatti ─────────────────────────────────────────────
                IconColumn::make('is_signed')
                    ->label('Firmato')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_template')
                    ->label('Template')
                    ->boolean()
                    ->trueColor('warning')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                // ── AI Confidence Score ───────────────────────────────────────
                TextColumn::make('ai_confidence_score')
                    ->label('AI Score')
                    ->numeric()
                    ->suffix('%')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                // ── Timestamps ────────────────────────────────────────────────
                TextColumn::make('created_at')
                    ->label('Caricato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Aggiornato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Filtro per stato documento
                SelectFilter::make('status')
                    ->label('Stato documento')
                    ->options(DocumentStatus::class),
                // Filtro per stato webhook
                SelectFilter::make('sync_status')
                    ->label('Stato webhook')
                    ->options(SyncStatus::class),
                // Filtro per tipo documento
                SelectFilter::make('document_type_id')
                    ->label('Tipo documento')
                    ->relationship('documentType', 'name')
                    ->searchable()
                    ->preload(),
                // Filtro: documenti in scadenza entro 30 giorni
                Filter::make('expiring_soon')
                    ->label('In scadenza (30 gg)')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<=', now()->addDays(30))
                        ->where('expires_at', '>=', now())),
                // Filtro: documenti già scaduti
                Filter::make('expired')
                    ->label('Scaduti')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<', now())),
                // Filtro: firma richiesta/presente
                Filter::make('is_signed')
                    ->label('Firmati')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->where('is_signed', true)),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getVerifyAction(): Action
    {
        return Action::make('verify')
            ->label('Verifica Documento')
            ->icon('heroicon-o-check-badge')
            ->color(fn(Document $record) => $record->status_code === 'IN VERIFICA' ? 'warning' : 'primary')
            ->visible(fn(Document $record) => in_array($record->status_code, ['DA VERIFICARE', 'IN VERIFICA']))
            ->form([
                Select::make('document_type_id')
                    ->label('Tipo Documento (Classificazione)')
                    ->options(DocumentType::pluck('name', 'id'))
                    ->default(fn(Document $record) => $record->document_type_id)
                    ->searchable()
                    ->required(),
                Select::make('status_code')
                    ->label('Esito Verifica')
                    ->options(DocumentStatus::pluck('name', 'code'))
                    ->default('OK')
                    ->required()
                    ->live(),  // Ricarica il form se cambia il valore
                Textarea::make('rejection_note')
                    ->label('Motivo Rifiuto / Note')
                    ->visible(fn($get) => in_array($get('status_code'), ['DIFFORME', 'ERRATO', 'RICHIESTA INFO']))
                    ->required(fn($get) => in_array($get('status_code'), ['DIFFORME', 'ERRATO'])),
            ])
            ->action(function (array $data, Document $record): void {
                DB::transaction(function () use ($data, $record) {
                    // 1. Capiamo se l'utente ha corretto l'AI (Override)
                    $isOverride = $record->document_type_id !== $data['document_type_id'];

                    // 2. Aggiorniamo il log di classificazione (se esiste) per addestrare l'AI
                    if ($isOverride) {
                        $record->classificationLogs()->latest()->first()?->update([
                            'actual_type_id' => $data['document_type_id'],
                            'is_override' => true,
                            'user_id' => auth()->id(),
                        ]);
                    }

                    // 3. Aggiorniamo il documento
                    $record->update([
                        'document_type_id' => $data['document_type_id'],
                        'status_code' => $data['status_code'],
                        'rejection_note' => $data['rejection_note'] ?? null,
                        'verified_at' => now(),
                        'verified_by' => auth()->id(),
                    ]);
                });
            });
    }
}
