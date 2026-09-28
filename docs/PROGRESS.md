# BuktiTagih AI — Progress Log & Roadmap Sprint

## Tentang Dokumen Ini

Ini bukan dokumen spesifikasi (itu tugas `BuktiTagih_AI.docx`, master reference document). Dokumen ini adalah **catatan eksekusi**: apa yang sudah dikerjakan dan diverifikasi, apa yang sedang berjalan, dan apa yang masih harus dikerjakan sampai submission.

Tiga status yang dipakai:
- **[SELESAI]** — sudah dikerjakan dan hasilnya sudah diverifikasi (ada bukti/screenshot/test)
- **[SEDANG BERJALAN]** — sudah dikerjakan sebagian atau belum ada konfirmasi hasil
- **[BELUM DIMULAI]** — direncanakan, belum dikerjakan

File ini di-update setiap ada progres baru dan di-commit ke `docs/PROGRESS.md` supaya kedua anggota tim melihat versi yang sama.

---

## Ringkasan Proyek

| | |
|---|---|
| Nama proyek | BuktiTagih AI |
| Event | IBM SkillsBuild University Education National Hackathon 2026 |
| Track | Track 1 (Langflow) |
| Tim | 2 orang: Person A (AI Workflow Engineer), Person B (Product Engineer) |
| Deadline Submission Stage 1 | 4 Oktober 2026, 23:00 WIB |
| Repo | github.com/yuzhuruuu/BuktiTagih-AI |
| Lingkungan dev | IBM Bob (fork VSCode), Langflow lokal di `127.0.0.1:7860` |
| Tools wajib hackathon | Langflow + IBM Bob (bukan watsonx/Granite) |
| LLM | Gemini API (`gemini-2.0-flash`) |
| Model di flow saat commit terakhir | gemini-2.0-flash (konfirmasi Person A) |
| Backend (keputusan Person B) | Laravel + MySQL, frontend HTML |

**Ide dasar produk:** AI evidence assistant untuk korban penagihan pinjaman daring (pinjol) bermasalah. User upload screenshot/chat/call log, sistem mengekstrak dan mengklasifikasi bukti pelanggaran, (nanti) di-ground ke regulasi lewat RAG, lalu hasilnya menjadi laporan PDF untuk pengaduan ke OJK atau pihak berwajib.

---

## Koreksi Penting: Granite diganti Gemini

Master reference document awalnya mengasumsikan **IBM Granite** sebagai reasoning engine. Setelah dicek ke materi resmi (slide Sesi 1–3 dan panduan hackathon):
- Tools wajib project hanya **Langflow dan IBM Bob**. Tidak ada kewajiban memakai watsonx/Granite.
- LLM yang diajarkan di Track 1 adalah **Gemini API Key** (Google AI Studio).
- Kriteria penilaian Track 1 tidak mewajibkan model tertentu.

**Keputusan final:** pakai Gemini, Granite di-drop dari implementasi. Setup watsonx.ai sempat dicoba tetapi model Granite instruct/chat tidak tersedia di plan akun (Lite/trial), dan memang tidak perlu diselesaikan.

**Yang perlu diselaraskan:** laporan Person B masih menyebut "IBM Granite" di diagram alur dan rencana Bob. Person B perlu menggantinya menjadi Gemini. *(Komentar di `frontend/pages/bob.html` sudah diperbaiki — 25 Sep 2026)*

---

## Status: SELESAI

### Sprint 1 — Foundation (Hari 1–5)

**Hari 1 — Repository + environment setup** `[SELESAI]`
- Struktur folder repo dibuat sesuai Bagian 13 dokumen master.
- Langflow terpasang dan berjalan lokal di `127.0.0.1:7860`; project baru dibuat (bukan Starter Project).
- IBM Bob terpasang dan dipakai sebagai environment kerja.
- Framework backend dan database sudah diputuskan oleh Person B: **Laravel + MySQL**.

**Hari 2 — Langflow basic workflow** `[SELESAI]`
- Flow `evidence_analysis_flow`: Chat Input → Language Model → Chat Output.
- Diuji dengan satu teks sample, alur end-to-end berjalan.
- JSON di-export dan di-commit ke `langflow/evidence_analysis_flow.json`.

**Hari 3 — Koneksi Granite** `[SELESAI — tidak diperlukan]`
- Dilewati, lihat bagian Koreksi di atas.

**Hari 4 — Simple evidence analysis** `[SELESAI]`
- System Message berisi ekstraksi entity (actor, victim, date_time, phone_number, organization, location, key_event), klasifikasi kategori (NORMAL/HARASSMENT/THREAT/DATA_EXPOSURE/SPAM), dan severity, dalam output JSON.
- Hasil awal 4 test case: 2 dari 4 cocok. Model menyamaratakan HARASSMENT dan DATA_EXPOSURE menjadi THREAT.

**Hari 5 — Demo internal Sprint 1** `[SELESAI]`

### Sprint 2 — Evidence Intelligence

**Hari 6 — Gemini membaca gambar (screenshot)** `[SELESAI di Playground]`
- Komponen input **tidak diganti**. Tetap memakai Chat Input, dengan screenshot dilampirkan lewat ikon klip di Playground. (Komponen File di Langflow untuk membaca dokumen, bukan untuk vision.)
- Screenshot berisi teks berhasil dibaca dan dianalisis, keluaran JSON valid.
- Uji lewat API (upload file lalu run) sedang disiapkan Person A — lihat bagian Sedang Berjalan.

**Hari 7 — Definisi kategori di prompt** `[SELESAI]`
- Ditambahkan definisi dan aturan pembeda antar kategori:
  - ada syarat ("kalau/jika tidak bayar ... kami akan ...") → THREAT
  - penyebaran data dinyatakan tanpa syarat → DATA_EXPOSURE
  - intimidasi tersirat tanpa ancaman eksplisit → HARASSMENT
- Output schema disamakan dengan kebutuhan backend: `category`, `severity`, `reason`, `confidence` (0–100), `regulation_reference` (`[]` sampai RAG jadi), `entities`.
- Hasil dengan redaksi test case dari master doc:

| # | Input | Target | Hasil | Cocok? |
|---|---|---|---|---|
| 1 | "Kalau tidak bayar hari ini, kami sebar data keluarga kamu." (via gambar) | THREAT / HIGH | THREAT / HIGH | ✅ |
| 2 | "Kami tahu alamat rumah kamu. Tunggu saja." | HARASSMENT / HIGH | HARASSMENT / HIGH | ✅ |
| 3 | "Tagihan Anda jatuh tempo besok. Silakan lakukan pembayaran." | NORMAL / LOW | NORMAL / LOW | ✅ |
| 4 | "Kami kirim foto KTP Anda ke grup." | DATA_EXPOSURE / HIGH | DATA_EXPOSURE / HIGH | ✅ |

**Catatan:** redaksi test case di log lama berbeda dari master doc (#2 "hati-hati", #4 ditambah "jika tidak bayar"). Dataset resmi memakai redaksi master doc.

**Integrasi backend Person B (Langflow side)** `[SELESAI — sisi kode]`
- `AiAnalysisService.php` diperbarui: alur dua langkah (upload file → run flow via tweaks Chat Input).
- `EvidenceController.php` diperbarui: hasil Langflow langsung disimpan ke tabel `ai_analysis` setelah upload.
- Fix kalkulasi `confidence_pct` di PDF report (skala 0–100, bukan 0–1).
- Variabel `.env` baru: `LANGFLOW_FLOW_ID`, `LANGFLOW_CHAT_INPUT_ID` (nilai: `ChatInput-aPEX5`).
- Migration baru: kolom `confidence` diubah dari `decimal(5,4)` → `decimal(5,2)`.
- `input_value: 'Analisis bukti terlampir.'` ditambahkan ke body run flow (fix — sebelumnya tidak dikirim).
- `EvidenceController` diperbarui: entities dari AI sekarang disimpan ke tabel `extracted_entity`.
- Tabel `sessions` dibuat via `php artisan session:table && migrate` (fix error `no such table: sessions`).
- **Hasil test Postman:** upload file sukses, `ai_process` berisi `status: error, message: Upload file ke Langflow gagal` — kode sudah sampai ke Langflow, gagal karena `127.0.0.1:7860` Person A tidak reachable dari laptop Person B. Ini expected (lihat Catatan Teknis baris 166).

---

## Status: SEDANG BERJALAN

**Install Langflow lokal di laptop Person B** `[SEDANG BERJALAN]`
- Person B perlu install Langflow sendiri (`pip install langflow && langflow run`), import `langflow/evidence_analysis_flow.json`, dan buat API key baru.
- Setelah itu end-to-end test dari `POST /api/evidence/upload` akan menggunakan Langflow lokal Person B.
- Alternatif: Person A jalankan `ngrok http 7860` dan kirim URL ngrok ke Person B.

**Uji API Langflow — Person A** `[SELESAI]`
- Person A sudah uji endpoint file upload + run via API — berhasil.
- Flow ID dikonfirmasi: `e05721f1-c3a2-4a33-bbd2-d30dee3df995`, Chat Input ID: `ChatInput-aPEX5`.
- Test case 20/20 recall 100% sudah dicapai (Hari 8–9 selesai di sisi Person A).

**Keputusan arsitektur: Opsi A vs Opsi B** `[SELESAI — Opsi B dipilih]`
- **Opsi A (callback):** Langflow memanggil endpoint Laravel setelah selesai.
- **Opsi B (sinkron):** Laravel memanggil Langflow `/run`, langsung terima respons, update DB sendiri.
- **Keputusan: Opsi B** — `/run` Langflow bersifat sinkron, tidak perlu callback. Backend Person B yang update DB. Sudah diimplementasi di `EvidenceController.php`.

**Skala `confidence`** `[SELESAI — dikonfirmasi]`
- Person A memakai skala 0–100. Kolom DB diubah ke `decimal(5,2)` agar bisa menampung nilai tersebut. Tidak ada konversi di service layer.

---

## Status: BELUM DIMULAI (Next Steps)

**End-to-end test setelah Langflow Person B jalan** `[BELUM DIMULAI]`
- Setelah Langflow lokal Person B berjalan (lihat "Sedang Berjalan" di atas):
  - Upload file via `POST /api/evidence/upload` → verifikasi response `ai_process.status === "success"`.
  - Cek tabel `ai_analysis` terisi category/severity/reason/confidence/regulation_reference yang real.
  - Cek tabel `extracted_entity` terisi entities yang diekstrak AI.

**Hari 8–9 — Full testing 20 test case** `[SELESAI — sisi Person A]`
- Person A sudah menyelesaikan 20 test case (20/20 recall 100%). File ada di `tests/evidence_cases/cases.json`.
- **Person B belum verifikasi** hasil ini dari sisi backend (end-to-end belum jalan karena Langflow belum terkoneksi).

**Hari 10 — Cek metrik lain** `[SEBAGIAN — Person A sudah mulai]`
- Kecepatan: rata-rata 20–25 detik/pesan pada free tier Gemini (5 req/menit). Target "100 pesan ≤3 menit" tidak realistis di free tier — akan dilaporkan sebagai limitasi jujur.
- F1 ekstraksi entity dan kebocoran PII: Person A akan cek di Hari 10.

**Perbaikan entity prompt — Person A** `[BELUM DIMULAI]`
- Entity `actor` dan `victim` masih terisi kata ganti ("Kami", "kamu"). Perlu instruksi agar berisi nilai konkret.

**Sprint 3 — RAG / Regulation Intelligence** `[BELUM DIMULAI]`
- Isi `regulation_reference` dari knowledge base regulasi OJK (folder `knowledge_base/`).
- Untuk submission minimal: `[]` sudah cukup. Untuk nilai lebih tinggi: RAG perlu jalan.
- **Pembagian Sprint 3:** Person A mengintegrasikan RAG ke flow Langflow. Person B tidak perlu mengubah backend — kolom `regulation_reference` sudah ada dan siap terima string JSON.

**Sprint 4–5 — Bob assistant, polish, submission** `[BELUM DIRENCANAKAN DETAIL]`

---

## Catatan Teknis

- **Flow ID & Component ID (dari `langflow/evidence_analysis_flow.json`):**
  - Flow ID: `e05721f1-c3a2-4a33-bbd2-d30dee3df995`
  - Chat Input Component ID: `ChatInput-aPEX5`
  - Isi di `backend/.env`: `LANGFLOW_FLOW_ID` dan `LANGFLOW_CHAT_INPUT_ID`
- **Kuota Gemini gratis:** batas 5 permintaan per menit. Person A dan Person B **wajib pakai API key dari Google project yang berbeda** agar tidak rebutan kuota saat testing bareng. Kuota dibagi per project Google, bukan per API key — semua key dalam satu project berbagi kuota yang sama.
- **API key:** jangan commit key ke repo. Sebelum commit export flow, cek: `Select-String -Path langflow\*.json -Pattern "AIza"` (PowerShell). Jika ada hasil, revoke key dan buat baru.
- **Langflow lokal:** `127.0.0.1:7860` di laptop Person A tidak bisa dijangkau backend di laptop Person B. Person B mengimpor `langflow/evidence_analysis_flow.json` dan menjalankan Langflow sendiri untuk development. Untuk demo final, semua berjalan di satu mesin.
- **Skala confidence:** 0–100 (dikonfirmasi Person A). Kolom DB `decimal(5,2)` sudah benar. Tidak ada konversi di service layer.
- **Entities:** disimpan di tabel `extracted_entity` (bukan kolom di `ai_analysis`). Setiap entity = 1 baris dengan `entity_type`, `entity_value`, `confidence`.

---

## Keputusan Terbuka

1. ~~Framework backend~~ → **Laravel** (Person B).
2. ~~Tipe database~~ → **MySQL** (Person B).
3. ~~Arsitektur integrasi Langflow~~ → **Opsi B: sinkron, Person B update DB** (disepakati 25 Sep 2026).
4. ~~Skala `confidence`~~ → **0–100** sesuai output Langflow. Kolom DB sudah disesuaikan.
5. **Target metrik MVP** (F1 entity ≥0,90, recall kategori ≥0,90, PII bocor nol, 100 pesan ≤3 menit): perlu dikonfirmasi tim sebagai acuan testing Hari 9–10.

---

## Revisi Dokumen Master yang Masih Menggantung

- Ganti semua sebutan "IBM Granite reasoning" menjadi Gemini di Bagian 1, 3, 4.2, dan 7.
- Reframe slide "IBM Technology Advantage" (Bagian 11): nilai jualnya adalah integrasi Langflow + IBM Bob, bukan Granite.
- Samakan nama field output: master memakai `evidence`, DB dan backend memakai `reason`.

---

*Terakhir diupdate: 28 September 2026 — sinkronisasi update Person A (Hari 8–9 selesai: 20/20 test case, recall 100%) + fix backend: `input_value`, entities ke `extracted_entity`, bug `sessions` table. Bottleneck sekarang: Langflow lokal Person B belum terinstall, end-to-end belum ditest.*
