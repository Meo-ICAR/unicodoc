Ecco la specifica architetturale completa e aggiornata (Versione 1.1), arricchita con tutti i nuovi domini operativi, le pipeline di ingestion via email, le API per il BPM, i Dossier (Magic Links) e la gestione delle eccezioni conversazionali.

---

# UnicoDoc — Document Management Integration Specification

> Specification for integrating the BPM application with this separate Laravel 13 + Filament 5.4 document-management application that shares the same MySQL server and manages documents, classifications, inbound email ingestion, automated collection dossiers, and document metadata.

---

## Document Metadata

- **Document version:** 1.1
- **Last updated:** 2026-04-11
- **Applies to:** external document-management application
- **Framework target:** Laravel 13
- **Admin target:** Filament 5.4
- **Media layer:** Spatie Media Library
- **Database topology:** shared MySQL server with logical application separation

---

## Purpose

This specification describes this external document-management application that works alongside the BPM platform.

This app is responsible for:

- document storage and metadata management
- omnichannel document ingestion (Manual, Guest Portal, Email IMAP)
- document classification (regex-based first-pass recognition, AI-assisted enrichment)
- proactive document collection (Magic Links / Dossiers)
- document verification workflow and conversational exception handling
- management of expiration, signature, and document validity states
- compliance gating via REST API for the BPM application
- document attachment to domain entities through a polymorphic relationship

The BPM app and this document-management app are separate Laravel applications, but they operate on the same MySQL server and share compatible business concepts.

---

## Core Concepts & Domain Expansion

### 1. Ownership Model (Polymorphic Entities)

Documents are attached through a polymorphic relation (`documentable_type` and `documentable_id`). To fully support the corporate ecosystem, UnicoDoc recognizes the following operational domain entities:

- **Company:** The tenant or main corporate entity.
- **Employee:** Internal staff members. They serve as documentable owners for HR files, certifications, and internal training.
- **Client:** Represents both B2C and B2B customers.
    - `is_company = false`: Individual customers/private citizens.
    - `is_company = true`: Professional consultants or external B2B service providers.
- **Agent:** External commercial agents or brokers who operate on behalf of the company but are not employees.
- **Principal:** An external entity (e.g., an insurance carrier or bank) that the company represents as a fiduciary or agent.
- **RegulatoryBody:** External institutions (e.g., IVASS, OAM, GDPR Authority) that impose compliance requirements and "own" regulatory documents or inspection reports.
- **Practice:** Specific transactional entities (e.g., a specific loan application or contract).

### 2. Document Type (`document_types`)

The core classification table defining what kind of document the system recognizes, its lifecycle rules (expiration, signature), and the Regex/AI logic needed to auto-classify it. _This is a global, tenant-agnostic lookup table._

### 3. Document Status (`document_status`)

The canonical verification vocabulary (e.g., `DA VERIFICARE`, `OK`, `SCADUTO`, `DIFFORME`, `RICHIESTA INFO`).

### 4. Document (`documents`)

The core operational record representing the physical/logical document, linking the `documentable` owner, the `document_type`, the physical media (via Spatie), AI metadata, and its verification state.

---

## Proactive Collection (Dossiers & Magic Links)

To transform UnicoDoc from a passive archive into an active collection engine, it implements the **Dossier System**. The BPM can request UnicoDoc to collect specific missing documents from a user.

### Data Models

**`document_requests`**

- `id`: UUID (serves as the secure Magic Link token).
- `documentable_type` / `documentable_id`: Who needs to upload the documents.
- `sender_email`: Target email for the request.
- `bpm_process_id` / `bpm_task_id`: Tracking metadata to reply to the BPM.
- `status`: `PENDING`, `PARTIAL`, `COMPLETED`, `EXPIRED`.
- `has_unread_messages`: Boolean flag for conversational exceptions.
- `last_message_received`: Text of the last email reply without attachments.
- `expires_at`: Validity of the request.

**`document_request_items`**

- `document_request_id`: Link to the parent dossier.
- `document_type_id`: The specific document required (e.g., KYC_ID).
- `fulfilled_by_document_id`: Populated when the user successfully uploads the file.

### The Collection Flow

1. **Creation:** BPM calls UnicoDoc API to create a `document_request` indicating the required `document_types`.
2. **Delivery:** User receives an email with a secure Magic Link (`/dossier/{uuid}`).
3. **Upload:** User accesses a Guest Livewire portal to upload the files.
4. **Resolution:** Uploaded files are instantiated as `documents` (status: `DA VERIFICARE`), the request item is marked fulfilled, and UnicoDoc triggers a Webhook to the BPM upon completion.

---

## Omnichannel Ingestion System (Email Pipeline)

Since users often reply to automated emails with attachments instead of using the provided portal, UnicoDoc features a robust **IMAP Ingestion Pipeline** with Anti-Noise and Contextual Routing.

### Data Models

**`mail_accounts`**

- `company_id`: Tenant owner.
- `email`, `protocol`, `host`, `port`, `encryption`, `credentials` (encrypted).
- `last_synced_at`.

**`mail_messages` (The Buffer)**

- `mail_account_id`, `message_id` (header ID to prevent duplicates).
- `from`, `to`, `subject`, `body_text`.
- `is_processed`: Boolean flag.

**`mail_attachments`**

- `mail_message_id`, `filename`, `mime_type`, `size`, `is_inline`.
- `document_id`: Populated once converted into a real `document`.

### The Pipeline Architecture

1. **The Vacuum (`MailSyncService`):** A scheduled command connects to IMAP, downloading unseen emails into the buffer (`mail_messages` / `mail_attachments`).
2. **Anti-Noise Filter:** Ignores useless files like `.gif` signatures, social logos (`< 8KB`), or inline images.
3. **Context Routing:** The system searches the email subject for a tracking Tag (e.g., `[Ref: uuid]`) or checks the sender's email to associate the inbound email with an open `document_request`.
4. **Document Creation & Classification:** Valid attachments are moved to `documents` with `source_app = 'email'` and passed to the Regex/AI pipeline for auto-classification.
5. **Conversational Exceptions (`MailMessageProcessor`):** If a user replies _without_ attachments but with text (e.g., asking a question), the pipeline flags the associated `document_request` (`has_unread_messages = true`), saves the text, and fires a Webhook to the BPM to alert a human operator.

---

## Classification Pipeline (Regex + AI)

The classification process is intentionally multi-step to optimize for speed, cost, and accuracy:

1. **Regex First:** Deterministic matching using file name, structured metadata, and `document_types.regex_pattern`.
2. **AI Second:** If Regex fails, an AI Classifier analyzes the file (via OCR/Vision) using `document_types.AiPattern`. AI provides the `document_type_id`, an `ai_abstract`, and an `ai_confidence_score`.
3. **Fulfillment Listener:** If a document is auto-classified (e.g., AI says it's a KYC) and belongs to a user with an open Dossier waiting for a KYC, the system auto-links the document to the request item.
4. **Human Verification:** Operators use Filament to review `DA VERIFICARE` documents, overriding or confirming the AI's choice.

---

## BPM Integration Interfaces (Machine-to-Machine)

The BPM App and UnicoDoc communicate via strictly defined REST APIs (secured via Laravel Sanctum) and Webhooks.

### 1. Compliance Gate API (BPM -> UnicoDoc)

Used by the BPM to check if an entity has valid documents to proceed with a workflow step.

- **Endpoint:** `POST /api/v1/compliance/check`
- **Payload:** `documentable_type`, `documentable_id`, `required_codes` (array of document type codes).
- **Response:** Evaluates the highest-priority document for each requested code. Returns `is_compliant: boolean`, grouped summaries (`valid_documents`, `missing_documents`, `invalid_documents` with reasons like "SCADUTO").

### 2. Dossier Creation API (BPM -> UnicoDoc)

Used to delegate document collection to UnicoDoc.

- **Endpoint:** `POST /api/v1/requests`
- **Payload:** Entity identifiers, `required_codes`, `sender_email`, `bpm_task_id`.
- **Response:** Returns the generated `request_id` and the public `upload_url` (Magic Link) to be sent to the user.

### 3. Webhooks (UnicoDoc -> BPM)

UnicoDoc pushes state changes back to the BPM so workflows can resume automatically:

- `document_request_completed`: Fired when all requested items in a Dossier are fulfilled.
- `document_request_question_received`: Fired when the IMAP pipeline detects a textual reply from the user without valid attachments, requiring agent intervention.

---

## Summary of Architectural Tradeoffs

- **Why Regex First, AI Second?** Regex is cheap, fast, and explainable. AI provides semantic fallback, metadata extraction, and conversational bridging.
- **Why Shared `document_types`?** Classification vocabulary must be normalized across all tenants and workflows to ensure API reliability.
- **Why a Separate App with the Same Database?** Document ingestion (IMAP), heavy file storage (Spatie), and AI processing have entirely different performance profiles and scaling needs than operational BPM workflows. Sharing the DB allows zero-latency data reads while decoupling the application logic.
