Ecco una proposta per un `README.md` professionale, strutturato per comunicare il valore tecnico e funzionale di **UnicoDoc** sia a un utente business che a un team di sviluppo.

---

# 📄 UnicoDoc — AI-Powered Document Compliance & Orchestration

> **Trasforma la gestione documentale passiva in un motore di compliance attivo.**

UnicoDoc è una piattaforma di gestione documentale di nuova generazione progettata per affiancare sistemi BPM complessi. Sviluppata con **Laravel 13** e **Filament 5.4**, automatizza l'intero ciclo di vita del documento: dall'ingestione multicanale alla classificazione tramite AI, fino alla verifica della compliance legale.

---

## 🚀 Caratteristiche Principali

### 🤖 Classificazione Intelligente a Due Livelli

Non perdere tempo con la selezione manuale. UnicoDoc utilizza un processo ibrido:

1.  **Regex First:** Riconoscimento istantaneo basato su pattern deterministici e metadati del file.
2.  **AI Fallback:** Se la Regex fallisce, un motore di Vision/OCR (GPT-4o/Claude) analizza il contenuto per classificare il documento, estrarre abstract e fornire un punteggio di confidenza.

### 📧 Ingestione Omnicanale & Anti-Noise

Dimentica il download manuale degli allegati dalle email.

- **Pipeline IMAP:** Sincronizzazione automatica delle caselle email aziendali.
- **Filtro Anti-Rumore:** Ignora automaticamente loghi social, firme e icone (file < 8KB).
- **Routing Contestuale:** Associa automaticamente le email in entrata alle pratiche aperte tramite Tag di tracciamento o indirizzo mittente.

### 🔗 Dossier & Magic Links (Proactive Collection)

Non aspettare che i documenti arrivino. Chiedili.

- Genera **Dossier di richiesta** con scadenze predefinite.
- Invia **Magic Links** sicuri che permettono ai clienti di caricare documenti in un portale dedicato senza registrazione.
- Gestione delle **Eccezioni Conversazionali**: se il cliente risponde via email ponendo una domanda invece di allegare un file, il sistema lo segnala immediatamente all'operatore.

### 🛡️ Compliance Gate API

Un'interfaccia M2M (Machine-to-Machine) che permette al tuo BPM di interrogare UnicoDoc in tempo reale:

- _"Il Cliente X ha un documento d'identità valido e non scaduto per procedere?"_
- Risposta granulare con motivazioni in caso di non conformità.

---

## 🛠 Tech Stack

- **Core:** Laravel 13 (PHP 8.4+)
- **Admin Panel:** Filament 5.4 (TALL Stack: Tailwind, Alpine.js, Laravel, Livewire)
- **Media Layer:** Spatie Media Library v11 (con supporto S3/SharePoint)
- **Database:** MySQL (Architettura a Database Condiviso con BPM)
- **AI Integration:** OpenAI / Anthropic / AWS Textract

---

## 📂 Architettura dei Dati

Il sistema ruota attorno a un modello di **Proprietà Polimorfica**, permettendo di collegare documenti a qualsiasi entità del business:

- **Aziende & Dipendenti** (HR/Certificazioni)
- **Clienti B2C & B2B** (KYC/Contratti)
- **Agenti & Mandanti** (Procure/Mandati)
- **Organismi di Vigilanza** (Audit/Ispezioni)

---

## 🔧 Installazione & Vibe Coding

UnicoDoc è ottimizzato per lo sviluppo rapido tramite **AI Agents** (Cursor, Cline, GitHub Copilot).

1. **Clona il repository:**
    ```bash
    git clone https://github.com/your-org/unicodoc.git
    ```
2. **Configura le connessioni DB:**
   Assicurati di mappare sia `mysql` (locale) che `mysql_bpm` (per le anagrafiche esterne) nel file `.env`.
3. **Avvia il Sync:**
    ```bash
    php artisan mail:sync # Per l'ingestione IMAP
    php artisan queue:work # Per la classificazione AI
    ```

---

## 📑 Integrazione BPM (Esempio API)

**Richiesta Compliance:**
`POST /api/v1/compliance/check`

```json
{
    "documentable_type": "client",
    "documentable_id": 99,
    "required_codes": ["KYC_ID", "TAX_CONFIRMATION"]
}
```

**Risposta:**

```json
{
    "is_compliant": false,
    "missing_documents": ["TAX_CONFIRMATION"],
    "invalid_documents": {
        "KYC_ID": "Documento scaduto il 01/01/2026"
    }
}
```

---

## ⚖️ Compliance & Sicurezza

- **Audit Log:** Ogni azione (umana o AI) è tracciata.
- **GDPR Ready:** Gestione nativa delle scadenze e della cancellazione sicura.
- **OOB Security:** PIN di accesso ai fascicoli inviati Out-of-Band (SMS/Email separate).

---

_UnicoDoc — Document Management, Reinvented._
