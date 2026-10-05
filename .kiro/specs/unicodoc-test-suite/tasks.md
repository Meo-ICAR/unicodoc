# Implementation Plan: UnicoDoc Test Suite

## Overview
Implementazione incrementale della test suite completa di UnicoDoc in Pest PHP. I task seguono l'ordine delle dipendenze: prima l'infrastruttura condivisa (TestCase, Factory, configurazione), poi i test Unit puri (Enum, Model, Servizi), infine i test Feature con DB in-memory e HTTP fake (Orchestratore, Mail, API, Job, Listener).

Ogni task produce codice eseguibile e verificabile prima di passare al successivo. I task marcati con `*` sono opzionali (property-based test) e possono essere saltati per un MVP più rapido.

---

## Tasks

- [x] 1. Configurare l'infrastruttura della test suite
  - Installare Pest PHP come dipendenza dev (`composer require pestphp/pest --dev`) e pubblicare la configurazione con `./vendor/bin/pest --init`
  - Aggiornare `phpunit.xml` aggiungendo le variabili d'ambiente: `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`, `CACHE_DRIVER=array`
  - Estendere `tests/TestCase.php` con il trait `RefreshDatabase` e gli helper condivisi: `fakeHttp(array $responses)`, `makeDocument(array $overrides)`, `makeDocumentType(array $overrides)`
  - _Requirements: 13.1, 13.2, 13.4, 13.8_

- [x] 2. Creare le Factory Eloquent
  - [x] 2.1 Creare `DocumentTypeFactory` con stati `->withRegex(string)`, `->withAi()`, `->autoVerifiable()`
    - Campi obbligatori: `code`, `name`, `min_confidence` (default 70), `allow_auto_verification` (default false), `regex_pattern` (nullable), `priority` (default 1)
    - _Requirements: 13.3_
  - [x] 2.2 Creare `DocumentFactory` con stati `->uploaded()`, `->verified()`, `->expired()`, `->withType(DocumentType)`
    - Usare UUID come PK, castare `status` a `DocumentStatus` e `sync_status` a `SyncStatus`
    - _Requirements: 13.3_
  - [x] 2.3 Creare `DocumentRequestFactory` con stati `->pending()`, `->partial()`, `->completed()`, `->expired()`, `->withItems(int $count)`
    - UUID come PK, `expires_at` default a `now()->addDays(7)`
    - _Requirements: 13.3_
  - [x] 2.4 Creare `DocumentRequestItemFactory` con stati `->fulfilled(Document $doc)`, `->pending()`
    - _Requirements: 13.3_
  - [x] 2.5 Creare `MailMessageFactory` con stati `->withBody(string)`, `->processed()`, `->withAttachments(int $count)`
    - _Requirements: 13.3_
  - [x] 2.6 Creare `MailAttachmentFactory` con stati `->valid()`, `->tooSmall()`, `->gif()`, `->inline()`
    - Stato `->valid()`: `size >= 8192`, `mime_type = 'application/pdf'`, `is_inline = false`
    - Stato `->tooSmall()`: `size = 1024`; `->gif()`: `mime_type = 'image/gif'`; `->inline()`: `is_inline = true`, `mime_type = 'image/png'`
    - _Requirements: 13.3_

- [x] 3. Checkpoint — Verificare che le factory e la configurazione funzionino
  - Eseguire `php artisan test --filter=ExampleTest` per confermare che l'ambiente SQLite in-memory sia operativo. Chiedere all'utente se ci sono problemi prima di procedere.

- [x] 4. Implementare i test Unit per gli Enum di dominio
  - [x] 4.1 Creare `tests/Unit/Enums/DocumentStatusTest.php`
    - Testare che tutti e sei i valori (`UPLOADED`, `VERIFIED`, `REJECTED`, `EXPIRED`, `REVOKED`, `PENDING`) siano istanziabili via `DocumentStatus::from(string)`
    - Testare che `getLabel()` restituisca una stringa non vuota per ogni valore
    - Testare che `getColor()` restituisca uno dei colori Filament validi per ogni valore
    - Testare che `getIcon()` restituisca un nome icona che inizia con `'heroicon-'` per ogni valore
    - Testare che `DocumentStatus::from('stringa_invalida')` lanci `ValueError`
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.7, 1.8_
  - [ ]* 4.2 Scrivere property test per DocumentStatus (Property 1, 3, 4)
    - **Property 1: Enum label/color/icon completeness** — per ogni case, `getLabel()` non vuoto, `getColor()` in lista valida, `getIcon()` inizia con `'heroicon-'`
    - **Property 3: Enum round-trip via from()** — `DocumentStatus::from($case->value)` restituisce lo stesso case
    - **Property 4: Invalid string throws ValueError** — stringa non valida lancia `ValueError`
    - **Validates: Requirements 1.2, 1.3, 1.4, 1.7, 1.8**
  - [x] 4.3 Creare `tests/Unit/Enums/SyncStatusTest.php`
    - Testare che tutti e quattro i valori (`LOCAL`, `SYNCING`, `SYNCED`, `FAILED`) abbiano `getLabel()`, `getColor()` e `getIcon()` non nulli e non vuoti
    - _Requirements: 1.5, 1.6_
  - [ ]* 4.4 Scrivere property test per SyncStatus (Property 2)
    - **Property 2: SyncStatus label/color/icon completeness** — per ogni case, tutti e tre i metodi restituiscono valori non nulli e non vuoti
    - **Validates: Requirements 1.6**

- [x] 5. Implementare i test Unit per il modello DocumentType
  - [x] 5.1 Creare `tests/Unit/Models/DocumentTypeTest.php`
    - Testare `canAutoVerify()`: `true` quando `allow_auto_verification=true` e `confidence > 95`; `false` quando `allow_auto_verification=false` o `confidence <= 95`
    - Testare `meetsConfidenceThreshold()`: `true` se `confidence >= min_confidence`, `false` altrimenti
    - Testare `hasRetentionPolicy()` e `getRetentionDate()`: `false`/`null` quando `retention_years=null`; data corretta quando `retention_years=N`
    - Testare `isExpired()`: `true` quando la data di ritenzione è nel passato; `false` quando è nel futuro
    - Testare `shouldNotify()`: con `notify_days_before=[30,7]` e 10 giorni alla scadenza, restituisce `[7]`
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8_
  - [ ]* 5.2 Scrivere property test per DocumentType (Property 5, 6, 7, 8)
    - **Property 5: canAutoVerify correctness** — per ogni combinazione di `allow_auto_verification` e `confidence`, il risultato è deterministico e corretto
    - **Property 6: meetsConfidenceThreshold correctness** — per ogni intero non negativo, restituisce booleano senza eccezioni
    - **Property 7: getRetentionDate correctness** — per ogni N positivo e data D, restituisce `D + N anni`
    - **Property 8: isExpired correctness** — `true` sse la data di ritenzione è nel passato
    - **Validates: Requirements 2.1, 2.2, 2.3, 2.5, 2.6, 2.7, 2.9**

- [x] 6. Implementare i test Unit per il modello Document
  - [x] 6.1 Creare `tests/Unit/Models/DocumentTest.php`
    - Testare `isExpired()`: `true` con `expires_at` nel passato; `false` con `expires_at=null`
    - Testare `isNearExpiry(N)`: `true` quando `expires_at` è entro N giorni da oggi
    - Testare `canBeVerified()` e `canBeRejected()`: `true` quando `status=UPLOADED`; `false` quando `status=VERIFIED`
    - Testare che il campo `status` sia castato a `DocumentStatus` e `sync_status` a `SyncStatus`
    - Testare che il campo `metadata` sia castato ad array PHP
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7_
  - [ ]* 6.2 Scrivere property test per Document (Property 9, 10, 11)
    - **Property 9: Document.isExpired correctness** — `true` per date passate, `false` per date future
    - **Property 10: Document.isNearExpiry correctness** — `true` per qualsiasi N quando `expires_at` è entro N giorni
    - **Property 11: Document enum cast round-trip** — il valore `DocumentStatus` persiste e viene riletto correttamente dal DB
    - **Validates: Requirements 3.1, 3.3, 3.6, 3.7**

- [x] 7. Implementare i test Unit per il modello DocumentRequest
  - [x] 7.1 Creare `tests/Unit/Models/DocumentRequestTest.php`
    - Testare `isPending()`, `isPartial()`, `isCompleted()`: esclusività degli stati
    - Testare `isExpired()`: `true` con `expires_at` nel passato indipendentemente da `status`; `false` con `expires_at` nel futuro e `status != EXPIRED`
    - Testare `getMagicLink()`: restituisce una stringa URL non vuota
    - Testare `getFormattedStatus()`: label italiana corretta per tutti e quattro gli stati
    - Testare `scopeExpiringSoon(N)`: include solo i record con `expires_at` tra 1 e N giorni
    - Testare `getExpiryProgress()`: valore tra 0 e 100 per combinazioni valide di `created_at` ed `expires_at`
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8_
  - [ ]* 7.2 Scrivere property test per DocumentRequest (Property 12, 13, 14)
    - **Property 12: Status method exclusivity** — esattamente uno tra `isPending()`, `isPartial()`, `isCompleted()` è `true` per ogni status
    - **Property 13: isExpired with past date** — `true` per qualsiasi `status` quando `expires_at` è nel passato
    - **Property 14: getExpiryProgress bounds** — sempre in `[0.0, 100.0]` per qualsiasi combinazione valida
    - **Validates: Requirements 4.1, 4.2, 4.3, 4.8**

- [x] 8. Checkpoint — Eseguire tutti i test Unit
  - Eseguire `php artisan test --filter=Unit` e verificare che tutti i test passino. Chiedere all'utente se ci sono fallimenti prima di procedere con i test Feature.

- [x] 9. Implementare i test Unit per RegexClassifier
  - [x] 9.1 Creare `tests/Unit/Services/RegexClassifierTest.php`
    - Testare che con pattern corrispondente restituisca `ClassificationResult` con `confidenceScore=100` e `classifierUsed='regex'`
    - Testare che senza corrispondenza restituisca `null`
    - Testare che con documento senza `extracted_text` né `name` restituisca `null`
    - Testare che con più tipi corrispondenti scelga quello con `priority` più alta
    - Testare che il match sia case-insensitive
    - Testare che `evidence['matched_string']` contenga la stringa corrispondente al match
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.7_
  - [ ]* 9.2 Scrivere property test per RegexClassifier (Property 15, 16, 17, 18)
    - **Property 15: Match returns score 100** — per qualsiasi testo che corrisponde al pattern, `confidenceScore == 100`
    - **Property 16: Priority ordering** — con più pattern corrispondenti, vince sempre la `priority` più alta
    - **Property 17: Robustness** — per qualsiasi testo e pattern validi, non lancia mai eccezioni
    - **Property 18: Evidence contains matched string** — `evidence['matched_string']` è sempre una sottostringa del testo analizzato
    - **Validates: Requirements 5.1, 5.4, 5.6, 5.7**

- [x] 10. Implementare i test Unit per MailAttachmentProcessor (logica shouldSkip)
  - [x] 10.1 Creare `tests/Unit/Services/MailAttachmentProcessorSkipTest.php`
    - Testare `shouldSkip()`: `true` per allegato con `size < 8192`
    - Testare `shouldSkip()`: `true` per allegato con `mime_type = 'image/gif'`
    - Testare `shouldSkip()`: `true` per allegato `is_inline=true` con `mime_type` che inizia con `'image/'`
    - Testare `shouldSkip()`: `false` per allegato valido (`size >= 8192`, mime non in blacklist, `is_inline=false`)
    - _Requirements: 7.1, 7.2, 7.3, 7.4_
  - [ ]* 10.2 Scrivere property test per shouldSkip (Property 22)
    - **Property 22: shouldSkip completeness** — per qualsiasi combinazione di `size`, `mime_type` e `is_inline`, restituisce booleano senza eccezioni; la logica booleana è corretta
    - **Validates: Requirements 7.1, 7.2, 7.3, 7.4, 7.7**

- [x] 11. Implementare i test Feature per ClassificationOrchestratorService
  - [x] 11.1 Creare `tests/Feature/Classification/ClassificationOrchestratorTest.php`
    - Mockare `RegexClassifier` e `AiClassifier` tramite `$this->mock()`
    - Testare che con `RegexClassifier` che restituisce risultato valido, il documento venga aggiornato e `AiClassifier` non venga invocato
    - Testare che con `RegexClassifier=null` e `AiClassifier` con risultato valido, il documento venga aggiornato con i dati AI
    - Testare che con entrambi `null`, `status_code` sia impostato a `'DA VERIFICARE'` e `document_type_id` a `null`
    - Testare le tre soglie di confidenza: `>= min_confidence` → `'IN VERIFICA'`; `>= 95` con `allow_auto_verification=true` → `'OK'` con `verified_at` valorizzato; `< min_confidence` → `'RICHIESTA INFO'`
    - Testare che venga creato un record `ClassificationLog` per ogni classificazione riuscita
    - Testare che il listener `RunDocumentClassification` salti l'elaborazione se `document_type_id` è già valorizzato
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8_
  - [ ]* 11.2 Scrivere property test per l'orchestratore (Property 19, 20, 21)
    - **Property 19: Regex-first, AI-skipped** — quando `RegexClassifier` ha successo, `AiClassifier` non viene mai invocato
    - **Property 20: Confidence threshold routing** — per qualsiasi score, il routing verso `'IN VERIFICA'`/`'OK'`/`'RICHIESTA INFO'` è sempre corretto
    - **Property 21: ClassificationLog always created on success** — per qualsiasi classificazione riuscita, viene creato esattamente un `ClassificationLog`
    - **Validates: Requirements 6.1, 6.4, 6.5, 6.6, 6.7**

- [x] 12. Implementare i test Feature per MailAttachmentProcessor (integrazione)
  - [x] 12.1 Creare `tests/Feature/Mail/MailAttachmentProcessorTest.php`
    - Usare `Event::fake([DocumentUploaded::class])` e `Storage::fake()`
    - Testare che un allegato valido crei un record `Document` con `source_app='email'` e dispatchi `DocumentUploaded`
    - Testare che il soggetto con pattern `[Ref: <uuid>]` associ il messaggio al `DocumentRequest` corretto
    - Testare che allegati non validi (troppo piccoli, GIF, inline) vengano saltati senza creare `Document`
    - _Requirements: 7.5, 7.6_
  - [ ]* 12.2 Scrivere property test per la creazione del Document (Property 23)
    - **Property 23: Valid attachment creates Document** — per qualsiasi allegato dove `shouldSkip()=false`, viene sempre creato un `Document` con `source_app='email'` e viene dispatchato `DocumentUploaded`
    - **Validates: Requirements 7.5**

- [x] 13. Implementare i test Feature per MailMessageProcessor
  - [x] 13.1 Creare `tests/Feature/Mail/MailMessageProcessorTest.php`
    - Usare `Http::fake()` per simulare il webhook BPM
    - Testare che un messaggio senza allegati validi e con `body_text > 20 char` imposti `has_unread_messages=true` sul `DocumentRequest`
    - Testare che `last_message_received` venga aggiornato con il testo ripulito dall'HTML
    - Testare che venga inviata una richiesta POST al webhook BPM configurato
    - Testare che `is_processed` venga impostato a `true` sul `MailMessage`
    - Testare che con `body_text <= 20 char` il `DocumentRequest` non venga aggiornato e il webhook non venga inviato
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_
  - [ ]* 13.2 Scrivere property test per MailMessageProcessor (Property 24)
    - **Property 24: Question handling** — per qualsiasi messaggio senza allegati validi e `body_text > 20 char`, le tre azioni (flag, update, webhook) vengono sempre eseguite tutte e tre
    - **Validates: Requirements 8.1, 8.2, 8.3**

- [x] 14. Implementare i test Feature per Compliance Gate API
  - [x] 14.1 Creare `tests/Feature/Api/ComplianceGateTest.php`
    - Testare HTTP 200 con `is_compliant=true` quando tutti i codici hanno documenti validi e non scaduti
    - Testare `is_compliant=false` con codice in `missing_documents` quando manca un documento
    - Testare `is_compliant=false` con documento in `invalid_documents` quando lo stato non è valido o è scaduto
    - Testare HTTP 422 quando mancano `documentable_type`, `documentable_id` o `required_codes`
    - Testare HTTP 422 quando `required_codes` è un array vuoto
    - Verificare la coerenza del campo `summary`: `total_required`, `valid`, `invalid`, `missing`
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6_
  - [ ]* 14.2 Scrivere property test per ComplianceGate (Property 25, 26)
    - **Property 25: Summary invariant** — per qualsiasi combinazione di documenti, `valid + invalid + missing == total_required` è sempre vera
    - **Property 26: is_compliant correctness** — `is_compliant=true` sse `missing` e `invalid` sono entrambi zero
    - **Validates: Requirements 9.5, 9.7**

- [x] 15. Implementare i test Feature per Dossier Creation API
  - [x] 15.1 Creare `tests/Feature/Api/DossierCreationTest.php`
    - Testare HTTP 200 con `request_id`, `upload_url` ed `expires_at` per richiesta valida
    - Testare HTTP 422 quando almeno un codice in `required_codes` non esiste
    - Testare che `expires_at` sia a 7 giorni dalla creazione quando `expires_in_days` non è specificato
    - Testare che `expires_at` rispetti `expires_in_days` quando specificato
    - Testare che vengano creati esattamente N `DocumentRequestItem` per N codici in `required_codes`
    - Testare che `upload_url` contenga l'UUID del `DocumentRequest`
    - Testare HTTP 422 quando mancano `documentable_type`, `documentable_id` o `required_codes`
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7_
  - [ ]* 15.2 Scrivere property test per DossierCreationAPI (Property 27, 28, 29)
    - **Property 27: Expiry correctness** — per qualsiasi intero positivo `expires_in_days`, `expires_at == now() + N giorni` (tolleranza 1 secondo)
    - **Property 28: Items count** — per N codici validi, vengono creati esattamente N `DocumentRequestItem`
    - **Property 29: upload_url contains UUID** — `upload_url` contiene sempre il `request_id` come sottostringa
    - **Validates: Requirements 10.4, 10.5, 10.6, 10.8**

- [x] 16. Checkpoint — Eseguire tutti i test Feature API
  - Eseguire `php artisan test --filter=Api` e verificare che tutti i test passino. Chiedere all'utente se ci sono problemi prima di procedere.

- [x] 17. Implementare i test Feature per ProcessDocumentAiJob
  - [x] 17.1 Creare `tests/Feature/Jobs/ProcessDocumentAiJobTest.php`
    - Usare `Http::fake()` per simulare il webhook
    - Verificare che il job implementi `ShouldQueue` con `$tries=3` e `$timeout=60`
    - Testare che in caso di successo il documento venga aggiornato a `DocumentStatus::VERIFIED`
    - Testare che in caso di eccezione il documento venga aggiornato a `DocumentStatus::FAILED` e l'eccezione venga rilanciata
    - Testare che con risposta webhook 2xx, `sync_status` sia `SyncStatus::SYNCED`
    - Testare che con risposta webhook non-2xx, `sync_status` sia `SyncStatus::FAILED`
    - Testare che senza webhook URL configurato venga registrato `Log::warning` e `sync_status` non venga aggiornato
    - Testare che la richiesta webhook includa l'header `X-DMS-Signature` con firma HMAC-SHA256
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7_
  - [ ]* 17.2 Scrivere property test per ProcessDocumentAiJob (Property 30, 31)
    - **Property 30: Webhook sync_status** — per qualsiasi risposta 2xx → `SYNCED`; per qualsiasi risposta non-2xx → `FAILED`
    - **Property 31: HMAC signature** — per qualsiasi payload, l'header `X-DMS-Signature` è sempre presente e contiene una firma HMAC-SHA256 valida
    - **Validates: Requirements 11.4, 11.5, 11.7**

- [x] 18. Implementare i test Feature per FulfillDocumentRequest Listener
  - [x] 18.1 Creare `tests/Feature/Listeners/FulfillDocumentRequestTest.php`
    - Usare `Http::fake()` per simulare il webhook BPM
    - Testare che un documento classificato corrispondente imposti `fulfilled_by_document_id` sull'item corretto
    - Testare che con tutti gli item soddisfatti lo stato del Dossier diventi `'COMPLETED'`
    - Testare che con almeno un item non soddisfatto lo stato del Dossier diventi `'PARTIAL'`
    - Testare che con documento senza `document_type_id` nessun `DocumentRequest` venga modificato
    - Testare che al completamento del Dossier venga inviata una POST al webhook BPM con `event='document_request_completed'` e `bpm_task_id` corretto
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5_
  - [ ]* 18.2 Scrivere property test per FulfillDocumentRequest (Property 32, 33)
    - **Property 32: Dossier completion invariant** — dopo ogni elaborazione, se tutti gli item sono soddisfatti → `COMPLETED`; se almeno uno è aperto → `PARTIAL`
    - **Property 33: Webhook on completion** — ogni transizione a `COMPLETED` invia sempre il webhook con i campi corretti
    - **Validates: Requirements 12.2, 12.3, 12.5**

- [x] 19. Checkpoint finale — Eseguire l'intera test suite
  - Eseguire `php artisan test` e verificare che tutti i test passino senza errori di configurazione dell'ambiente.
  - Eseguire `php artisan test --coverage` per verificare la copertura delle aree critiche.
  - Chiedere all'utente se ci sono fallimenti o aree da approfondire prima di considerare la suite completa.

---

## Notes

- I task marcati con `*` sono opzionali (property-based test) e possono essere saltati per un MVP più rapido
- Ogni property test deve eseguire almeno 100 iterazioni (usare `->repeat(100)` o `dataset()` in Pest)
- Ogni property test include un commento con il numero della property dal design: `// Property N: <titolo>`
- I mock dei classificatori usano `$this->mock(RegexClassifier::class)` — mai istanziare direttamente nei Feature test
- `Http::fake()`, `Queue::fake()`, `Event::fake()`, `Storage::fake()` vanno chiamati nel `setUp()` o all'inizio di ogni test che li richiede
- Il `shouldSkip()` di `MailAttachmentProcessor` è `protected`: nei test Unit usare una sottoclasse anonima o rendere il metodo `public` per i test
