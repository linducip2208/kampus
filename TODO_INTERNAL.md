# Internal progress tracker

## DONE

- Laravel 13 + Filament 5 + Livewire 4 scaffold.
- ULID core schema, FK, indexes, seed demo.
- Organization, academic core, student, finance ledger schema/models.
- Branded landing/login, responsive portal, Filament theme and widgets.
- Docs, README baseline, sitemap/robots, blog/RSS, IndexNow command.
- Normalized permission tables, seeded roles, backend Gate interception, and organization-scope policy checks.
- Configurable KRS validation service, transactional KRS finalization, immutable audit entry, and critical tests.
- KHS/transkrip portal, weighted IPS/IPK rekap service, semester summaries, and route coverage test.
- Real desktop/mobile screenshot capture scripts and branded Filament login/avatar provider.

## IN PROGRESS

- Expand service-layer transactions, policies, and critical tests across remaining domains.
- Replace demo-only backup metadata with configured MySQL/object-storage backup in deployment.

## NEXT

- Sanctum token auth and resource endpoints for API v1.
- Scope-aware query builder for list pages, beyond record-level policy checks.
- PMB lifecycle and transactional applicant conversion.
- KHS/transcript PDF generation and final-document verification.
- Payment allocation UI and verified-payment workflow.

## BLOCKER

- Production MySQL, Redis, object storage, mail, and domain credentials are not available in local environment. Provider values remain environment/admin-configurable.
