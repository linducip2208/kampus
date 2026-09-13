# Database conventions

- Primary key domain: ULID string.
- FK: `foreignUlid()->constrained()` with `restrictOnDelete`, `cascadeOnDelete`, atau `nullOnDelete` sesuai lifecycle.
- Master data memakai `SoftDeletes`; transaksi financial dan audit tidak memakai soft delete/delete action.
- Uang memakai `decimal(15,2)`, tidak memakai float.
- Query dashboard memakai aggregate dan eager loading.
- KRS, payment allocation, publish grade, dan future conversion wajib memakai `DB::transaction()`.
- Setiap transaksi akademik menyimpan semester atau relasi menuju semester.

Migration core: `database/migrations/2026_09_13_000001_create_campus_erp_core_tables.php`.
