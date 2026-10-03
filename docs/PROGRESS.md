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
| LLM | Gemini API (`gemini-3.5-flash`) |
| Model di flow saat commit terakhir | `gemini-3.5-flash` (final, diputuskan 3 Okt 2026) |
| Backend (keputusan Person B) | Laravel + MySQL, frontend HTML |

**Ide dasar produk:** AI evidence assistant untuk korban penagihan pinjaman daring (pinjol) bermasalah. User upload screenshot/chat/call log, sistem mengekstrak dan mengklasifikasi bukti pelanggaran, memberi referensi regulasi yang relevan, lalu hasilnya menjadi laporan PDF untuk pengaduan ke OJK atau pihak berwajib.

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

**Hari 6 — Gemini membaca gambar (screenshot)** `[SELESAI]`
- Komponen input **tidak diganti**. Tetap memakai Chat Input, dengan screenshot dilampirkan lewat ikon klip di Playground. (Komponen File di Langflow untuk membaca dokumen, bukan untuk vision.)
- Screenshot berisi teks berhasil dibaca dan dianalisis, keluaran JSON valid.
- Uji lewat API (upload file lalu run) berhasil — lihat "Uji API Langflow" di bawah.

**Hari 7 — Definisi kategori di prompt** `[SELESAI]`
- Ditambahkan definisi dan aturan pembeda antar kategori:
  - ada syarat ("kalau/jika tidak bayar ... kami akan ...") → THREAT
  - penyebaran data dinyatakan tanpa syarat → DATA_EXPOSURE
  - intimidasi tersirat tanpa ancaman eksplisit → HARASSMENT
- Output schema disamakan dengan kebutuhan backend: `category`, `severity`, `reason`, `confidence` (0–100), `regulation_reference`, `entities`.
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
- **Hasil test Postman:** upload file sukses, `ai_process` berisi `status: error, message: Upload file ke Langflow gagal` — kode sudah sampai ke Langflow, gagal karena `127.0.0.1:7860` Person A tidak reachable dari laptop Person B. Ini expected (lihat Catatan Teknis, bagian "Langflow lokal").

**Uji API Langflow — Person A** `[SELESAI]`
- Person A sudah uji endpoint file upload + run via API — berhasil.
- Flow ID dikonfirmasi: `e05721f1-c3a2-4a33-bbd2-d30dee3df995`, Chat Input ID: `ChatInput-aPEX5`.

**Keputusan arsitektur: Opsi A vs Opsi B** `[SELESAI — Opsi B dipilih]`
- **Opsi A (callback):** Langflow memanggil endpoint Laravel setelah selesai.
- **Opsi B (sinkron):** Laravel memanggil Langflow `/run`, langsung terima respons, update DB sendiri.
- **Keputusan: Opsi B** — `/run` Langflow bersifat sinkron, tidak perlu callback. Backend Person B yang update DB. Sudah diimplementasi di `EvidenceController.php`.

**Skala `confidence`** `[SELESAI — dikonfirmasi]`
- Person A memakai skala 0–100. Kolom DB diubah ke `decimal(5,2)` agar bisa menampung nilai tersebut. Tidak ada konversi di service layer.

**Hari 8–9 — Full testing 20 test case** `[SELESAI — sisi Person A]`
- 20 test case (teks) ada di `tests/evidence_cases/cases.json`, skrip: `run_test_cases.py`, hasil: `tests/evidence_cases/results.json`.
- Run awal (prompt sebelum aturan entity dan regulasi): 20/20 recall kategori.
- Setelah aturan entity dan regulasi ditambahkan: 16 dari 18 kasus yang berhasil dijalankan cocok. Kasus 12 dan 16 meleset; kasus 19 dan 20 tidak dijalankan karena error 429 (kuota harian Gemini habis), bukan karena model.
- Prompt direvisi: definisi HARASSMENT dan DATA_EXPOSURE diperjelas (termasuk penyebaran data ke penagih lapangan), aturan pembeda ditambah, severity SPAM flooding diset MEDIUM, dan contoh kalimat di definisi THREAT dihapus karena nyaris sama dengan kasus uji 20.
- Run final (3 Okt 2026, `gemini-3.5-flash`): lihat "Hasil pengujian final" di bawah.
- **Person B belum verifikasi** hasil ini dari sisi backend (end-to-end belum jalan).

**Perbaikan entity prompt — Person A** `[SELESAI]`
- Ditambahkan ATURAN ENTITY di prompt:
  - `actor` = nama orang penagih yang tertulis eksplisit (bukan nomor atau nama aplikasi/perusahaan)
  - `phone_number` hanya yang terbaca lengkap (nomor terpotong/disensor dan short code dilewati)
  - `date_time` hanya penanda yang memuat tanggal (jam saja dan kata relatif seperti "kemarin" dilewati; semua tanggal yang terlihat diekstrak)
  - satu nilai hanya muncul di satu `entity_type`; tidak boleh berisi kata ganti ("kami", "kamu", "Anda")

**Referensi regulasi lewat prompt** `[SELESAI — pemetaan statis, bukan RAG]`
- `regulation_reference` kini diisi dari pemetaan kategori → pasal di prompt (bentuk: array `{"law", "article", "note"}`):

| Kategori | Referensi |
|---|---|
| THREAT | UU ITE (UU 11/2008 jo. UU 19/2016 jo. UU 1/2024) Pasal 29 |
| HARASSMENT | POJK 22/2023 Pasal 62 ayat (2) huruf a–b; UU ITE Pasal 27A |
| DATA_EXPOSURE | UU PDP (UU 27/2022) Pasal 65 ayat (2) jo. Pasal 67 ayat (2); UU ITE Pasal 32 (pendukung) |
| SPAM (flooding terkait tagihan sendiri) | POJK 22/2023 Pasal 62 ayat (2) huruf d |
| SPAM (promosi) dan NORMAL | `[]` |

- RAG dari knowledge base regulasi tetap opsional (lihat Sprint 3).

**Hari 10 — Metrik lain** `[SEBAGIAN — sisi Person A]`
- F1 ekstraksi entity: **0,978** (`gemini-3.5-flash`), diukur dengan `run_entity_f1.py` pada 10 gambar uji.
- Kecepatan: rata-rata 20–25 detik/pesan pada free tier Gemini. Target "100 pesan ≤3 menit" tidak realistis di free tier — dilaporkan sebagai limitasi.
- Kebocoran PII: belum diukur.

### Hasil pengujian final — 3 Oktober 2026

| Uji | Data | Hasil |
|---|---|---|
| Klasifikasi kategori | 20 kasus teks (`tests/evidence_cases/cases.json`) | recall 20/20 = 1,00 (THREAT 6/6, HARASSMENT 4/4, DATA_EXPOSURE 4/4, NORMAL 3/3, SPAM 3/3) |
| Severity | 20 kasus yang sama | 18/20 (kasus 9: HIGH → MEDIUM; kasus 20: MEDIUM → HIGH) |
| Ekstraksi entity | 10 gambar uji (tidak di-commit) | F1 0,978 |

Catatan dan keterbatasan:
- Set uji kecil dan internal. Prompt direvisi setelah kasus 12 dan 16 meleset, jadi 20/20 **bukan** estimasi performa pada data baru. Idealnya diuji dengan kasus baru yang ditulis tanpa melihat prompt.
- Severity kasus 9 dan 20: label expected belum selaras dengan aturan prompt (semua ancaman = HIGH). Belum diputuskan apakah label atau aturan yang diubah.
- `results.json` hanya mencatat kategori dan severity. `regulation_reference` dan `entities` belum divalidasi di tes 20 kasus.
- Kasus yang gagal karena error 429/500 dicatat sebagai "tidak dijalankan", bukan sebagai salah.

**Keputusan model final** `[SELESAI]`
- Model: `gemini-3.5-flash` untuk flow, semua tes, dan dokumen. Semua angka di atas diukur dengan model ini.
- Free tier Gemini dibatasi 5 permintaan per menit dan 20 permintaan per hari per model (di akun Person A). Provider lain (Groq, Mistral, OpenRouter, Cloudflare) sempat dipertimbangkan sebagai cadangan, tapi tidak dipakai karena semua angka harus diukur ulang dan deadline dekat.

**Keamanan repo** `[SELESAI]`
- API key tidak ditulis di kode: skrip membaca `LANGFLOW_API_KEY` dari environment variable (`tests/test_langflow_api.py` sudah diperbaiki).
- Data uji F1 (`tests/f1_entity/`: gambar, `entity_cases.json`, `entity_results*.json`) tidak di-commit karena memuat data pribadi (nama dan nomor). Disimpan lokal dan masuk `.gitignore`; yang di-commit hanya skrip dan angka ringkasan.
- Export flow sudah dicek: tidak ada API key, prompt dan model sesuai versi final.

---

## Status: SEDANG BERJALAN

**Install Langflow lokal di laptop Person B** `[SEDANG BERJALAN]`
- Person B perlu install Langflow sendiri (`pip install langflow && langflow run`), import `langflow/evidence_analysis_flow.json` (versi terbaru), dan buat API key Langflow baru.
- Isi `LANGFLOW_FLOW_ID` di `backend/.env` dengan Flow ID hasil import. Pastikan `LANGFLOW_CHAT_INPUT_ID` tetap `ChatInput-aPEX5`.
- Pilih `gemini-3.5-flash` di komponen Language Model, dengan API key Gemini dari Google project yang berbeda dari Person A.
- Alternatif: Person A jalankan `ngrok http 7860` dan kirim URL ngrok ke Person B.

---

## Status: BELUM DIMULAI (Next Steps)

**End-to-end test dengan flow terbaru** `[BELUM DIMULAI]`
- Setelah Langflow lokal Person B berjalan:
  - Upload file via `POST /api/evidence/upload` → verifikasi response `ai_process.status === "success"`.
  - Cek tabel `ai_analysis` terisi category/severity/reason/confidence/regulation_reference yang real.
  - Cek tabel `extracted_entity` terisi entities tanpa kata ganti.
  - Pastikan kolom `regulation_reference` muat teks JSON (sekitar 1 KB untuk HARASSMENT dan DATA_EXPOSURE); ubah ke tipe text kalau perlu.

**Template PDF: bagian referensi regulasi** `[PERLU DICEK — Person B]`
- Commit terbaru Person B ("Update PDF blade & Backend wiring") sudah menyentuh PDF. Pastikan `regulation_reference` ditampilkan (`law`, `article`, `note`) dan bagian itu disembunyikan kalau array kosong (NORMAL dan SPAM promosi).

**Revisi dokumen master dan materi demo** `[BELUM DIMULAI]`
- Lihat bagian "Revisi Dokumen Master" di bawah. Materi demo (alur upload → analisis → PDF) perlu disiapkan setelah end-to-end jalan.

**Sprint 3 — RAG / Regulation Intelligence** `[OPSIONAL — BELUM DIMULAI]`
- Pengganti pemetaan statis di prompt: isi `regulation_reference` dari knowledge base regulasi OJK (folder `knowledge_base/`).
- Untuk submission Stage 1, pemetaan statis sudah cukup. RAG hanya dikerjakan kalau waktu tersisa.
- Person A mengintegrasikan RAG ke flow Langflow. Person B tidak perlu mengubah backend — kolom `regulation_reference` sudah ada dan siap terima string JSON.

**Sprint 4–5 — Bob assistant, polish, submission** `[BELUM DIRENCANAKAN DETAIL]`

---

## Catatan Teknis

- **Flow ID & Component ID (dari `langflow/evidence_analysis_flow.json`):**
  - Flow ID: `e05721f1-c3a2-4a33-bbd2-d30dee3df995`
  - Chat Input Component ID: `ChatInput-aPEX5`
  - Isi di `backend/.env`: `LANGFLOW_FLOW_ID` dan `LANGFLOW_CHAT_INPUT_ID`
  - Setelah import ke Langflow lain, Flow ID bisa berbeda. Pakai ID hasil import.
- **Kuota Gemini gratis:** 5 permintaan per menit dan 20 permintaan per hari per model (`gemini-3.5-flash`, akun Person A). Person A dan Person B **wajib pakai API key dari Google project yang berbeda** agar tidak rebutan kuota saat testing bareng. Kuota dibagi per project Google, bukan per API key — semua key dalam satu project berbagi kuota yang sama. Satu analisis = satu permintaan; run penuh 20 kasus menghabiskan jatah harian. Reset harian sekitar pukul 14.00 WIB (cek di Google AI Studio).
- **Error 429/500 dari Langflow:** error 500 bisa membungkus 429 dari Gemini (kuota habis). Cek log terminal Langflow untuk melihat error asli. Backend sebaiknya menampilkan pesan "coba lagi", bukan error mentah.
- **API key:** jangan commit key ke repo. Sebelum commit export flow, cek:
  `Select-String -Path langflow\*.json -Pattern 'AIza|sk-[A-Za-z0-9_-]{20,}'` (PowerShell). Jika ada hasil, revoke key dan buat baru. Untuk skrip tes, set `$env:LANGFLOW_API_KEY` dan `$env:BENCH_MODEL="gemini-3.5-flash"` sebelum menjalankan `run_test_cases.py`.
- **Langflow lokal:** `127.0.0.1:7860` di laptop Person A tidak bisa dijangkau backend di laptop Person B. Person B mengimpor `langflow/evidence_analysis_flow.json` dan menjalankan Langflow sendiri untuk development. Untuk demo final, semua berjalan di satu mesin.
- **Skala confidence:** 0–100 (dikonfirmasi Person A). Kolom DB `decimal(5,2)` sudah benar. Tidak ada konversi di service layer.
- **Entities:** disimpan di tabel `extracted_entity` (bukan kolom di `ai_analysis`). Setiap entity = 1 baris dengan `entity_type`, `entity_value`, `confidence`.
- **Format output model:** model diminta membalas JSON murni tanpa markdown, tetapi parser backend sebaiknya tetap membersihkan pembungkus ``` dan punya fallback kalau JSON tidak valid.

---

## Keputusan Terbuka

1. ~~Framework backend~~ → **Laravel** (Person B).
2. ~~Tipe database~~ → **MySQL** (Person B).
3. ~~Arsitektur integrasi Langflow~~ → **Opsi B: sinkron, Person B update DB** (disepakati 25 Sep 2026).
4. ~~Skala `confidence`~~ → **0–100** sesuai output Langflow. Kolom DB sudah disesuaikan.
5. ~~Model LLM final~~ → **`gemini-3.5-flash`** (3 Okt 2026).
6. **Target metrik MVP** — status per 3 Okt 2026:
   - recall kategori ≥0,90 → **tercapai** (20/20, set uji internal)
   - F1 entity ≥0,90 → **tercapai** (0,978)
   - PII bocor nol → **belum diukur** (definisi "kebocoran PII" belum disepakati)
   - 100 pesan ≤3 menit → **tidak tercapai** di free tier (dilaporkan sebagai limitasi)
7. **Label severity kasus 9 dan 20** → perlu diputuskan: ubah label expected atau aturan severity di prompt.

---

## Revisi Dokumen Master yang Masih Menggantung

- Ganti semua sebutan "IBM Granite reasoning" menjadi Gemini (`gemini-3.5-flash`) di Bagian 1, 3, 4.2, dan 7.
- Reframe slide "IBM Technology Advantage" (Bagian 11): nilai jualnya adalah integrasi Langflow + IBM Bob, bukan Granite.
- Samakan nama field output: master memakai `evidence`, DB dan backend memakai `reason`.
- Tulis hasil pengujian dengan keterbatasannya (set uji internal, prompt disetel dari kasus uji, free tier).

---

## Rencana Sampai Submission

| Waktu | Target |
|---|---|
| 3 Okt | Push flow final dan hasil uji; Person B import flow dan jalankan end-to-end; PDF memuat referensi regulasi |
| 4 Okt pagi | Tes bareng, dokumen master dan materi demo dirapikan |
| 4 Okt siang | Isi form submission (bisa diisi ulang; data terakhir yang dinilai) |
| 4 Okt 23:00 WIB | Batas akhir submission Stage 1 |

Info panitia (28 Sep): penilaian Stage 1 terbesar di ide proyek; belum maksimal atau masih ada error boleh; kalau lolos, proyek wajib sudah bisa dijalankan; form submission menerima dokumentasi, screenshot, GitHub repository, atau pitching deck.

---

*Terakhir diupdate: 3 Oktober 2026 — Person A: model final `gemini-3.5-flash`; recall kategori 20/20, severity 18/20, F1 entity 0,978; ATURAN ENTITY dan referensi regulasi (pemetaan statis) ditambahkan ke prompt; export flow diperbarui; API key dipindah ke environment variable dan data uji sensitif dikeluarkan dari repo. Bottleneck sekarang: Langflow lokal Person B dan uji end-to-end dengan flow terbaru.*