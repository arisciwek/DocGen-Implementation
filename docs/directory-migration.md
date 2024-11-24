Directory Migration Schema

Skenario yang mungkin terjadi saat admin mengganti folder:

1. Folder lama:
   - Masih berisi file template dan temporary files
   - File-file ini menjadi "orphaned" (terlantar)
   - Memakan space storage
   - Berpotensi jadi sampah data

2. Folder baru:
   - Kosong/belum ada file template
   - Module tidak bisa berjalan karena template hilang
   - Perlu migrasi file dari folder lama

Solusi yang bisa ditawarkan:

1. Konfirmasi saat save settings:
   "Perhatian: Mengganti folder akan membuat file di folder lama tidak bisa diakses. Apakah Anda ingin:
   - Pindahkan semua file ke folder baru (Migrate)
   - Biarkan file di folder lama (Keep)
   - Hapus file di folder lama (Delete)"

2. Tambah fitur migrasi:
   - Tombol "Migrate Files" di settings
   - Pindahkan semua file dari folder lama ke folder baru
   - Maintain struktur folder dan permission

3. Cleanup tool:
   - Scan "orphaned folders"
   - List file-file yang sudah tidak terpakai
   - Opsi untuk cleanup

===


1. JavaScript (migration-js) simpan di:
```
/wp-content/plugins/docgen-implementation/admin/js/directory-migration.js
```
Tambahkan kode di file yang sudah ada, karena ini masih bagian dari settings handler.

2. CSS (migration-css) simpan di:
```
/wp-content/plugins/docgen-implementation/assets/css/style.css
```
Tambahkan di file CSS yang sudah ada, karena ini style untuk admin area.

3. PHP Class (Directory Migration Handler) buat file baru:
```
/wp-content/plugins/docgen-implementation/admin/class-directory-migration.php
```

Kemudian register classnya di constructor class-admin-page.php:

```php
public function __construct() {
    require_once DOCGEN_IMPLEMENTATION_DIR . 'admin/class-directory-migration.php';
    
    $this->dir_handler = DocGen_Implementation_Directory_Structure::get_instance();
    $this->migration_handler = DocGen_Implementation_Directory_Migration::get_instance();
    
    // ... kode lainnya ...
}
```
