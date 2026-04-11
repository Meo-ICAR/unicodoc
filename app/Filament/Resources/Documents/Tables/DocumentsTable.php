<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Enums\DocumentStatus;
use App\Enums\SyncStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    ->description(fn ($record): string => $record->docnumber ?? ''),

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
                    ->color(fn ($record) => match (true) {
                        $record->expires_at === null                        => null,
                        $record->expires_at->isPast()                      => 'danger',
                        $record->expires_at->diffInDays(now()) <= 30       => 'warning',
                        default                                             => null,
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
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<=', now()->addDays(30))
                        ->where('expires_at', '>=', now())
                    ),

                // Filtro: documenti già scaduti
                Filter::make('expired')
                    ->label('Scaduti')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<', now())
                    ),

                // Filtro: firma richiesta/presente
                Filter::make('is_signed')
                    ->label('Firmati')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('is_signed', true)),

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
}
