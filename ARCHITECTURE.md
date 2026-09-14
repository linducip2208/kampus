# Campus ERP — Architecture

Campus ERP adalah modular monolith Laravel 13 untuk universitas multi-kampus. Domain inti dipisahkan antara identity, organization, academic, student, finance, approval, audit, notification, dan integration.

## Batas modul

- `Organization`: university, campus, faculty, department, study program.
- `Academic`: academic year, semester, course, curriculum, offering, class, schedule, KRS, attendance, grade.
- `Student`: profile, enrollment, status history, portal.
- `Finance`: fee, invoice, payment, payment allocation.
- `Control plane`: roles, scope, approval engine, audit log, settings.
- `Experience`: Tabler public/auth/student/lecturer shells, temporary Filament admin during parity migration, REST API v1.

Setiap domain penting memakai ULID dan FK eksplisit. Data identitas mahasiswa dipisahkan dari enrollment sehingga riwayat program studi tidak menggandakan orang.

## Stack

Laravel 13, PHP 8.3 local / 8.4 production, MySQL 8.4 primary, Redis/queue-ready, Tabler 1.5, Livewire 4, Blade, dan temporary Filament 5/Tailwind during migration. SQLite hanya digunakan untuk test/demo lokal saat MySQL tidak tersedia.

## Request flow

`HTTP → Form Request/Controller → Service/Action → transaction + event → audit/notification/queue`.

## Security

RBAC disimpan pada `roles` dan `role_user` dengan scope sampai study program. UI navigation bukan boundary keamanan; policy/service wajib menegakkan scope sebelum query mutasi.

## Status

Implementasi aktual bersifat bertahap. PMB/KRS/academic record/student finance/leave/assignment/quiz sudah memiliki core service tetapi belum seluruhnya end-to-end. Modul lanjutan masih banyak yang MISSING. Sumber kebenaran status ada di docs/MODULE_COMPLETION.md dan TODO_INTERNAL.md.