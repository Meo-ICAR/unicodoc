<?php

namespace App\Filament\Resources\DocumentTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // ── Identificazione ───────────────────────────────────────────────────
                TextColumn::make('name')
                    ->label('Nome Tipo Documento')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn ($record): string => $record->description ?? ''),

                TextColumn::make('code')
                    ->label('Codice')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('codegroup')
                    ->label('Gruppo')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('phase')
                    ->label('Fase')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('priority')
                    ->label('Priorità')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('duration')
                    ->label('Durata (gg)')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state ? "{$state} gg" : '—')
                    ->toggleable(),

                // ── Flag Applicabilità (compatti, toggleable) ─────────────────────────
                IconColumn::make('is_person')
                    ->label('Persona')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_company')
                    ->label('Azienda')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_client')
                    ->label('Cliente')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_employee')
                    ->label('Dipendente')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_practice')
                    ->label('Pratica')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                // ── Flag Comportamento ────────────────────────────────────────────────
                IconColumn::make('is_sensible')
                    ->label('Sensibile')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('gray')
                    ->trueIcon('heroicon-s-shield-exclamation')
                    ->falseIcon('heroicon-o-shield-check')
                    ->alignCenter(),

                IconColumn::make('is_monitored')
                    ->label('Monitorato')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(),

                IconColumn::make('is_signed')
                    ->label('Firmato')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(),

                IconColumn::make('is_stored')
                    ->label('Archiviato')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_template')
                    ->label('Template')
                    ->boolean()
                    ->trueColor('warning')
                    ->alignCenter()
                    ->toggleable(),

                // ── Flag AI ───────────────────────────────────────────────────────────
                IconColumn::make('is_AiAbstract')
                    ->label('AI Abstract')
                    ->boolean()
                    ->trueColor('success')
                    ->trueIcon('heroicon-s-sparkles')
                    ->alignCenter()
                    ->toggleable(),

                IconColumn::make('is_AiCheck')
                    ->label('AI Validation')
                    ->boolean()
                    ->trueColor('success')
                    ->trueIcon('heroicon-s-cpu-chip')
                    ->alignCenter()
                    ->toggleable(),

                // ── Date ──────────────────────────────────────────────────────────────
                TextColumn::make('created_at')
                    ->label('Creato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Aggiornato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->defaultSort('priority', 'desc')

            ->filters([
                TrashedFilter::make(),

                // Filtro: solo tipi con AI abilitata
                Filter::make('ai_enabled')
                    ->label('Con AI attiva')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query
                        ->where(fn (Builder $q) => $q
                            ->where('is_AiAbstract', true)
                            ->orWhere('is_AiCheck', true)
                        )
                    ),

                // Filtro: solo tipi sensibili
                Filter::make('is_sensible')
                    ->label('Solo documenti sensibili (GDPR)')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('is_sensible', true)),

                // Filtro: solo tipi che richiedono firma
                Filter::make('is_signed')
                    ->label('Richiede firma')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('is_signed', true)),

                // Filtro: solo template
                Filter::make('is_template')
                    ->label('Solo template')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('is_template', true)),
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
