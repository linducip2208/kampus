# Tabler Design System

## Arah visual

Campus ERP memakai Tabler sebagai satu-satunya target UI produksi. Karakternya tenang, padat, akademik, dan mudah dipindai: navy sebagai fondasi navigasi, azure untuk aksi utama, teal untuk status positif, serta warna semantik Tabler untuk warning dan error. Branding institusi dibaca dari database melalui `UniversityBrandingService`.

## Asset pipeline

- `@tabler/core` dan `@tabler/icons-webfont` di-install melalui npm dan dibundel Vite.
- Tidak ada CDN produksi untuk Tabler.
- Entry point berada di `resources/css/app.css` dan `resources/js/app.js`.
- Preferensi tema mendukung `light`, `dark`, dan `system` melalui `data-bs-theme`.

## Layout

Layout target berada di `resources/views/layouts/tabler`:

- `public.blade.php`
- `auth.blade.php`
- `admin.blade.php`
- `student.blade.php`
- `lecturer.blade.php`
- `admission.blade.php`
- `alumni.blade.php`
- `docs.blade.php`

Filament dipertahankan sementara hanya selama custom admin Tabler belum mencapai feature parity.

## Komponen

Komponen reusable berada di `resources/views/components/tabler`. Gunakan komponen ini untuk alert, badge, breadcrumb, button, card, confirmation, table, filters, fields, modal/offcanvas, pagination, status, tabs, timeline, progress, search, notification, avatar, empty state, dan quick actions.

## Aturan UX

- Semua action menggunakan route atau handler nyata; tidak boleh `href="#"`.
- Aksi irreversible atau sensitif memakai confirmation modal.
- Tabel besar wajib server-side search/filter/sort/pagination dan dibungkus `table-responsive`.
- Form menampilkan label, required marker, help, validation, loading, success, dan failure state.
- Sidebar mobile menjadi drawer dengan backdrop; target sentuh minimal 44px.
- UI harus berfungsi pada 375, 414, 768, dan 1440 piksel serta menghormati `prefers-reduced-motion`.
- Arabic/RTL harus memakai logical spacing dan tidak bergantung pada arah kiri/kanan untuk makna.

## Status migrasi

Fondasi paket, tema, branding, auth, student shell, lecturer shell, dan komponen telah tersedia. Konten halaman portal masih bermigrasi dari utility class lama dan admin produksi masih Filament. Karena itu status Tabler belum boleh disebut selesai.

