# Changelog Arletta Gym

**Periode:** Mulai 3 Juni 2026
**Repository:** `arletta-gym` (DGDev-ID/arletta-gym)

Berikut adalah rincian pembaruan sistem yang mencakup penambahan fitur baru (Add-ons) dan pemeliharaan sistem (Maintenance / Bug Fixes) untuk aplikasi **Frontend** dan **Backend** Arletta Gym.

---

## 📱 1. Aplikasi Frontend (`arletta-gym` — Vue/Inertia)

### ✨ Fitur Baru

- **Metode Pembayaran Kasir POS** (30 Juni 2026)
  Penambahan pilihan metode pembayaran pada antarmuka kasir POS, meliputi QRIS, Debit, dan Manual/Cash, sehingga kasir dapat mencatat transaksi sesuai metode yang digunakan pelanggan.

- **Filter Tanggal pada History Transaksi** (25 Juni 2026)
  Penambahan komponen *date-range filter* di halaman History Transaction, memungkinkan pengguna memfilter riwayat transaksi berdasarkan rentang tanggal tertentu secara langsung dari antarmuka.

- **Cetak Nota / Invoice POS (PDF)** (13–14 Juli 2026)
  Penambahan tombol aksi cetak nota dan unduh PDF invoice pada menu kasir POS dan halaman History Transaction. Invoice menampilkan detail transaksi lengkap dengan format yang rapi dan terstruktur.

- **Tampilan Detail Jadwal Kelas** (3 Juni 2026)
  Penambahan halaman *Show* detail untuk jadwal kelas (`ClassSchedule/Show.vue`), menampilkan informasi lengkap sesi kelas termasuk data booking dan informasi pelatih.

### 🔧 Maintenance & Penyelesaian Bug

- **Perbaikan Export CSV History Transaksi** (25 Juni 2026)
  Penyelesaian *bug* pada fitur ekspor CSV di halaman History Transaction; meliputi perbaikan tipe data numerik agar kompatibel dengan kalkulasi Excel, serta sinkronisasi filter tanggal antara tampilan web dan hasil ekspor.

- **Penambahan Kolom Tanggal pada Transaction Per Session** (25 Juni 2026)
  Perbaikan tampilan data *Transaction Per Session* dengan menambahkan kolom tanggal (`created_at`) agar riwayat sesi lebih informatif dan mudah dilacak.

- **Perbaikan Invoice PDF Kasir POS** (13–14 Juli 2026)
  Penyempurnaan template Blade invoice PDF kasir (`pdf/pos-invoice.blade.php`) dan perbaikan *bug* 500 saat proses cetak, termasuk refaktor controller `TransactionPosController` dan `HistoryTransactionController` untuk mendukung generate PDF menggunakan DomPDF.

---

## 💻 2. Aplikasi Backend / Dashboard (`arletta-gym` — Laravel)

### ✨ Fitur Baru

- **Dashboard Analitik Metode Pembayaran (Nominal)** (30 Juni 2026)
  Pembaruan bagian *"Metode Pembayaran (Hari Ini)"* pada Dashboard untuk menampilkan total **nominal** transaksi per metode pembayaran (QRIS, Debit, Manual/Cash), bukan sekadar jumlah record transaksi.

- **Metode Pembayaran Baru: Debit** (30 Juni 2026)
  Penambahan kolom `payment_method` pada tabel `transaction_product_outs` melalui migrasi database baru, mendukung pencatatan dan pembedaan transaksi berdasarkan metode pembayaran.

- **Manajemen Pengguna (User Management)** (30 Juni 2026)
  Penambahan `ManageUserController` dengan fungsionalitas lengkap untuk pengelolaan data pengguna dari sisi admin/dashboard.

- **Top Purchased Products di Dashboard** (30 Juni 2026)
  Penambahan kartu ringkasan *"Top Produk Terlaris"* pada Dashboard yang menampilkan produk dengan volume penjualan tertinggi dari sistem POS.

- **Hapus Log Stok pada Edit Produk** (30 Juni 2026)
  Penambahan aksi *delete* untuk setiap entri log stok individual pada halaman *Edit Master Product*, meningkatkan kontrol pengelolaan data stok.

- **Generate Invoice PDF Transaksi** (13 Juli 2026)
  Penambahan endpoint generate PDF invoice untuk riwayat transaksi (`HistoryTransactionController`), mengintegrasikan library DomPDF (`barryvdh/laravel-dompdf`) ke dalam aplikasi dengan template invoice terstruktur (`resources/views/invoices/transaction.blade.php`).

- **Generate & Cetak Nota PDF Kasir POS** (13–14 Juli 2026)
  Penambahan endpoint generate PDF nota kasir (`TransactionPosController`) beserta template Blade khusus POS (`resources/views/pdf/pos-invoice.blade.php`), mendukung cetak nota langsung dari antarmuka kasir.

- **Jadwal Kelas Berulang (Recurring Class Schedule)** (3 Juni 2026)
  Penambahan fitur jadwal kelas berulang (*recurring*) pada `MasterClassScheduleController`, lengkap dengan kolom `is_recurring` dan `trainer_name` di tabel `class_schedules`, serta Artisan Command `ResetRecurringScheduleSlots` untuk me-reset slot jadwal berulang secara otomatis via scheduler.

- **Dukungan Tamu (Guest) pada Booking** (3 Juni 2026)
  Penambahan kolom `guest` pada tabel `bookings` dengan menjadikan kolom `user_id` nullable, memungkinkan pencatatan booking untuk tamu tanpa akun terdaftar.

### 🔧 Maintenance, Penyelesaian Bug & Optimasi

- **Perbaikan Filter Dashboard & Analitik** (22 Juni 2026)
  Refaktor `DashboardController` untuk mendukung sistem filter dinamis berbasis *date-range query parameter* (hari, minggu, bulan, tahun), menggantikan preset statis sebelumnya.

- **Perbaikan Export CSV & Filter Tanggal History Transaksi** (25 Juni 2026)
  Perbaikan `HistoryTransactionController` untuk memastikan filter tanggal diterapkan konsisten pada tampilan web dan hasil ekspor CSV, serta penyesuaian tipe data casting agar nilai numerik kompatibel dengan operasi kalkulasi di Excel.

- **Perbaikan Bug Cetak Nota POS (Error 500)** (13–14 Juli 2026)
  Penyelesaian *Internal Server Error* (HTTP 500) yang terjadi saat proses cetak nota dari menu kasir POS; perbaikan dilakukan pada logika controller `TransactionPosController` dan `HistoryTransactionController`, termasuk perbaikan template `pos-invoice.blade.php`.

- **Perbaikan Jadwal Kelas (Class Schedule)** (3 Juni 2026)
  Perbaikan menyeluruh pada `MasterClassScheduleController` yang mencakup logika validasi, pengelolaan data pelatih, dan manajemen slot jadwal kelas; serta penambahan route dan console command terkait.

---

## 📦 Dependensi Baru

| Paket | Versi | Keterangan | Tanggal |
|---|---|---|---|
| `barryvdh/laravel-dompdf` | `^3.1` | Generate PDF invoice & nota kasir | 13 Juli 2026 |

---

## 🗃️ Perubahan Database (Migrasi)

| Tanggal | Migrasi | Keterangan |
|---|---|---|
| 3 Juni 2026 | `add_recurring_and_trainer_name_to_class_schedules` | Kolom `is_recurring` & `trainer_name` pada jadwal kelas |
| 3 Juni 2026 | `add_guest_to_bookings_make_user_nullable` | Kolom `guest` & nullable `user_id` pada booking |
| 30 Juni 2026 | `add_payment_method_to_transaction_product_outs` | Kolom `payment_method` pada transaksi POS |

---

*Changelog ini dibuat berdasarkan rekap git commit history pada repository `DGDev-ID/arletta-gym`.*
*Terakhir diperbarui: 17 Juli 2026*
