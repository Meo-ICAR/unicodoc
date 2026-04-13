# Design Document — UnicoDoc Test Suite

## Overview

Questo documento descrive l'architettura tecnica della test suite di UnicoDoc, un'applicazione Laravel 13 + Filament 5.4 per la gestione documentale. La suite copre 13 aree funzionali: Enum di dominio, modelli Eloquent, pipeline di classificazione (Regex + AI), ingestion email, API M2M (Compliance Gate e Dossier Creation), job asincroni e listener di eventi.

**Obiettivi principali:**
- Garantire correttezza funzionale di ogni componente in isolamento
- Verificare le invarianti di dominio tramite property-based testing
- Eseguire i test in modo rapido e deterministico (SQLite in-memory, fake HTTP/Queue/Event)
- Fornire una base di regressione stabile per l'evoluzione del sistema

**Stack di test:**
- **Framework:** Pest PHP (installato come dipendenza dev, wrapper su PHPUnit 12)
- **Property-based testing:** `pestphp/pest-plugin-faker` + generatori custom per le proprietà universali
- **Database:** SQLite `:memory:` con `RefreshDatabase`
- **Fakes:** `Http::fake()`, `Queue::fake()`, `Event::fake()`, `Storage::fake()`, `Log::spy()`

---

## Architecture

La suite è organizzata in tre livelli:

```
tests/
├── Unit/                          # Test puri, nessun DB, nessun framework
│   ├── Enums/
│   │   ├── DocumentStatusTest.php
│   │   └── SyncStatusTest.php
│   ├── Models/
│   │   ├── DocumentTypeTest.php
│   │   ├── DocumentTest.php
│   │   └── DocumentRequestTest.php
│   └── Services/
│       ├── RegexClassifierTest.php
│       └── MailAttachmentProcessorSkipTest.php
├── Feature/                       # Test con DB in-memory e HTTP fake
│   ├── Classification/
│   │   └── ClassificationOrchestratorTest.php
│   ├── Mail/
│   │   ├── MailAttachmentProcessorTest.php
│   │   └── MailMessageProcessorTest.php
│   ├── Api/
│   │   ├── ComplianceGateTest.php
│   │   └── DossierCreationTest.php
│   ├── Jobs/
│   │   └── ProcessDocumentAiJobTest.php
│   └── Listeners/
│       └── FulfillDocumentRequestTest.php
├── Factories/                     # (già in database/factories/)
└── TestCase.php                   # Base class con helper condivisi
```

### Principi architetturali

1. **Isolamento per livello**: i test Unit non toccano il database né il container IoC. I test Feature usano `RefreshDatabase` e i fake di Laravel.
2. **Dependency injection esplicita**: i servizi (`RegexClassifier`, `AiClassifier`, `MailAttachmentProcessor`) vengono iniettati o mockati tramite `$this->mock()` di Pest/Laravel, mai istanziati direttamente nei test Feature.
3. **Nessuna chiamata reale**: ogni chiamata HTTP outgoing (BPM webhook, AI API) è intercettata da `Http::fake()`. Ogni job è verificato con `Queue::fake()`.
4. **Factory come unica fonte di verità**: tutti i record di test vengono creati tramite Factory Eloquent con stati predefiniti (`->verified()`, `->expired()`, `->pending()`).

---

## Components and Interfaces

### TestCase Base (`tests/TestCase.php`)

Estende `Illuminate\Foundation\Testing\TestCase` e include:
- `RefreshDatabase` trait
- Helper `fakeHttp(array $responses = [])` — configura `Http::fake()` con risposte predefinite
- Helper `makeDocument(array $overrides = [])` — crea un `Document` tramite factory con override
- Helper `makeDocumentType(array $overrides = [])` — crea un `DocumentType` con pattern e soglie

### Factory Eloquent

| Factory | Stati predefiniti |
|---|---|
| `DocumentFactory` | `->uploaded()`, `->verified()`, `->expired()`, `->withType(DocumentType)` |
| `DocumentTypeFactory` | `->withRegex(string)`, `->withAi()`, `->autoVerifiable()` |
| `DocumentRequestFactory` | `->pending()`, `->partial()`, `->completed()`, `->expired()`, `->withItems(int)` |
| `DocumentRequestItemFactory` | `->fulfilled(Document)`, `->pending()` |
| `MailMessageFactory` | `->withAttachments(int)`, `->withBody(string)`, `->processed()` |
| `MailAttachmentFactory` | `->valid()`, `->tooSmall()`, `->gif()`, `->inline()` |

### Mock Strategy per i Classificatori

```php
// Nei test Feature dell'Orchestratore:
$this->mock(RegexClassifier::class)
    ->shouldReceive('classify')
    ->once()
    ->andReturn(new ClassificationResult(...));

$this->mock(AiClassifier::class)
    ->shouldNotReceive('classify'); // Verifica che non venga chiamato
```

---

## Data Models

### Configurazione ambiente di test

**`phpunit.xml`** (o `pest.config.php`):
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="MAIL_MAILER" value="array"/>
<env name="CACHE_DRIVER" value="array"/>
```

### Struttura ClassificationResult (Value Object)

```php
readonly class ClassificationResult {
    public function __construct(
        public ?int    $documentTypeId,
        public int     $confidenceScore,  // 0–100
        public string  $classifierUsed,   // 'regex' | 'ai'
        public ?array  $evidence = null   // ['matched_string' => ..., 'pattern' => ...]
    ) {}
}
```

### Enum di dominio testati

| Enum | Valori | Contratti Filament |
|---|---|---|
| `DocumentStatus` | `UPLOADED`, `VERIFIED`, `REJECTED`, `EXPIRED`, `REVOKED`, `PENDING` | `HasLabel`, `HasColor`, `HasIcon` |
| `SyncStatus` | `LOCAL`, `SYNCING`, `SYNCED`, `FAILED` | `HasLabel`, `HasColor`, `HasIcon` |

### Mappa stati DossierStatus → metodi DocumentRequest

| Status | `isPending()` | `isPartial()` | `isCompleted()` | `isExpired()` |
|---|---|---|---|---|
| `PENDING` | `true` | `false` | `false` | dipende da `expires_at` |
| `PARTIAL` | `false` | `true` | `false` | dipende da `expires_at` |
| `COMPLETED` | `false` | `false` | `true` | dipende da `expires_at` |
| `EXPIRED` | `false` | `false` | `false` | `true` sempre |

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Enum label/color/icon completeness

*For any* `DocumentStatus` case, `getLabel()` SHALL return a non-empty string, `getColor()` SHALL return one of `['warning', 'success', 'danger', 'gray', 'info']`, and `getIcon()` SHALL return a non-empty string starting with `'heroicon-'`.

**Validates: Requirements 1.2, 1.3, 1.4**

### Property 2: SyncStatus label/color/icon completeness

*For any* `SyncStatus` case, `getLabel()`, `getColor()` e `getIcon()` SHALL return non-null, non-empty values.

**Validates: Requirements 1.6**

### Property 3: Enum round-trip via from()

*For any* `DocumentStatus` case, `DocumentStatus::from($case->value)` SHALL return the same case without throwing exceptions.

**Validates: Requirements 1.7**

### Property 4: Invalid string throws ValueError

*For any* string that is not a valid `DocumentStatus` value, `DocumentStatus::from($string)` SHALL throw `ValueError`.

**Validates: Requirements 1.8**

### Property 5: canAutoVerify correctness

*For any* `DocumentType` with `allow_auto_verification=true` and any integer `confidence > 95`, `canAutoVerify($confidence)` SHALL return `true`. *For any* `DocumentType` with `allow_auto_verification=false` OR any `confidence <= 95`, `canAutoVerify($confidence)` SHALL return `false`.

**Validates: Requirements 2.1, 2.2**

### Property 6: meetsConfidenceThreshold correctness

*For any* non-negative integer `confidence` and any `min_confidence`, `meetsConfidenceThreshold($confidence)` SHALL return `true` if and only if `confidence >= min_confidence`, and SHALL never throw an exception.

**Validates: Requirements 2.3, 2.9**

### Property 7: getRetentionDate correctness

*For any* positive integer `N` (retention_years) and any valid date `D`, `getRetentionDate(D)` SHALL return a date equal to `D + N years`.

**Validates: Requirements 2.5**

### Property 8: isExpired correctness for DocumentType

*For any* `DocumentType` with `retention_years` set, `isExpired($documentDate)` SHALL return `true` if and only if the retention date is in the past.

**Validates: Requirements 2.6, 2.7**

### Property 9: Document.isExpired correctness

*For any* `Document` with `expires_at` set to a past date, `isExpired()` SHALL return `true`. *For any* `Document` with `expires_at` set to a future date, `isExpired()` SHALL return `false`.

**Validates: Requirements 3.1**

### Property 10: Document.isNearExpiry correctness

*For any* positive integer `N` and any `Document` with `expires_at` within `N` days from now, `isNearExpiry(N)` SHALL return `true`.

**Validates: Requirements 3.3**

### Property 11: Document enum cast round-trip

*For any* `DocumentStatus` value stored in a `Document`, retrieving the document from the database SHALL return the same `DocumentStatus` enum instance. The same holds for `SyncStatus` and `metadata` (array cast).

**Validates: Requirements 3.6, 3.7**

### Property 12: DocumentRequest status method exclusivity

*For any* `DocumentRequest` with a given `status` value, exactly one of `isPending()`, `isPartial()`, `isCompleted()` SHALL return `true` (excluding `isExpired()` which depends on `expires_at`).

**Validates: Requirements 4.1, 4.2**

### Property 13: DocumentRequest.isExpired with past date

*For any* `DocumentRequest` with `expires_at` in the past, `isExpired()` SHALL return `true` regardless of the `status` value.

**Validates: Requirements 4.3**

### Property 14: DocumentRequest.getExpiryProgress bounds

*For any* valid combination of `created_at` and `expires_at`, `getExpiryProgress()` SHALL return a value in the range `[0.0, 100.0]`.

**Validates: Requirements 4.8**

### Property 15: RegexClassifier match returns score 100

*For any* `DocumentType` with a valid `regex_pattern` and any document text that matches that pattern, `RegexClassifier::classify()` SHALL return a `ClassificationResult` with `confidenceScore == 100` and `classifierUsed == 'regex'`.

**Validates: Requirements 5.1**

### Property 16: RegexClassifier priority ordering

*For any* set of `DocumentType` records with matching patterns and different `priority` values, `RegexClassifier::classify()` SHALL return the `ClassificationResult` corresponding to the type with the highest `priority`.

**Validates: Requirements 5.4**

### Property 17: RegexClassifier robustness

*For any* valid text string and any set of valid regex patterns, `RegexClassifier::classify()` SHALL return either `null` or a `ClassificationResult` without throwing exceptions.

**Validates: Requirements 5.6**

### Property 18: RegexClassifier evidence contains matched string

*For any* successful regex match, the returned `ClassificationResult::$evidence['matched_string']` SHALL be a non-empty substring of the analyzed text.

**Validates: Requirements 5.7**

### Property 19: Orchestrator regex-first, AI-skipped

*For any* document where `RegexClassifier` returns a valid result, `ClassificationOrchestratorService::process()` SHALL update the document with the regex result and SHALL NOT invoke `AiClassifier::classify()`.

**Validates: Requirements 6.1**

### Property 20: Orchestrator confidence threshold routing

*For any* classification result where `confidenceScore >= min_confidence` (but < 95 or `allow_auto_verification=false`), the orchestrator SHALL set `status_code = 'IN VERIFICA'`. *For any* result where `confidenceScore >= 95` and `allow_auto_verification=true`, it SHALL set `status_code = 'OK'` and populate `verified_at`. *For any* result where `confidenceScore < min_confidence`, it SHALL set `status_code = 'RICHIESTA INFO'`.

**Validates: Requirements 6.4, 6.5, 6.6**

### Property 21: Orchestrator always creates ClassificationLog on success

*For any* document successfully classified (regex or AI), `ClassificationOrchestratorService::process()` SHALL create exactly one `ClassificationLog` record in the database.

**Validates: Requirements 6.7**

### Property 22: MailAttachmentProcessor.shouldSkip completeness

*For any* combination of `size` (integer), `mime_type` (string) and `is_inline` (boolean), `MailAttachmentProcessor::shouldSkip()` SHALL return a boolean without throwing exceptions. Specifically: returns `true` if `size < 8192` OR `mime_type == 'image/gif'` OR (`is_inline == true` AND `mime_type` starts with `'image/'`); returns `false` otherwise.

**Validates: Requirements 7.1, 7.3, 7.4, 7.7**

### Property 23: MailAttachmentProcessor valid attachment creates Document

*For any* attachment where `shouldSkip()` returns `false`, `convertToDocument()` SHALL create a `Document` record with `source_app == 'email'` and SHALL dispatch the `DocumentUploaded` event.

**Validates: Requirements 7.5**

### Property 24: MailMessageProcessor question handling

*For any* `MailMessage` with no valid attachments and `body_text` longer than 20 characters, `MailMessageProcessor::processUnreadMessages()` SHALL set `has_unread_messages = true` on the associated `DocumentRequest`, update `last_message_received` with HTML-stripped text, and send a POST request to the BPM webhook URL.

**Validates: Requirements 8.1, 8.2, 8.3**

### Property 25: ComplianceGate summary invariant

*For any* combination of valid, invalid and missing documents, the `ComplianceGate` response SHALL satisfy: `summary.valid + summary.invalid + summary.missing == summary.total_required`.

**Validates: Requirements 9.5, 9.7**

### Property 26: ComplianceGate is_compliant correctness

*For any* entity, `is_compliant` SHALL be `true` if and only if `missing_documents` is empty AND `invalid_documents` is empty.

**Validates: Requirements 9.1, 9.2, 9.3**

### Property 27: DossierCreationAPI expiry correctness

*For any* positive integer `expires_in_days`, the created `DocumentRequest` SHALL have `expires_at` equal to `now() + expires_in_days days` (within a 1-second tolerance).

**Validates: Requirements 10.4, 10.8**

### Property 28: DossierCreationAPI items count

*For any* list of N valid `required_codes`, the API SHALL create exactly N `DocumentRequestItem` records linked to the new `DocumentRequest`.

**Validates: Requirements 10.5**

### Property 29: DossierCreationAPI upload_url contains UUID

*For any* created `DocumentRequest`, the `upload_url` in the response SHALL contain the `request_id` UUID as a substring.

**Validates: Requirements 10.6**

### Property 30: ProcessDocumentAiJob webhook sync_status

*For any* document processed by `ProcessDocumentAiJob`, if the webhook response is 2xx then `sync_status == SyncStatus::SYNCED`; if the response is non-2xx then `sync_status == SyncStatus::FAILED`.

**Validates: Requirements 11.4, 11.5**

### Property 31: ProcessDocumentAiJob HMAC signature

*For any* webhook call made by `ProcessDocumentAiJob`, the HTTP request SHALL include the `X-DMS-Signature` header containing a valid HMAC-SHA256 signature of the payload computed with `config('app.key')`.

**Validates: Requirements 11.7**

### Property 32: FulfillDocumentRequest dossier completion invariant

*For any* `DocumentRequest`, after `FulfillDocumentRequest` processes a matching document: if all items are fulfilled then `status == 'COMPLETED'`; if at least one item remains unfulfilled then `status == 'PARTIAL'`.

**Validates: Requirements 12.2, 12.3**

### Property 33: FulfillDocumentRequest webhook on completion

*For any* `DocumentRequest` that transitions to `COMPLETED` status, `FulfillDocumentRequest` SHALL send a POST request to the BPM webhook with `event == 'document_request_completed'` and the correct `bpm_task_id`.

**Validates: Requirements 12.5**

---

## Error Handling

### Classificazione fallita
- Se entrambi i classificatori restituiscono `null`, il documento viene marcato `'DA VERIFICARE'` senza `document_type_id`. Nessuna eccezione viene propagata.
- Se il `RegexClassifier` incontra un pattern regex malformato, `preg_match` restituisce `false`; il classificatore deve gestire questo caso restituendo `null` (non lanciando).

### Webhook outgoing fallito
- `ProcessDocumentAiJob`: risposta non-2xx → `sync_status = FAILED`, log dell'errore, nessun retry aggiuntivo (il retry è gestito da Horizon tramite `$tries`).
- `FulfillDocumentRequest`: la chiamata HTTP al BPM è wrappata in try/catch; un fallimento non deve impedire l'aggiornamento dello stato del Dossier.
- `MailMessageProcessor`: idem — il fallimento del webhook viene loggato ma non blocca il processing del messaggio.

### Webhook URL non configurato
- `ProcessDocumentAiJob`: se `$webhookUrl` è null/vuoto, viene registrato un `Log::warning` e `sync_status` non viene aggiornato.

### Validazione API
- Tutti gli endpoint API usano `$request->validate()` di Laravel. Campi mancanti o `required_codes` vuoto restituiscono HTTP 422 con il payload di errore standard di Laravel.

### Job retry
- `ProcessDocumentAiJob` ha `$tries = 3` e `$timeout = 60`. In caso di eccezione, lo stato viene impostato a `DocumentStatus::FAILED` e l'eccezione viene rilanciata per permettere il retry nativo di Horizon.

---

## Testing Strategy

### Approccio duale

La suite combina due tipologie complementari:

1. **Test di esempio (unit/feature)**: verificano comportamenti specifici con input concreti, casi limite e condizioni di errore.
2. **Property-based test**: verificano invarianti universali su un ampio spazio di input generati casualmente.

### Libreria PBT

Pest PHP non include un motore PBT nativo. Si utilizzerà **`pestphp/pest-plugin-faker`** per la generazione di dati casuali nei test di proprietà, combinato con cicli `it()->repeat(100)` o con il pattern `dataset()` per coprire lo spazio degli input. Per proprietà più complesse si valuterà l'integrazione di **`eris/eris`** (QuickCheck per PHP).

Ogni property test è configurato per eseguire **almeno 100 iterazioni**.

### Configurazione tag

Ogni property test include un commento di riferimento al documento di design:

```php
// Feature: unicodoc-test-suite, Property 25: ComplianceGate summary invariant
it('summary counts are always consistent', function () { ... })->repeat(100);
```

### Copertura per area

| Area | Tipo test | File |
|---|---|---|
| Enum (Req 1) | Unit + Property | `tests/Unit/Enums/` |
| DocumentType (Req 2) | Unit + Property | `tests/Unit/Models/DocumentTypeTest.php` |
| Document (Req 3) | Unit + Property | `tests/Unit/Models/DocumentTest.php` |
| DocumentRequest (Req 4) | Unit + Property | `tests/Unit/Models/DocumentRequestTest.php` |
| RegexClassifier (Req 5) | Unit + Property | `tests/Unit/Services/RegexClassifierTest.php` |
| ClassificationOrchestrator (Req 6) | Feature + Property | `tests/Feature/Classification/` |
| MailAttachmentProcessor (Req 7) | Unit + Feature + Property | `tests/Unit/Services/` + `tests/Feature/Mail/` |
| MailMessageProcessor (Req 8) | Feature + Property | `tests/Feature/Mail/MailMessageProcessorTest.php` |
| ComplianceGate (Req 9) | Feature + Property | `tests/Feature/Api/ComplianceGateTest.php` |
| DossierCreation (Req 10) | Feature + Property | `tests/Feature/Api/DossierCreationTest.php` |
| ProcessDocumentAiJob (Req 11) | Feature + Property | `tests/Feature/Jobs/ProcessDocumentAiJobTest.php` |
| FulfillDocumentRequest (Req 12) | Feature + Property | `tests/Feature/Listeners/FulfillDocumentRequestTest.php` |
| Infrastruttura (Req 13) | Smoke | Configurazione `phpunit.xml` + `TestCase.php` |

### Fake helpers utilizzati

```php
Http::fake([
    config('services.bpm.webhook_url') => Http::response(['ok' => true], 200),
    config('services.ai.endpoint')     => Http::response(['type_id' => 1, 'confidence' => 90, 'reasoning' => 'test'], 200),
]);

Queue::fake();
Event::fake([DocumentUploaded::class, DocumentClassified::class]);
Storage::fake('local');
Log::spy(); // Per verificare Log::warning() e Log::error()
```

### Esecuzione

```bash
php artisan test                    # Tutti i test
php artisan test --filter=Unit      # Solo unit test
php artisan test --filter=Feature   # Solo feature test
php artisan test --coverage         # Con report di copertura
```
