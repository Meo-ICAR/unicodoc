# Requirements Document

## Introduction

Questo documento definisce i requisiti per una test suite completa di UnicoDoc, un'applicazione Laravel 13 + Filament 5.4 per la gestione documentale. La suite copre tutti i componenti critici: metadati dei documenti, pipeline di ingestion email, pipeline di classificazione (Regex + AI), API M2M (Compliance Gate e Dossier Creation), webhook outgoing e gli Enum di dominio.

L'obiettivo è garantire correttezza funzionale, robustezza agli input anomali e assenza di regressioni durante l'evoluzione del sistema.

---

## Glossary

- **Document**: Record principale che rappresenta un file caricato nel sistema, con stato (`DocumentStatus`) e relazione polimorfica verso l'entità proprietaria.
- **DocumentType**: Dizionario globale dei tipi di documento, con codice univoco, pattern regex opzionale e soglie di confidenza AI.
- **DocumentRequest**: Dossier di raccolta documenti identificato da UUID (usato come Magic Link token), con stato (`DossierStatus`) e scadenza.
- **DocumentRequestItem**: Slot all'interno di un Dossier che rappresenta un documento richiesto; può essere soddisfatto da un `Document`.
- **ClassificationOrchestrator**: Servizio che esegue la pipeline Regex → AI → fallback manuale su un `Document`.
- **RegexClassifier**: Classificatore deterministico che confronta il testo/nome del documento con i `regex_pattern` dei `DocumentType`.
- **AiClassifier**: Classificatore basato su LLM che riceve il testo estratto e restituisce `type_id`, `confidence` e `reasoning`.
- **ClassificationResult**: Value object immutabile con `documentTypeId`, `confidenceScore`, `classifierUsed` ed `evidence`.
- **MailSyncService**: Servizio che si connette via IMAP a un `MailAccount` e persiste i messaggi non letti nella tabella `mail_messages`.
- **MailAttachmentProcessor**: Servizio che converte gli allegati email validi in `Document` e scatena l'evento `DocumentUploaded`.
- **MailMessageProcessor**: Servizio che gestisce i messaggi senza allegati validi, aggiornando il Dossier e notificando il BPM via webhook.
- **ComplianceGate**: Endpoint `POST /api/v1/compliance/check` che valuta se un'entità possiede tutti i documenti richiesti in stato valido.
- **DossierCreationAPI**: Endpoint `POST /api/v1/requests` che crea un `DocumentRequest` con i relativi `DocumentRequestItem`.
- **WebhookDispatcher**: Componente che invia notifiche HTTP outgoing al BPM al completamento di un Dossier o alla ricezione di una domanda.
- **DocumentStatus**: Enum PHP nativo con valori `UPLOADED`, `VERIFIED`, `REJECTED`, `EXPIRED`, `REVOKED`, `PENDING`.
- **SyncStatus**: Enum PHP nativo con valori `LOCAL`, `SYNCING`, `SYNCED`, `FAILED`.
- **DossierStatus**: Stato del `DocumentRequest`: `PENDING`, `PARTIAL`, `COMPLETED`, `EXPIRED`.
- **Anti-Noise Rule**: Regola che esclude allegati < 8 KB e MIME type `image/gif` dalla conversione in documenti.
- **Magic Link**: URL pubblico generato dall'UUID del `DocumentRequest`, usato per il caricamento guest.
- **ProcessDocumentAiJob**: Job asincrono (`ShouldQueue`) che esegue OCR/LLM su un documento e invia il webhook di completamento.

---

## Requirements

### Requirement 1: Enum di Dominio

**User Story:** Come sviluppatore, voglio che gli Enum `DocumentStatus` e `SyncStatus` espongano label, colori e icone corretti per ogni valore, così che l'interfaccia Filament mostri badge coerenti senza logica duplicata.

#### Acceptance Criteria

1. THE `DocumentStatus` Enum SHALL definire i valori `UPLOADED`, `VERIFIED`, `REJECTED`, `EXPIRED`, `REVOKED`, `PENDING` come stringhe PHP native.
2. THE `DocumentStatus` Enum SHALL restituire una label non vuota tramite `getLabel()` per ciascuno dei sei valori.
3. THE `DocumentStatus` Enum SHALL restituire un colore Filament valido (`warning`, `success`, `danger`, `gray`) tramite `getColor()` per ciascuno dei sei valori.
4. THE `DocumentStatus` Enum SHALL restituire un nome icona Heroicon non vuoto tramite `getIcon()` per ciascuno dei sei valori.
5. THE `SyncStatus` Enum SHALL definire i valori `LOCAL`, `SYNCING`, `SYNCED`, `FAILED` come stringhe PHP native.
6. THE `SyncStatus` Enum SHALL restituire una label, un colore e un'icona non nulli per ciascuno dei quattro valori.
7. WHEN viene fornita una stringa valida, THE `DocumentStatus` Enum SHALL essere istanziabile tramite `DocumentStatus::from(string)` senza eccezioni.
8. IF viene fornita una stringa non valida, THEN THE `DocumentStatus` Enum SHALL lanciare `ValueError` tramite `DocumentStatus::from(string)`.

---

### Requirement 2: Modello DocumentType — Logiche di Business

**User Story:** Come operatore, voglio che il modello `DocumentType` calcoli correttamente scadenze, soglie di confidenza e notifiche, così da automatizzare la gestione del ciclo di vita dei documenti.

#### Acceptance Criteria

1. WHEN `allow_auto_verification` è `true` e `confidence` è maggiore di 95, THE `DocumentType` SHALL restituire `true` da `canAutoVerify(confidence)`.
2. WHEN `allow_auto_verification` è `false` oppure `confidence` è minore o uguale a 95, THE `DocumentType` SHALL restituire `false` da `canAutoVerify(confidence)`.
3. WHEN `confidence` è maggiore o uguale a `min_confidence`, THE `DocumentType` SHALL restituire `true` da `meetsConfidenceThreshold(confidence)`.
4. WHEN `retention_years` è `null`, THE `DocumentType` SHALL restituire `false` da `hasRetentionPolicy()` e `null` da `getRetentionDate(date)`.
5. WHEN `retention_years` è un intero positivo N, THE `DocumentType` SHALL restituire una data pari a `documentDate + N anni` da `getRetentionDate(documentDate)`.
6. WHEN la data di ritenzione è nel passato, THE `DocumentType` SHALL restituire `true` da `isExpired(documentDate)`.
7. WHEN la data di ritenzione è nel futuro, THE `DocumentType` SHALL restituire `false` da `isExpired(documentDate)`.
8. WHEN `notify_days_before` contiene [30, 7] e mancano 10 giorni alla scadenza, THE `DocumentType` SHALL restituire `[7]` da `shouldNotify(documentDate)`.
9. FOR ALL valori interi non negativi di `confidence`, THE `DocumentType` SHALL restituire un booleano da `meetsConfidenceThreshold(confidence)` (proprietà: nessuna eccezione per input validi).

---

### Requirement 3: Modello Document — Scadenza e Stato

**User Story:** Come operatore, voglio che il modello `Document` esponga metodi corretti per verificare scadenza e transizioni di stato, così da filtrare i documenti da revisionare.

#### Acceptance Criteria

1. WHEN `expires_at` è una data nel passato, THE `Document` SHALL restituire `true` da `isExpired()`.
2. WHEN `expires_at` è `null`, THE `Document` SHALL restituire `false` da `isExpired()`.
3. WHEN `expires_at` è entro N giorni da oggi e N è il parametro passato, THE `Document` SHALL restituire `true` da `isNearExpiry(N)`.
4. WHEN lo stato è `UPLOADED`, THE `Document` SHALL restituire `true` da `canBeVerified()` e `true` da `canBeRejected()`.
5. WHEN lo stato è `VERIFIED`, THE `Document` SHALL restituire `false` da `canBeVerified()`.
6. THE `Document` SHALL castare il campo `status` all'Enum `DocumentStatus` e il campo `sync_status` all'Enum `SyncStatus`.
7. THE `Document` SHALL castare il campo `metadata` ad array PHP.

---

### Requirement 4: Modello DocumentRequest — Stato e Scadenza

**User Story:** Come integratore BPM, voglio che il modello `DocumentRequest` esponga metodi di stato e scadenza affidabili, così da sapere se un Dossier è ancora attivo o è scaduto.

#### Acceptance Criteria

1. WHEN `status` è `PENDING`, THE `DocumentRequest` SHALL restituire `true` da `isPending()` e `false` dagli altri metodi di stato.
2. WHEN `status` è `COMPLETED`, THE `DocumentRequest` SHALL restituire `true` da `isCompleted()`.
3. WHEN `expires_at` è nel passato, THE `DocumentRequest` SHALL restituire `true` da `isExpired()` indipendentemente dal valore di `status`.
4. WHEN `expires_at` è nel futuro e `status` non è `EXPIRED`, THE `DocumentRequest` SHALL restituire `false` da `isExpired()`.
5. THE `DocumentRequest` SHALL restituire una stringa URL non vuota da `getMagicLink()`.
6. THE `DocumentRequest` SHALL restituire la label italiana corretta da `getFormattedStatus()` per ciascuno dei quattro stati.
7. WHEN `expires_at` è tra 1 e N giorni, THE `DocumentRequest` SHALL essere incluso nello scope `expiringSoon(N)`.
8. THE `DocumentRequest` SHALL restituire un valore tra 0 e 100 da `getExpiryProgress()` per qualsiasi combinazione valida di `created_at` ed `expires_at`.

---

### Requirement 5: RegexClassifier

**User Story:** Come sistema di classificazione, voglio che il `RegexClassifier` identifichi deterministicamente il tipo di documento tramite pattern regex, così da evitare chiamate AI non necessarie.

#### Acceptance Criteria

1. WHEN un `DocumentType` ha `regex_pattern` non nullo e il pattern corrisponde al testo del documento, THE `RegexClassifier` SHALL restituire un `ClassificationResult` con `confidenceScore` pari a 100 e `classifierUsed` uguale a `'regex'`.
2. WHEN nessun pattern corrisponde al testo del documento, THE `RegexClassifier` SHALL restituire `null`.
3. WHEN il documento non ha né `extracted_text` né `name`, THE `RegexClassifier` SHALL restituire `null`.
4. WHEN esistono più `DocumentType` con pattern corrispondente, THE `RegexClassifier` SHALL restituire il tipo con `priority` più alta.
5. THE `RegexClassifier` SHALL eseguire il match in modalità case-insensitive.
6. FOR ALL stringhe di testo valide e pattern regex validi, THE `RegexClassifier` SHALL restituire `null` oppure un `ClassificationResult` senza lanciare eccezioni (proprietà: robustezza agli input).
7. THE `ClassificationResult` restituito SHALL contenere nel campo `evidence` la stringa corrispondente al match (`matched_string`).

---

### Requirement 6: ClassificationOrchestratorService

**User Story:** Come sistema, voglio che l'orchestratore esegua la pipeline Regex → AI → fallback in modo corretto, così da classificare automaticamente il maggior numero possibile di documenti.

#### Acceptance Criteria

1. WHEN il `RegexClassifier` restituisce un risultato valido, THE `ClassificationOrchestratorService` SHALL aggiornare il documento con il `document_type_id` corrispondente senza invocare l'`AiClassifier`.
2. WHEN il `RegexClassifier` restituisce `null` e l'`AiClassifier` restituisce un risultato valido, THE `ClassificationOrchestratorService` SHALL aggiornare il documento con i dati dell'AI.
3. WHEN entrambi i classificatori restituiscono `null`, THE `ClassificationOrchestratorService` SHALL impostare `status_code` a `'DA VERIFICARE'` e `document_type_id` a `null`.
4. WHEN `confidenceScore` è maggiore o uguale a `min_confidence` del tipo trovato, THE `ClassificationOrchestratorService` SHALL impostare `status_code` a `'IN VERIFICA'`.
5. WHEN `confidenceScore` è maggiore o uguale a 95 e `allow_auto_verification` è `true`, THE `ClassificationOrchestratorService` SHALL impostare `status_code` a `'OK'` e valorizzare `verified_at`.
6. WHEN `confidenceScore` è inferiore a `min_confidence`, THE `ClassificationOrchestratorService` SHALL impostare `status_code` a `'RICHIESTA INFO'`.
7. THE `ClassificationOrchestratorService` SHALL creare un record `ClassificationLog` per ogni classificazione riuscita.
8. WHEN il documento ha già un `document_type_id` valorizzato, THE `RunDocumentClassification` listener SHALL saltare l'elaborazione senza invocare l'orchestratore.

---

### Requirement 7: Anti-Noise Rule e MailAttachmentProcessor

**User Story:** Come sistema di ingestion email, voglio che gli allegati rumorosi (troppo piccoli, inline o GIF) vengano scartati, così da non inquinare il repository documentale con loghi e firme.

#### Acceptance Criteria

1. WHEN un allegato ha `size` inferiore a 8192 byte, THE `MailAttachmentProcessor` SHALL restituire `true` da `shouldSkip()`.
2. WHEN un allegato ha `mime_type` uguale a `'image/gif'`, THE `MailAttachmentProcessor` SHALL restituire `true` da `shouldSkip()`.
3. WHEN un allegato ha `is_inline` uguale a `true` e `mime_type` che inizia con `'image/'`, THE `MailAttachmentProcessor` SHALL restituire `true` da `shouldSkip()`.
4. WHEN un allegato ha `size` maggiore o uguale a 8192 byte, `mime_type` non nella blacklist e `is_inline` uguale a `false`, THE `MailAttachmentProcessor` SHALL restituire `false` da `shouldSkip()`.
5. WHEN `shouldSkip()` restituisce `false`, THE `MailAttachmentProcessor` SHALL creare un record `Document` con `source_app` uguale a `'email'` e scatenare l'evento `DocumentUploaded`.
6. WHEN il soggetto della mail contiene il pattern `[Ref: <uuid>]`, THE `MailAttachmentProcessor` SHALL associare il messaggio al `DocumentRequest` con quell'UUID.
7. FOR ALL combinazioni di `size`, `mime_type` e `is_inline`, THE `MailAttachmentProcessor` SHALL restituire un booleano da `shouldSkip()` senza eccezioni (proprietà: completezza della regola anti-noise).

---

### Requirement 8: MailMessageProcessor — Gestione Domande

**User Story:** Come sistema, voglio che le email senza allegati validi ma con testo significativo aggiornino il Dossier e notifichino il BPM, così da non perdere comunicazioni dell'utente.

#### Acceptance Criteria

1. WHEN un messaggio non ha allegati validi e il corpo testo ha più di 20 caratteri, THE `MailMessageProcessor` SHALL impostare `has_unread_messages` a `true` sul `DocumentRequest` associato.
2. WHEN un messaggio non ha allegati validi e il corpo testo ha più di 20 caratteri, THE `MailMessageProcessor` SHALL aggiornare `last_message_received` con il testo ripulito dall'HTML.
3. WHEN un messaggio non ha allegati validi e il corpo testo ha più di 20 caratteri, THE `MailMessageProcessor` SHALL inviare una richiesta HTTP POST all'URL configurato in `services.bpm.webhook_url`.
4. WHEN il messaggio è stato processato, THE `MailMessageProcessor` SHALL impostare `is_processed` a `true` sul record `MailMessage`.
5. WHEN il corpo testo ha 20 caratteri o meno, THE `MailMessageProcessor` SHALL non aggiornare il `DocumentRequest` e non inviare webhook.

---

### Requirement 9: Compliance Gate API

**User Story:** Come sistema BPM, voglio che l'endpoint `POST /api/v1/compliance/check` valuti correttamente la conformità documentale di un'entità, così da bloccare o sbloccare automaticamente i processi aziendali.

#### Acceptance Criteria

1. WHEN tutti i codici richiesti hanno un documento con stato valido e non scaduto, THE `ComplianceGate` SHALL restituire `is_compliant: true` e HTTP 200.
2. WHEN almeno un codice richiesto non ha documenti associati all'entità, THE `ComplianceGate` SHALL restituire `is_compliant: false` e includere il codice in `missing_documents`.
3. WHEN almeno un documento ha stato non valido o è scaduto, THE `ComplianceGate` SHALL restituire `is_compliant: false` e includere il documento in `invalid_documents`.
4. WHEN la richiesta non include `documentable_type`, `documentable_id` o `required_codes`, THE `ComplianceGate` SHALL restituire HTTP 422 con errori di validazione.
5. THE `ComplianceGate` SHALL restituire nel campo `summary` i contatori `total_required`, `valid`, `invalid` e `missing` coerenti con i dettagli.
6. WHEN `required_codes` è un array vuoto, THE `ComplianceGate` SHALL restituire HTTP 422 (array richiesto non vuoto).
7. FOR ALL combinazioni valide di documenti presenti, assenti e invalidi, THE `ComplianceGate` SHALL garantire che `valid + invalid + missing == total_required` (proprietà: coerenza del sommario).

---

### Requirement 10: Dossier Creation API

**User Story:** Come sistema BPM, voglio che l'endpoint `POST /api/v1/requests` crei un Dossier con i relativi slot documento e restituisca un Magic Link, così da avviare la raccolta documentale guest.

#### Acceptance Criteria

1. WHEN la richiesta è valida e tutti i `required_codes` esistono, THE `DossierCreationAPI` SHALL creare un `DocumentRequest` con UUID, restituire HTTP 200 e includere `request_id`, `upload_url` ed `expires_at`.
2. WHEN almeno un codice in `required_codes` non esiste nella tabella `document_types`, THE `DossierCreationAPI` SHALL restituire HTTP 422 con messaggio di errore.
3. WHEN `expires_in_days` non è specificato, THE `DossierCreationAPI` SHALL impostare `expires_at` a 7 giorni dalla creazione.
4. WHEN `expires_in_days` è specificato, THE `DossierCreationAPI` SHALL impostare `expires_at` al numero di giorni indicato dalla creazione.
5. THE `DossierCreationAPI` SHALL creare un `DocumentRequestItem` per ciascun codice in `required_codes`.
6. THE `DossierCreationAPI` SHALL restituire un `upload_url` che contiene l'UUID del `DocumentRequest` come token.
7. WHEN `documentable_type` o `documentable_id` o `required_codes` sono assenti, THE `DossierCreationAPI` SHALL restituire HTTP 422.
8. FOR ALL valori interi positivi di `expires_in_days`, THE `DossierCreationAPI` SHALL creare il Dossier con la scadenza corretta (proprietà: correttezza della scadenza).

---

### Requirement 11: ProcessDocumentAiJob — Retry e Webhook

**User Story:** Come sistema, voglio che il job `ProcessDocumentAiJob` gestisca correttamente i retry in caso di errore e invii il webhook di completamento, così da garantire affidabilità nel processing asincrono.

#### Acceptance Criteria

1. THE `ProcessDocumentAiJob` SHALL implementare `ShouldQueue` con `$tries` pari a 3 e `$timeout` pari a 60 secondi.
2. WHEN il processing AI ha successo, THE `ProcessDocumentAiJob` SHALL aggiornare lo stato del documento a `DocumentStatus::VERIFIED`.
3. WHEN il processing AI fallisce con un'eccezione, THE `ProcessDocumentAiJob` SHALL aggiornare lo stato del documento a `DocumentStatus::FAILED` e rilanciare l'eccezione per il retry.
4. WHEN il webhook URL è configurato e la risposta HTTP è 2xx, THE `ProcessDocumentAiJob` SHALL aggiornare `sync_status` a `SyncStatus::SYNCED`.
5. WHEN la risposta HTTP del webhook non è 2xx, THE `ProcessDocumentAiJob` SHALL aggiornare `sync_status` a `SyncStatus::FAILED`.
6. WHEN il webhook URL non è configurato, THE `ProcessDocumentAiJob` SHALL registrare un warning nel log e non aggiornare `sync_status`.
7. THE `ProcessDocumentAiJob` SHALL includere nell'header della richiesta webhook la firma HMAC-SHA256 `X-DMS-Signature`.

---

### Requirement 12: FulfillDocumentRequest Listener

**User Story:** Come sistema, voglio che il listener `FulfillDocumentRequest` colleghi automaticamente un documento classificato allo slot corrispondente nel Dossier, così da aggiornare lo stato del Dossier in tempo reale.

#### Acceptance Criteria

1. WHEN un documento classificato corrisponde a un `DocumentRequestItem` aperto per la stessa entità, THE `FulfillDocumentRequest` SHALL impostare `fulfilled_by_document_id` sull'item.
2. WHEN tutti gli item di un `DocumentRequest` sono soddisfatti, THE `FulfillDocumentRequest` SHALL aggiornare lo stato del Dossier a `'COMPLETED'`.
3. WHEN almeno un item rimane non soddisfatto, THE `FulfillDocumentRequest` SHALL aggiornare lo stato del Dossier a `'PARTIAL'`.
4. WHEN il documento non ha `document_type_id` valorizzato, THE `FulfillDocumentRequest` SHALL non modificare alcun `DocumentRequest`.
5. WHEN il Dossier raggiunge lo stato `COMPLETED`, THE `FulfillDocumentRequest` SHALL inviare una richiesta HTTP POST al webhook BPM con `event: 'document_request_completed'` e `bpm_task_id`.

---

### Requirement 13: Infrastruttura della Test Suite

**User Story:** Come sviluppatore, voglio che la test suite sia configurata correttamente con database in-memory, factory e trait condivisi, così da poter eseguire i test in isolamento e in modo rapido.

#### Acceptance Criteria

1. THE `TestSuite` SHALL usare SQLite in-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) per tutti i test di unità e feature.
2. THE `TestSuite` SHALL usare il trait `RefreshDatabase` per garantire l'isolamento tra i test.
3. THE `TestSuite` SHALL fornire Factory per `Document`, `DocumentType`, `DocumentRequest`, `DocumentRequestItem`, `MailMessage` e `MailAttachment`.
4. THE `TestSuite` SHALL usare `Http::fake()` per simulare le chiamate HTTP outgoing verso BPM e AI senza effettuare richieste reali.
5. THE `TestSuite` SHALL usare `Queue::fake()` per verificare il dispatch dei job senza eseguirli realmente.
6. THE `TestSuite` SHALL usare `Event::fake()` per verificare il dispatch degli eventi senza eseguire i listener reali.
7. THE `TestSuite` SHALL usare `Storage::fake()` per simulare il filesystem senza scrivere file reali su disco.
8. WHEN viene eseguito il comando `php artisan test`, THE `TestSuite` SHALL completare senza errori di configurazione dell'ambiente.
