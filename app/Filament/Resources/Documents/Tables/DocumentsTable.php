<?php

namespace App\Filament\Resources\Documents\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use App\Enums\DocumentStatus;
use App\Enums\SyncStatus;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable(),

                // Filament 3+ renderizza automaticamente il badge se l'enum ha l'attributo/interfaccia HasColor/HasIcon
                TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->sortable(),

                TextColumn::make('sync_status')
                    ->label('Stato Sincronizzazione Webhook')
                    ->badge()
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('Scadenza')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filtro standard a discesa basato sull'enum
                SelectFilter::make('status')
                    ->label('Filtra per Stato')
                    ->options(DocumentStatus::class),

                // Filtro custom per i documenti in scadenza (es. nei prossimi 30 giorni) o scaduti
                Filter::make('expiring_soon')
                    ->label('In Scadenza (30 gg)')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<=', now()->addDays(30))
                        ->where('expires_at', '>=', now())
                    ),
            ])
            ->actions([])
            ->bulkActions([]);
    }
 }
