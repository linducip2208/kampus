# Module map

Status source code aktual dan persentase kelengkapan berada di [`docs/MODULE_COMPLETION.md`](docs/MODULE_COMPLETION.md). Dokumen tersebut menjadi sumber kebenaran dan membedakan workflow usable dari schema/CRUD saja.

## Core yang sudah usable tetapi masih PARTIAL

- Organization dan scoped RBAC foundation.
- PMB lifecycle serta applicant conversion transactional/idempotent.
- Academic core, KRS validation/finalization, GradeCalculator, KHS/IPS/IPK/transcript baseline.
- Student invoice/payment allocation dengan arithmetic decimal.
- Student leave, status history, reactivation.
- LMS content/assignment baseline dan quiz attempt engine.
- Student/lecturer portal baseline, API v1 read endpoints, audit dan approval engine.

## Migrasi UI

Target UI produksi adalah 100% Tabler. Auth dan shared shells/components sudah memakai Tabler. Konten portal masih bermigrasi, sedangkan Filament tetap dipertahankan sementara sampai custom Tabler admin memiliki feature parity.

## Domain MISSING utama

Scholarship, MBKM, internship, KKN, thesis, yudisium, graduation, alumni/tracer/career, library, research/community service, student affairs, HRM/BKD lengkap, assets, procurement, accounting, documents/letters, PDDikti/integration center, backup, dan system health.

Setiap modul hanya boleh naik menjadi `COMPLETE` setelah database, model/relations, service/action, authorization, workflow, UI yang relevan, audit, tests, dan dokumentasi tersedia.