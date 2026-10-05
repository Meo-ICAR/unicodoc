# Graph Report - unicodoc  (2026-10-05)

## Corpus Check
- 176 files · ~105,958 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 32 file(s) not represented in the graph (top: (none) 16, .woff2 7, .css 3)

## Summary
- 6476 nodes · 19621 edges · 188 communities (134 shown, 54 thin omitted)
- Extraction: 91% EXTRACTED · 9% INFERRED · 0% AMBIGUOUS · INFERRED: 1817 edges (avg confidence: 0.86)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- code-editor.js
- rich-editor.js
- components/chart.js
- resolve
- stat/chart.js
- e
- update
- Document
- markdown-editor.js
- _update
- facet
- DocumentRequest
- Illuminate\Database\Eloquent\Relations\BelongsTo
- n
- draw
- Tasks
- n
- updateElements
- Illuminate\Database\Eloquent\Model
- constructor
- create
- support.js
- buildTicks
- .slice
- o
- get
- MailMessage
- t
- e
- x
- columns/select.js
- slice
- fn
- Cn
- DocumentType
- i
- advance
- _update
- T
- draw
- AdminPanelProvider.php
- reduce
- te
- tables.js
- Nt
- O
- ee
- r
- parse
- nt
- getDatasetMeta
- eq
- renderOptions
- constructor
- configure
- constructor
- RequestRegistryAttachment
- updateElements
- AuditExport
- add
- DocumentStatus
- echo.js
- nt
- qt
- parse
- Up
- DocumentsTable.php
- components/select.js
- getDatasetMeta
- og
- eq
- slider.js
- vd
- Principal
- ir
- ComplianceController.php
- filament/app.js
- buildTicks
- X0
- aw
- _a
- notifications.js
- RequestRegistryAction
- file-upload.js
- W
- package.json
- fn
- DocumentTypeResource.php
- CreateDocument.php
- RegulatoryBody
- sliceDoc
- RequestEmailLog
- fromObject
- zc
- DocumentResource
- define
- da
- RequestRegistryProcess
- closeDropdown
- selectRecords
- Media
- t
- ut
- color-picker.js
- fu
- Illuminate\Database\Seeder
- selectOption
- renderOptions
- status
- Illuminate\Database\Schema\Blueprint
- 📄 UnicoDoc — L'Intelligenza Artificiale per la Gestione Documentale e la Compliance
- date-time-picker.js
- getContext
- 📄 UnicoDoc — AI-Powered Document Compliance & Orchestration
- $t
- Mt
- Fe
- oe
- textBetween
- composer.json
- require
- scripts
- Il
- yl
- ot
- Wd
- schemas.js
- ListDocuments
- require-dev
- Illuminate\Database\Migrations\Migration
- view
- dispatch
- Filament\Schemas\Schema
- config
- o
- CreateDocument
- actions/actions.js
- St
- Pt
- components/actions.js
- AppServiceProvider
- psr-4
- Illuminate\Support\Facades\Schema
- clickPercent
- init
- 2026_04_07_081106_create_documents_table.php
- 2026_04_11_075622_create_document_status_table.php
- 2026_04_11_122253_create_mail_accounts_table.php
- 2026_04_11_122253_create_mail_messages_table.php
- 2026_04_11_122254_create_mail_attachments_table.php
- 2026_04_11_173227_create_personal_access_tokens_table.php
- 2026_04_11_185500_create_classification_logs_table.php
- 2026_04_11_190000_create_document_requests_table.php
- 2026_04_11_190100_create_document_request_items_table.php
- 2026_04_13_060051_recreate_request_registries_table.php
- 2026_04_13_060053_recreate_request_registry_actions_table.php
- 2026_04_13_060056_create_request_registry_attachments_table.php
- 2026_04_13_060101_create_request_registry_processes_table.php
- 2026_04_13_060103_create_request_email_logs_table.php
- 2026_04_13_060105_create_audit_exports_table.php
- ExampleTest
- c
- autoload-dev
- extra
- pt
- K
- toJSON

## God Nodes (most connected - your core abstractions)
1. `update()` - 140 edges
2. `constructor()` - 137 edges
3. `Document` - 102 edges
4. `DocumentType` - 97 edges
5. `resolve()` - 95 edges
6. `x()` - 87 edges
7. `_update()` - 86 edges
8. `_update()` - 86 edges
9. `node()` - 77 edges
10. `te()` - 76 edges

## Surprising Connections (you probably didn't know these)
- `Enum di dominio testati` --references--> `SyncStatus`  [INFERRED]
  .kiro/specs/unicodoc-test-suite/design.md → app/Enums/SyncStatus.php
- `Property 5: canAutoVerify correctness` --references--> `DocumentType`  [INFERRED]
  .kiro/specs/unicodoc-test-suite/design.md → app/Models/DocumentType.php
- `Property 8: isExpired correctness for DocumentType` --references--> `DocumentType`  [INFERRED]
  .kiro/specs/unicodoc-test-suite/design.md → app/Models/DocumentType.php
- `TestCase Base (`tests/TestCase.php`)` --references--> `DocumentType`  [INFERRED]
  .kiro/specs/unicodoc-test-suite/design.md → app/Models/DocumentType.php
- `Notes` --references--> `MailAttachmentProcessor`  [INFERRED]
  .kiro/specs/unicodoc-test-suite/tasks.md → app/Services/Mail/MailAttachmentProcessor.php

## Import Cycles
- None detected.

## Communities (188 total, 54 thin omitted)

### Community 0 - "code-editor.js"
Cohesion: 0.01
Nodes (111): ac(), ak(), attrs(), [b.Blockquote](), [b.ListItem](), baseTheme(), bh(), Blockquote() (+103 more)

### Community 1 - "rich-editor.js"
Cohesion: 0.01
Nodes (140): Eo(), addAttributes(), addHackNode(), addNodeMark(), addTextblockHacks(), an(), applyAspectRatio(), applyConstraints() (+132 more)

### Community 2 - "components/chart.js"
Cohesion: 0.01
Nodes (105): readOnly(), Ac(), addControllers(), addPlugins(), addScales(), am(), beforeDraw(), Bh() (+97 more)

### Community 3 - "resolve"
Cohesion: 0.05
Nodes (158): Xf(), $a(), ac(), Ad(), addCommands(), addKeyboardShortcuts(), after(), ak() (+150 more)

### Community 4 - "stat/chart.js"
Cohesion: 0.02
Nodes (113): addControllers(), addPlugins(), addScales(), afterDraw(), an(), as(), beforeDatasetDraw(), beforeDatasetsDraw() (+105 more)

### Community 5 - "e"
Cohesion: 0.03
Nodes (149): kS(), accepts(), addNodeView(), addProseMirrorPlugins(), ag(), al(), bc(), bg() (+141 more)

### Community 6 - "update"
Cohesion: 0.03
Nodes (142): accept(), add(), addChunk(), addEventListener(), addInfoPane(), addInner(), addToSet(), addWindowListeners() (+134 more)

### Community 7 - "Document"
Cohesion: 0.03
Nodes (65): DocumentUploaded, ProcessDocumentAiJob, RunDocumentClassification, DynamicAiMail, Document, AiClassifier, ClassificationOrchestratorService, ClassificationResult (+57 more)

### Community 8 - "markdown-editor.js"
Cohesion: 0.04
Nodes (103): ad(), af(), ai(), An(), ao(), Ba(), bf(), bo() (+95 more)

### Community 9 - "_update"
Cohesion: 0.04
Nodes (108): Aa(), add(), addBox(), afterBuildTicks(), afterCalculateLabelRotation(), afterDataLimits(), afterFit(), afterSetDimensions() (+100 more)

### Community 10 - "facet"
Cohesion: 0.03
Nodes (105): active(), addElement(), applyChanges(), balanced(), baseIndent(), baseIndentFor(), between(), blockAt() (+97 more)

### Community 11 - "DocumentRequest"
Cohesion: 0.03
Nodes (57): DocumentClassified, FulfillDocumentRequest, DocumentRequest, DocumentRequestItem, {closure#5}(), Correctness Properties, Mappa stati DossierStatus → metodi DocumentRequest, Property 10: Document.isNearExpiry correctness (+49 more)

### Community 12 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.03
Nodes (8): Agent, CompanyUser, Employee, ClassificationLog, Client, {closure#1}(), Ownership Model (Polymorphic Entities), Property 21: Orchestrator always creates ClassificationLog on success

### Community 13 - "n"
Cohesion: 0.04
Nodes (96): aa(), ah(), AP(), atLastNode(), c$(), child(), childAfter(), childBefore() (+88 more)

### Community 14 - "draw"
Cohesion: 0.04
Nodes (93): acquireContext(), adjustHitBoxes(), afterDraw(), bs(), bt(), calculateLabelRotation(), _calculatePadding(), clear() (+85 more)

### Community 15 - "Tasks"
Cohesion: 0.03
Nodes (31): DocumentFactory, DocumentRequestFactory, DocumentRequestItemFactory, DocumentTypeFactory, MailAccountFactory, MailAttachmentFactory, MailMessageFactory, UserFactory (+23 more)

### Community 16 - "n"
Cohesion: 0.07
Nodes (87): _a(), Ac(), Ae(), ar(), bc(), bl(), ee(), ue() (+79 more)

### Community 17 - "updateElements"
Cohesion: 0.04
Nodes (88): afterAutoSkip(), applyStack(), Ar(), aspectRatio(), At(), bi(), buildLookupTable(), _calculateBarIndexPixels() (+80 more)

### Community 18 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.04
Nodes (7): Company, Complaint, DocumentScope, DocumentStatus, MailAccount, RequestRegistry, SmartCommunicationService

### Community 19 - "constructor"
Cohesion: 0.04
Nodes (88): add(), addExtensions(), applyInitialSize(), bt(), Cn(), configure(), connectSelection(), constructor() (+80 more)

### Community 20 - "create"
Cohesion: 0.04
Nodes (87): addAll(), addDOM(), addElement(), addElementByRule(), addInputRules(), addMark(), addStoredMark(), addTextNode() (+79 more)

### Community 21 - "support.js"
Cohesion: 0.06
Nodes (68): aa(), ae(), bo(), br(), T(), Bt(), ca(), close() (+60 more)

### Community 22 - "buildTicks"
Cohesion: 0.04
Nodes (83): abutsStart(), after(), as(), before(), buildTicks(), Cm(), contains(), count() (+75 more)

### Community 23 - ".slice"
Cohesion: 0.04
Nodes (78): a0(), addInner(), addMaps(), addOptions(), addStep(), addTransform(), appendMap(), appendMapping() (+70 more)

### Community 24 - "o"
Cohesion: 0.04
Nodes (78): ai(), bg(), bl(), o(), cg(), clone(), co(), create() (+70 more)

### Community 25 - "get"
Cohesion: 0.05
Nodes (76): addBlockWidget(), addBreak(), addComposition(), addDelimiter(), addInlineWidget(), addLine(), addLineStart(), addLineStartIfNotCovered() (+68 more)

### Community 26 - "MailMessage"
Cohesion: 0.05
Nodes (27): MailAttachment, MailMessage, EmailSyncService, MailAttachmentProcessor, MailMessageProcessor, MailSyncService, {closure#3}(), Property 23: MailAttachmentProcessor valid attachment creates Document (+19 more)

### Community 27 - "t"
Cohesion: 0.04
Nodes (69): aS(), b1(), bidiSpans(), checkHover(), combine(), configure(), coordsAt(), coordsAtPos() (+61 more)

### Community 28 - "e"
Cohesion: 0.05
Nodes (68): aa(), ac(), addEventListener(), al(), Ce(), cs(), dataset(), l() (+60 more)

### Community 29 - "x"
Cohesion: 0.14
Nodes (65): al(), as(), at(), Be(), cd(), Cr(), Ct(), de() (+57 more)

### Community 30 - "columns/select.js"
Cohesion: 0.07
Nodes (55): Ae(), applyDisabledState(), b(), be(), bi(), Bt(), Ce(), Cn() (+47 more)

### Community 31 - "slice"
Cohesion: 0.05
Nodes (64): ad(), addActive(), applyTransaction(), Ar(), asSingle(), cd(), chunkEnd(), create() (+56 more)

### Community 32 - "fn"
Cohesion: 0.08
Nodes (61): Ah(), Bh(), Bi(), ch(), ct(), dh(), Dt(), Eh() (+53 more)

### Community 33 - "Cn"
Cohesion: 0.11
Nodes (57): Cn(), b(), Be(), Ce(), De(), dn(), _e(), F() (+49 more)

### Community 34 - "DocumentType"
Cohesion: 0.06
Nodes (27): DocumentType, Acceptance Criteria, Requirement 2: Modello DocumentType — Logiche di Business, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#9}() (+19 more)

### Community 35 - "i"
Cohesion: 0.05
Nodes (58): an(), AQ(), bd(), build(), Cg(), clearDelayedAndroidKey(), compute(), i() (+50 more)

### Community 36 - "advance"
Cohesion: 0.05
Nodes (58): addChild(), addGaps(), addLeafElement(), addNode(), advance(), ATXHeading(), balance(), blockTiles() (+50 more)

### Community 37 - "_update"
Cohesion: 0.05
Nodes (57): Em(), nh(), afterBuildTicks(), afterCalculateLabelRotation(), afterDataLimits(), afterFit(), afterSetDimensions(), afterTickToLabelConversion() (+49 more)

### Community 38 - "T"
Cohesion: 0.05
Nodes (55): Bc(), ae(), bl(), calculateCircumference(), cc(), _circumference(), _computeAngle(), _computeLabelItems() (+47 more)

### Community 39 - "draw"
Cohesion: 0.07
Nodes (55): adjustHitBoxes(), At(), bi(), _calculatePadding(), clear(), _computeLabelArea(), _computeTitleHeight(), De() (+47 more)

### Community 40 - "AdminPanelProvider.php"
Cohesion: 0.05
Nodes (15): User, AdminPanelProvider, BpmIntegrationService, 0. AI Vibe Coding Guidelines & Constraints, 1. Compliance Gate API, 1. Document Metadata, 2. Core Concepts & Domain Expansion, 2. Dossier Creation API (+7 more)

### Community 41 - "reduce"
Cohesion: 0.06
Nodes (53): _0(), addActions(), advanceFully(), advanceStack(), allActions(), AZ(), blank(), canShift() (+45 more)

### Community 42 - "te"
Cohesion: 0.05
Nodes (13): Rd(), Bi(), Bn(), br(), Id(), ji(), on(), qd() (+5 more)

### Community 43 - "tables.js"
Cohesion: 0.13
Nodes (48): ae(), be(), C(), Ce(), D(), De(), E(), ee() (+40 more)

### Community 44 - "Nt"
Cohesion: 0.06
Nodes (48): addChanges(), addSelection(), after(), Ag(), before(), BO(), Cf(), Ch() (+40 more)

### Community 45 - "O"
Cohesion: 0.18
Nodes (40): y(), [x](), Aa(), b(), $c(), X(), ca(), me() (+32 more)

### Community 46 - "ee"
Cohesion: 0.06
Nodes (46): ah(), average(), beforeDatasetsDraw(), bu(), dataset(), ee(), eh(), En() (+38 more)

### Community 47 - "r"
Cohesion: 0.13
Nodes (41): ai(), ar(), q(), c(), d(), Ft(), Do(), f() (+33 more)

### Community 48 - "parse"
Cohesion: 0.07
Nodes (45): ad(), au(), buildOrUpdateScales(), Ca(), cd(), data(), E(), Fa() (+37 more)

### Community 49 - "nt"
Cohesion: 0.13
Nodes (41): ai(), b(), bi(), ci(), di(), Dn(), Dt(), Et() (+33 more)

### Community 50 - "getDatasetMeta"
Cohesion: 0.07
Nodes (41): $a(), addElements(), afterDatasetsUpdate(), an(), buildOrUpdateControllers(), buildOrUpdateElements(), Cn(), _dataCheck() (+33 more)

### Community 51 - "eq"
Cohesion: 0.07
Nodes (40): activeForPoint(), addBlock(), addLineDeco(), at(), be(), blankContent(), boundChange(), commit() (+32 more)

### Community 52 - "renderOptions"
Cohesion: 0.12
Nodes (40): addBadgesForSelectedOptions(), addSingleBadge(), addSingleSelectionDisplay(), closeDropdown(), constructor(), createBadgeElement(), createOptionElement(), createRemoveButton() (+32 more)

### Community 53 - "constructor"
Cohesion: 0.07
Nodes (40): apply(), _cachedScopes(), Cc(), chartOptionScopes(), ci(), constructor(), describe(), dg() (+32 more)

### Community 54 - "configure"
Cohesion: 0.08
Nodes (40): addElements(), bindEvents(), bindResponsiveEvents(), bindUserEvents(), buildOrUpdateControllers(), buildOrUpdateElements(), _checkEventBindings(), configure() (+32 more)

### Community 55 - "constructor"
Cohesion: 0.07
Nodes (38): _a(), alpha(), apply(), ba(), Bt(), ca(), chartOptionScopes(), constructor() (+30 more)

### Community 56 - "RequestRegistryAttachment"
Cohesion: 0.06
Nodes (3): RequestRegistryAttachment, DocumentAiOrchestrator, SharePointService

### Community 57 - "updateElements"
Cohesion: 0.09
Nodes (37): Ao(), applyStack(), _calculateBarIndexPixels(), _calculateBarValuePixels(), _computeGridLineItems(), countVisibleElements(), Ct(), getActiveElements() (+29 more)

### Community 59 - "add"
Cohesion: 0.08
Nodes (35): active(), add(), _animateOptions(), beforeUpdate(), _cachedScopes(), cancel(), ci(), _createAnimations() (+27 more)

### Community 60 - "DocumentStatus"
Cohesion: 0.14
Nodes (21): DocumentStatus, EXPIRED, FAILED, PENDING, REJECTED, REVOKED, UPLOADED, VERIFIED (+13 more)

### Community 61 - "echo.js"
Cohesion: 0.09
Nodes (20): ar(), Be(), cr(), d(), ei(), f(), ii(), le() (+12 more)

### Community 62 - "nt"
Cohesion: 0.17
Nodes (34): ai(), bn(), ci(), ct(), di(), Dt(), Et(), gi() (+26 more)

### Community 63 - "qt"
Cohesion: 0.07
Nodes (34): active(), alpha(), _animateOptions(), bn(), cancel(), _createAnimations(), _createDescriptors(), _descriptors() (+26 more)

### Community 64 - "parse"
Cohesion: 0.11
Nodes (34): buildOrUpdateScales(), ch(), D(), determineDataLimits(), diff(), En(), endOf(), Fn() (+26 more)

### Community 65 - "Up"
Cohesion: 0.09
Nodes (33): ab(), append(), cc(), dc(), defaultType(), Dn(), done(), eat() (+25 more)

### Community 66 - "DocumentsTable.php"
Cohesion: 0.10
Nodes (12): {closure#11}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), {closure#3}() (+4 more)

### Community 67 - "components/select.js"
Cohesion: 0.10
Nodes (22): be(), bn(), Cn(), _e(), ft(), gt(), he(), In() (+14 more)

### Community 68 - "getDatasetMeta"
Cohesion: 0.09
Nodes (32): afterDatasetsUpdate(), fc(), gc(), generateLabels(), getDatasetMeta(), getDataVisibility(), getMaxBorderWidth(), getStyle() (+24 more)

### Community 69 - "og"
Cohesion: 0.08
Nodes (31): acceptToken(), allows(), $d(), eh(), fP(), fromClass(), gv(), Hc() (+23 more)

### Community 70 - "eq"
Cohesion: 0.10
Nodes (30): addNode(), cg(), destroyBetween(), destroyRest(), dg(), eq(), findIndexWithChild(), findNodeMatch() (+22 more)

### Community 71 - "slider.js"
Cohesion: 0.15
Nodes (28): Be(), _e(), Ee(), er(), Fe(), G(), He(), Ie() (+20 more)

### Community 72 - "vd"
Cohesion: 0.07
Nodes (30): af(), Ba(), bd(), beforeLayout(), _d(), du(), first(), Ha() (+22 more)

### Community 74 - "ir"
Cohesion: 0.12
Nodes (27): ar(), Ce(), De(), et(), ir(), Ct(), ee(), Et() (+19 more)

### Community 75 - "ComplianceController.php"
Cohesion: 0.10
Nodes (6): ComplianceController, DocumentRequestController, Controller, {closure#1}(), {closure#2}(), GET /user()

### Community 76 - "filament/app.js"
Cohesion: 0.12
Nodes (22): B(), close(), E(), F(), G(), I(), L(), N() (+14 more)

### Community 77 - "buildTicks"
Cohesion: 0.10
Nodes (27): afterAutoSkip(), Ar(), average(), buildLookupTable(), buildTicks(), cn(), computeTickLimit(), Do() (+19 more)

### Community 78 - "X0"
Cohesion: 0.14
Nodes (26): Pr(), Ei(), Lr(), Ti(), ys(), ce(), df(), Do() (+18 more)

### Community 79 - "aw"
Cohesion: 0.10
Nodes (26): addPasteRules(), aw(), check(), checkAttrs(), Di(), dw(), endIndex(), getObj() (+18 more)

### Community 80 - "_a"
Cohesion: 0.18
Nodes (26): _a(), ct(), Da(), Fi(), ft(), Gi(), gr(), ht() (+18 more)

### Community 81 - "notifications.js"
Cohesion: 0.09
Nodes (3): duration(), persistent(), seconds()

### Community 83 - "file-upload.js"
Cohesion: 0.09
Nodes (7): cm(), dm(), la(), om(), Pl(), Qp(), Tl()

### Community 84 - "W"
Cohesion: 0.12
Nodes (24): At(), B(), cr(), de(), dt(), Ee(), x(), fr() (+16 more)

### Community 85 - "package.json"
Cohesion: 0.09
Nodes (19): devDependencies, axios, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite, private (+11 more)

### Community 86 - "fn"
Cohesion: 0.16
Nodes (23): Ae(), Bt(), Ce(), ct(), De(), ei(), en(), fn() (+15 more)

### Community 87 - "DocumentTypeResource.php"
Cohesion: 0.13
Nodes (3): DocumentsTable, DocumentTypeResource, DocumentTypesTable

### Community 90 - "sliceDoc"
Cohesion: 0.13
Nodes (22): Bg(), charCategorizer(), cs(), di(), f1(), flatten(), getCursor(), getDeco() (+14 more)

### Community 92 - "fromObject"
Cohesion: 0.16
Nodes (20): al(), Ao(), cl(), daysInYear(), fromObject(), Gl(), greyscale(), No() (+12 more)

### Community 93 - "zc"
Cohesion: 0.12
Nodes (20): Bo(), cs(), darken(), desaturate(), Fc(), Hm(), Ho(), jc() (+12 more)

### Community 94 - "DocumentResource"
Cohesion: 0.14
Nodes (3): DocumentResource, EditDocument, EditDocumentType

### Community 95 - "define"
Cohesion: 0.12
Nodes (19): addCompletion(), addCompletions(), addNamespace(), addNamespaceObject(), Ao(), AX(), define(), domEventHandlers() (+11 more)

### Community 96 - "da"
Cohesion: 0.16
Nodes (18): cf(), da(), ef(), fa(), Gr(), Jc(), Kr(), Ln() (+10 more)

### Community 98 - "closeDropdown"
Cohesion: 0.23
Nodes (17): applyDisabledState(), closeDropdown(), constructor(), destroy(), disable(), enable(), focusNextOption(), focusPreviousOption() (+9 more)

### Community 99 - "selectRecords"
Cohesion: 0.21
Nodes (17): areRecordsSelected(), areRecordsToggleable(), canSelectAllRecords(), deselectAllRecords(), deselectRecords(), getRecordsOnPage(), getSelectedRecordsCount(), handleCheckboxClick() (+9 more)

### Community 101 - "t"
Cohesion: 0.14
Nodes (15): b(), di(), e(), g(), Ht(), i(), Ie(), Me() (+7 more)

### Community 102 - "ut"
Cohesion: 0.24
Nodes (16): Ft(), ce(), de(), Dt(), fe(), ft(), kt(), le() (+8 more)

### Community 103 - "color-picker.js"
Cohesion: 0.14
Nodes (3): [g](), style(), update()

### Community 104 - "fu"
Cohesion: 0.16
Nodes (15): addEventListener(), bindEvents(), bindResponsiveEvents(), bindUserEvents(), _checkEventBindings(), cu(), Ea(), fu() (+7 more)

### Community 105 - "Illuminate\Database\Seeder"
Cohesion: 0.22
Nodes (4): DatabaseSeeder, DocumentScopeSeeder, DocumentStatusSeeder, DocumentTypeSeeder

### Community 106 - "selectOption"
Cohesion: 0.28
Nodes (13): addBadgesForSelectedOptions(), addSingleBadge(), addSingleSelectionDisplay(), createBadgeElement(), createRemoveButton(), getLabelForSingleSelection(), getLabelsForMultipleSelection(), getSelectedOptionLabel() (+5 more)

### Community 107 - "renderOptions"
Cohesion: 0.37
Nodes (13): createOptionElement(), deferPositionDropdown(), filterOptions(), handleSearch(), hideLoadingState(), openDropdown(), populateLabelRepositoryFromOptions(), positionDropdown() (+5 more)

### Community 108 - "status"
Cohesion: 0.21
Nodes (12): 3. Data Dictionary (Strict Schema for Migrations), `document_request_items`, `document_requests` (Dossier / Magic Link), `document_types` (Global Dictionary), `documents` (Core Record), Property 13: DocumentRequest.isExpired with past date, Acceptance Criteria, danger() (+4 more)

### Community 109 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.24
Nodes (5): {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}()

### Community 110 - "📄 UnicoDoc — L'Intelligenza Artificiale per la Gestione Documentale e la Compliance"
Cohesion: 0.17
Nodes (11): 📧 1. Acquisizione Automatica dalle Email (Basta copia-incolla), 🤖 2. Lettura e Classificazione tramite Intelligenza Artificiale, 🔗 3. Raccolta Documenti "Senza Attriti" (Magic Link), 🚦 4. Il "Semaforo" della Compliance (Integrazione Totale), 🏢 Casi d'Uso Ideali, 🚀 Come UnicoDoc Rivoluziona il tuo Flusso di Lavoro, 💼 I Vantaggi per la Tua Azienda, 🎯 Il Problema che Risolviamo (+3 more)

### Community 111 - "date-time-picker.js"
Cohesion: 0.29
Nodes (7): d(), e(), i(), m(), r(), s(), t()

### Community 112 - "getContext"
Cohesion: 0.24
Nodes (12): acquireContext(), Dr(), Ee(), getContext(), il(), kl(), oh(), or() (+4 more)

### Community 113 - "📄 UnicoDoc — AI-Powered Document Compliance & Orchestration"
Cohesion: 0.17
Nodes (11): 📂 Architettura dei Dati, 🚀 Caratteristiche Principali, 🤖 Classificazione Intelligente a Due Livelli, 🛡️ Compliance Gate API, ⚖️ Compliance & Sicurezza, 🔗 Dossier & Magic Links (Proactive Collection), 📧 Ingestione Omnicanale & Anti-Noise, 🔧 Installazione & Vibe Coding (+3 more)

### Community 114 - "$t"
Cohesion: 0.35
Nodes (11): A(), E(), at(), be(), Gt(), i(), Jt(), N() (+3 more)

### Community 115 - "Mt"
Cohesion: 0.22
Nodes (11): apply(), as(), ba(), it(), Ka(), Mt(), _o(), rr() (+3 more)

### Community 116 - "Fe"
Cohesion: 0.20
Nodes (10): Ce(), De(), Dt(), Fe(), He(), ir(), Mt(), nr() (+2 more)

### Community 117 - "oe"
Cohesion: 0.29
Nodes (10): am(), be(), je(), oe(), pe(), Rt(), sm(), Ut() (+2 more)

### Community 118 - "textBetween"
Cohesion: 0.24
Nodes (10): deleteNode(), deleteRange(), findDiffEnd(), findDiffStart(), iu(), ly(), onBeforeCreate(), textBetween() (+2 more)

### Community 119 - "composer.json"
Cohesion: 0.22
Nodes (8): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type

### Community 120 - "require"
Cohesion: 0.22
Nodes (9): require, filament/filament, filament/spatie-laravel-media-library-plugin, laravel/framework, laravel/sanctum, laravel/tinker, php, spatie/laravel-medialibrary (+1 more)

### Community 121 - "scripts"
Cohesion: 0.22
Nodes (9): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+1 more)

### Community 122 - "Il"
Cohesion: 0.22
Nodes (9): Ap(), bi(), Fp(), Il(), im(), ll(), ol(), xl() (+1 more)

### Community 123 - "yl"
Cohesion: 0.25
Nodes (9): Bp(), Cp(), Dp(), lm(), Op(), sa(), Ye(), yl() (+1 more)

### Community 124 - "ot"
Cohesion: 0.28
Nodes (9): e(), em(), ha(), Ia(), It(), ot(), Pp(), ra() (+1 more)

### Community 125 - "Wd"
Cohesion: 0.25
Nodes (9): bd(), descAt(), endOfTextblock(), gd(), jc(), sameParent(), Wd(), wg() (+1 more)

### Community 128 - "require-dev"
Cohesion: 0.25
Nodes (8): require-dev, fakerphp/faker, laravel/pail, laravel/pint, mockery/mockery, nunomaduro/collision, pestphp/pest, phpunit/phpunit

### Community 130 - "view"
Cohesion: 0.25
Nodes (8): actions(), button(), constructor(), grouped(), iconButton(), link(), name(), view()

### Community 131 - "dispatch"
Cohesion: 0.25
Nodes (8): dispatch(), dispatchSelf(), dispatchTo(), emit(), emitSelf(), emitTo(), event(), eventData()

### Community 133 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 134 - "o"
Cohesion: 0.33
Nodes (7): c(), it(), l(), o(), b(), toggleFiltersDropdown(), a()

### Community 136 - "actions/actions.js"
Cohesion: 0.73
Nodes (5): closeModal(), generateModalId(), init(), openModal(), syncActionModals()

### Community 137 - "St"
Cohesion: 0.33
Nodes (6): constructor(), define(), _getTestState(), getType(), registerListeners(), St()

### Community 138 - "Pt"
Cohesion: 0.33
Nodes (6): Ae(), Bt(), ne(), Pt(), ue(), jt()

### Community 141 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 144 - "clickPercent"
Cohesion: 0.60
Nodes (5): clickPercent(), getPosition(), mouseUp(), movePlayhead(), timelineClicked()

### Community 145 - "init"
Cohesion: 0.40
Nodes (5): c(), close(), configureAnimations(), configureTransitions(), init()

### Community 162 - "c"
Cohesion: 0.67
Nodes (4): c(), o(), p(), s()

### Community 164 - "autoload-dev"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 165 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 166 - "pt"
Cohesion: 0.67
Nodes (3): H(), ji(), pt()

### Community 167 - "K"
Cohesion: 1.00
Nodes (3): D(), K(), wt()

## Knowledge Gaps
- **106 isolated node(s):** `$schema`, `name`, `type`, `description`, `keywords` (+101 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 1177 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **54 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Tasks` connect `Tasks` to `DocumentType`, `Document`, `DocumentRequest`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `status`, `buildTicks`, `MailMessage`, `DocumentStatus`?**
  _High betweenness centrality (0.254) - this node is a cross-community bridge._
- **Are the 16 inferred relationships involving `update()` (e.g. with `ia()` and `r()`) actually correct?**
  _`update()` has 16 INFERRED edges - model-reasoned connections that need verification._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _106 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `code-editor.js` be split into smaller, more focused modules?**
  _Cohesion score 0.008634906092533211 - nodes in this community are weakly interconnected._
- **Why does `invalid()` connect `buildTicks` to `components/chart.js`, `DocumentRequest`, `fromObject`, `Tasks`?**
  _High betweenness centrality (0.253) - this node is a cross-community bridge._
- **Are the 6 inferred relationships involving `constructor()` (e.g. with `co()` and `pa()`) actually correct?**
  _`constructor()` has 6 INFERRED edges - model-reasoned connections that need verification._
- **Should `rich-editor.js` be split into smaller, more focused modules?**
  _Cohesion score 0.012550954730744475 - nodes in this community are weakly interconnected._