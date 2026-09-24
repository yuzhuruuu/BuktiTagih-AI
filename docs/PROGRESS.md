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
| LLM | Gemini API (`gemini-3.8-flash`) |
| Model di flow saat commit terakhir | ______ (isi manual: 3.8 Flash atau 3.6 Flash) |
| Backend (keputusan Person B) | Laravel + MySQL, frontend HTML |

**Ide dasar produk:** AI evidence assistant untuk korban penagihan pinjaman daring (pinjol) bermasalah. User upload screenshot/chat/call log, sistem mengekstrak dan mengklasifikasi bukti pelanggaran, (nanti) di-ground ke regulasi lewat RAG, lalu hasilnya menjadi laporan PDF untuk pengaduan ke OJK atau pihak berwajib.

---

## Koreksi Penting: Granite diganti Gemini

Master reference document awalnya mengasumsikan **IBM Granite** sebagai reasoning engine. Setelah dicek ke materi resmi (slide Sesi 1–3 dan panduan hackathon):
- Tools wajib project hanya **Langflow dan IBM Bob**. Tidak ada kewajiban memakai watsonx/Granite.
- LLM yang diajarkan di Track 1 adalah **Gemini API Key** (Google AI Studio).
- Kriteria penilaian Track 1 tidak mewajibkan model tertentu.

**Keputusan final:** pakai Gemini, Granite di-drop dari implementasi. Setup watsonx.ai sempat dicoba tetapi model Granite instruct/chat tidak tersedia di plan akun (Lite/trial), dan memang tidak perlu diselesaikan.

**Yang perlu diselaraskan:** laporan Person B masih menyebut "IBM Granite" di diagram alur dan rencana Bob. Person B perlu menggantinya menjadi Gemini.

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
- Uji lewat API (upload file lalu run) masih perlu dilakukan, lihat bagian Belum Dimulai.

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

---

## Status: SEDANG BERJALAN

**Sinkronisasi dengan Person B** `[SEDANG BERJALAN]`
- Sisi Person B: upload, hash SHA-256, halaman hasil, dan laporan PDF sudah berjalan (contoh Evidence #5). Hasil AI masih "Menunggu Proses AI" karena Langflow belum tersambung.
- Person B menunggu: URL Langflow, Flow ID, API key, format request, format respons, dan kepastian siapa yang mengupdate DB.
- Usulan dari Person A: backend Laravel menerima respons Langflow langsung lalu mengupdate tabel `ai_analysis` (Langflow `/run` bersifat sinkron, tidak perlu callback). Upload gambar lewat `/api/v1/files/upload/{FLOW_ID}`, lalu `/api/v1/run/{FLOW_ID}` dengan tweak `files` pada Chat Input.

---

## Status: BELUM DIMULAI (Next Steps)

**Uji API Langflow (lanjutan Hari 6)** `[BELUM DIMULAI]`
Upload gambar lewat endpoint file, lalu run flow lewat API. Kirim ID komponen Chat Input dan contoh respons asli ke Person B.

**Hari 8 — Tambah 16 test case baru** `[BELUM DIMULAI]`
Target minimum 20 test case (Bagian 8 dokumen master). Baru ada 4 dan belum ada satu pun kategori SPAM. Usul pembagian: 8 oleh Person A (termasuk SPAM), 8 oleh Person B. Format: `tests/evidence_cases/cases.json` (id, input, expected_category, expected_severity).

**Hari 9 — Full testing 20 test case** `[BELUM DIMULAI]`
Jalankan semua, ukur recall kategori (target ≥0,90), tuning prompt jika belum tercapai. Semua test memakai satu model yang sama.

**Hari 10 — Cek metrik lain** `[BELUM DIMULAI]`
F1 ekstraksi entity ≥0,90, kecepatan (100 pesan ≤3 menit; saat ini sekitar 17 detik per pesan), kebocoran PII nol.

**Perbaikan opsional prompt** `[BELUM DIMULAI]`
Entity `actor` dan `victim` masih terisi kata ganti ("Kami", "kamu"). Tambahkan instruksi agar entity berisi nilai konkret (nama, nomor, organisasi, tanggal).

**Sprint 3–5** `[BELUM DIRENCANAKAN DETAIL]`
Regulation Intelligence (RAG), Product Layer (sebagian besar sudah dikerjakan Person B), Bob assistant, testing, polish, dan persiapan submission.

---

## Catatan Teknis

- **Kuota Gemini gratis:** batas 5 permintaan per menit per model, dan error kuota (limit 20 permintaan) pernah muncul di `gemini-3.8-flash`. Person A dan Person B memakai API key dari project Google yang berbeda. Jangan kirim pesan beruntun saat uji di Playground.
- **API key:** jangan commit key ke repo. Sebelum commit export flow, cek dengan `Select-String -Path langflow\*.json -Pattern "AIza"` (PowerShell). Jika ada hasil, revoke key dan buat baru.
- **Langflow lokal:** `127.0.0.1:7860` di laptop Person A tidak bisa dijangkau backend di laptop Person B. Person B mengimpor `langflow/evidence_analysis_flow.json` dan menjalankan Langflow sendiri untuk development. Untuk demo final, semua berjalan di satu mesin.

---

## Keputusan Terbuka

1. ~~Framework backend~~ → **Laravel** (Person B).
2. ~~Tipe database~~ → **MySQL** (Person B).
3. **Target metrik MVP** (F1 entity ≥0,90, recall kategori ≥0,90, PII bocor nol, 100 pesan ≤3 menit): perlu dikonfirmasi tim sebagai acuan testing.
4. **Skala `confidence`:** Person A memakai 0–100. Person B perlu mengonfirmasi kolom DB.

---

## Revisi Dokumen Master yang Masih Menggantung

- Ganti semua sebutan "IBM Granite reasoning" menjadi Gemini di Bagian 1, 3, 4.2, dan 7.
- Reframe slide "IBM Technology Advantage" (Bagian 11): nilai jualnya adalah integrasi Langflow + IBM Bob, bukan Granite.
- Samakan nama field output: master memakai `evidence`, DB dan backend memakai `reason`.

---

*Terakhir diupdate: 24 September 2026, setelah Hari 6–7 selesai.*
