# UnicoDoc — AI-Optimized Document Management Specification

> Specification for integrating the BPM application with this separate Laravel 13 + Filament 5.4 document-management application that shares the same MySQL server and manages documents, classifications, inbound email ingestion, automated collection dossiers, and document metadata. **Optimized for Autonomous AI Code Generation (Vibe Coding).**

---

## 0. AI Vibe Coding Guidelines & Constraints

**CRITICAL INSTRUCTIONS FOR AI AGENT:**

- **Tech Stack:** Laravel 13, PHP 8.4+, Filament 5.4, Livewire 3, Spatie Media Library v11+.
- **PHP Features:** Strictly use Constructor Property Promotion, typed properties, strict return types, and native PHP 8.4 Enums for all states (`DocumentStatus`, `RequestStatus`).
- **Filament Pattern:** Use standard Filament Resource classes. For complex verifications, use Action modals or custom Livewire components within the Resource view.
- **Service Pattern:** Keep Controllers and Livewire components thin. Move heavy logic (Classification, IMAP syncing) into dedicated `app/Services/` or `app/Actions/`.
- **Database:** Assume the database is shared with the BPM. Do NOT create Foreign Key constraints referencing tables outside of UnicoDoc (e.g., `users`, `companies`). Use logical ID indexing instead.
- **Queues:** All IMAP ingestion, AI processing, and Webhook dispatching MUST be queued (`ShouldQueue`).

---

## 1. Document Metadata

- **Document version:** 1.2 (Vibe Coding Edition)
- **Database topology:** Shared MySQL server with logical application separation (UnicoDoc prefix or specific schema boundaries).

---

## 2. Core Concepts & Domain Expansion

### Ownership Model (Polymorphic Entities)

Documents are attached through a polymorphic relation (`documentable_type` and `documentable_id`).
_Allowed `documentable_type` strings:_ `company`, `employee`, `client`, `agent`, `principal`, `regulatory_body`, `practice`.

### Explicit Enums

The AI must generate these native PHP Enums:

- `DocumentStatus`: `TO_VERIFY`, `APPROVED`, `EXPIRED`, `NON_COMPLIANT`, `INFO_REQUESTED`.
- `DossierStatus`: `PENDING`, `PARTIAL`, `COMPLETED`, `EXPIRED`.

---

## 3. Data Dictionary (Strict Schema for Migrations)

### `document_types` (Global Dictionary)

- `id`: ulid/bigIncrements
- `code`: string (unique, e.g., 'KYC_ID', 'PAYSLIP')
- `name`: string
- `requires_signature`: boolean (default false)
- `validity_days`: integer (nullable)
- `regex_pattern`: string (nullable)
- `ai_prompt_context`: text (nullable)

### `documents` (Core Record)

- `id`: uuid (Primary Key)
- `documentable_type`: string
- `documentable_id`: unsignedBigInteger
- `document_type_id`: foreignId
- `status`: string (Enum: DocumentStatus, default 'TO_VERIFY')
- `source_app`: string (enum: 'manual', 'portal', 'email', 'bpm_api')
- `ai_abstract`: json (nullable, stores extraction data)
- `ai_confidence_score`: decimal(5,2) (nullable)
- `expires_at`: date (nullable)

### `document_requests` (Dossier / Magic Link)

- `id`: uuid (Primary Key, used as Magic Link token)
- `documentable_type`: string
- `documentable_id`: unsignedBigInteger
- `sender_email`: string
- `bpm_process_id`: string (nullable)
- `bpm_task_id`: string (nullable)
- `status`: string (Enum: DossierStatus, default 'PENDING')
- `has_unread_messages`: boolean (default false)
- `last_message_received`: text (nullable)
- `expires_at`: datetime

### `document_request_items`

- `id`: bigIncrements
- `document_request_id`: foreignId (uuid)
- `document_type_id`: foreignId
- `fulfilled_by_document_id`: foreignId (uuid, nullable)

---

## 4. Omnichannel Ingestion System (Email Pipeline)

**AI Agent Implementation Task:**

1.  Create `MailSyncCommand` (runs every 5 mins).
2.  Use `php-imap` or `webklex/php-imap` to ingest emails into `mail_messages` table.
3.  **Anti-Noise Rule:** Ignore attachments `< 8KB` and mime types `image/gif`.
4.  **Routing Logic:** Regex match subject for `\[Ref:\s*([a-f0-9\-]{36})\]` to extract the `document_requests.id`.
5.  If attachment exists -> Create `Document` -> Trigger `ClassifyDocumentJob`.
6.  If NO attachment but body text exists -> Update `document_requests` (set `has_unread_messages = true`, update `last_message_received`) -> Dispatch `WebhookQuestionReceivedJob`.

---

## 5. Classification Pipeline (Regex + AI)

**AI Agent Implementation Task (`DocumentClassifierService`):**

- **Step 1:** Check `document_types` where `regex_pattern` is not null. Run `preg_match` against the file name. If match -> Link type, set confidence 100, return.
- **Step 2 (Fallback):** If no regex match, trigger LLM API (e.g., OpenAI Vision / Anthropic).
    - _Prompt logic:_ Inject the list of available `document_types.code` and the file context.
    - _Expected output:_ Strict JSON `{ "type_code": "...", "confidence": 0.0-100.0, "abstract": {} }`.
- **Step 3:** If matched to an open `document_requests`, update the corresponding `document_request_items.fulfilled_by_document_id`.

---

## 6. Strict API Contracts (Machine-to-Machine)

**AI Agent Instructions:** Use Laravel FormRequests for validation. Return strict JSON resources.

### 1. Compliance Gate API

- **Endpoint:** `POST /api/v1/compliance/check`
- **Payload:**
    ```json
    {
        "documentable_type": "client",
        "documentable_id": 1234,
        "required_codes": ["KYC_ID", "LIVELINESS_VIDEO"]
    }
    ```
- **Response (200 OK):**
    ```json
    {
        "is_compliant": false,
        "valid_documents": { "KYC_ID": "uuid-here" },
        "missing_documents": ["LIVELINESS_VIDEO"],
        "invalid_documents": {}
    }
    ```

### 2. Dossier Creation API

- **Endpoint:** `POST /api/v1/requests`
- **Payload:**
    ```json
    {
        "documentable_type": "client",
        "documentable_id": 1234,
        "required_codes": ["PAYSLIP", "TAX_RETURN"],
        "sender_email": "user@example.com",
        "bpm_task_id": "task_890",
        "expires_in_days": 7
    }
    ```
- **Response (201 Created):**
    ```json
    {
        "request_id": "uuid-1234-5678",
        "upload_url": "https://unicodoc.app/dossier/uuid-1234-5678",
        "status": "PENDING"
    }
    ```

### 3. Webhook Payloads (Outgoing)

The AI must implement a `WebhookDispatchService` using Laravel's HTTP Client.

- **Event: Dossier Completed**
    ```json
    {
        "event": "document_request_completed",
        "bpm_task_id": "task_890",
        "request_id": "uuid-1234-5678",
        "timestamp": "2026-04-11T10:00:00Z"
    }
    ```

---

### Perché queste aggiunte sono fondamentali per il Vibe Coding?

1. **Eliminazione delle scelte banali:** L'AI non ti chiederà se usare UUID o interi per i documenti, glielo abbiamo imposto. Non creerà tabelle pivot strane, sa esattamente come strutturare `document_requests`.
2. **Standardizzazione degli I/O:** Avendo fornito i JSON esatti per le API, l'agente genererà i FormRequest e i JsonResource perfettamente aderenti a ciò che il BPM si aspetta, riducendo i bug di integrazione al minimo.
3. **Task List implicita:** Leggendo le sezioni "AI Agent Implementation Task", l'editor sa in quale ordine procedere (es. prima l'infrastruttura Mail, poi le code, poi l'AI). Puoi letteralmente evidenziare la sezione 4 e dirgli: _"Implementa questo blocco"_.
