<?php

namespace App\Filament\Resources\DocumentTypes;

use App\Filament\Resources\DocumentTypes\Pages\CreateDocumentType;
use App\Filament\Resources\DocumentTypes\Pages\EditDocumentType;
use App\Filament\Resources\DocumentTypes\Pages\ListDocumentTypes;
use App\Filament\Resources\DocumentTypes\Schemas\DocumentTypeForm;
use App\Filament\Resources\DocumentTypes\Tables\DocumentTypesTable;
use App\Models\DocumentType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DocumentTypeResource extends Resource
{
    protected static ?string $model = DocumentType::class;

    // ── Navigazione ──────────────────────────────────────────────────────────
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

  //  protected static ?string $navigationGroup = 'Configurazione DMS';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Tipi Documento';

    // ── Label del modello (singolare/plurale) ────────────────────────────────
    protected static ?string $modelLabel = 'Tipo Documento';

    protected static ?string $pluralModelLabel = 'Tipi Documento';

    // ── Slug URL ─────────────────────────────────────────────────────────────
    protected static ?string $slug = 'document-types';

    // ── Record title ─────────────────────────────────────────────────────────
    protected static ?string $recordTitleAttribute = 'name';

    // ── Ricerca globale ──────────────────────────────────────────────────────
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'code', 'codegroup', 'slug', 'description'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Codice' => $record->code ?? '—',
            'Gruppo' => $record->codegroup ?? '—',
        ];
    }

    // ── Form (delegato al file Schemas) ──────────────────────────────────────
    public static function form(Schema $schema): Schema
    {
        return DocumentTypeForm::configure($schema);
    }

    // ── Table (delegata al file Tables) ──────────────────────────────────────
    public static function table(Table $table): Table
    {
        return DocumentTypesTable::configure($table);
    }

    // ── Relation Managers ────────────────────────────────────────────────────
    public static function getRelations(): array
    {
        return [
            // Futuro: DocumentsRelationManager per vedere i documenti associati a questo tipo
        ];
    }

    // ── Pages ────────────────────────────────────────────────────────────────
    public static function getPages(): array
    {
        return [
            'index'  => ListDocumentTypes::route('/'),
            'create' => CreateDocumentType::route('/create'),
            'edit'   => EditDocumentType::route('/{record}/edit'),
        ];
    }

    // ── Eloquent: include soft-deleted per route binding ─────────────────────
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
