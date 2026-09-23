<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 11pt;
        color: #1a1a1a;
        background: #fff;
        padding: 40px;
    }

    /* ── Header ── */
    .header {
        border-bottom: 3px solid #1d4ed8;
        padding-bottom: 16px;
        margin-bottom: 24px;
    }
    .header-brand {
        font-size: 22pt;
        font-weight: bold;
        color: #1d4ed8;
        letter-spacing: -0.5px;
    }
    .header-sub {
        font-size: 9pt;
        color: #6b7280;
        margin-top: 2px;
    }
    .header-meta {
        text-align: right;
        font-size: 8.5pt;
        color: #6b7280;
        margin-top: -36px;
    }

    /* ── Section ── */
    .section {
        margin-bottom: 20px;
    }
    .section-title {
        font-size: 8pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6b7280;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 4px;
        margin-bottom: 10px;
    }

    /* ── Verdict box ── */
    .verdict-box {
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-left: 4px solid #0ea5e9;
        border-radius: 6px;
        padding: 14px 18px;
        margin-bottom: 20px;
    }
    .verdict-box.high   { background: #fff1f2; border-color: #fecdd3; border-left-color: #ef4444; }
    .verdict-box.medium { background: #fffbeb; border-color: #fde68a; border-left-color: #f59e0b; }
    .verdict-box.low    { background: #f0fdf4; border-color: #bbf7d0; border-left-color: #22c55e; }
    .verdict-box.pending{ background: #f9fafb; border-color: #e5e7eb; border-left-color: #9ca3af; }

    .verdict-category {
        font-size: 14pt;
        font-weight: bold;
        color: #111827;
    }
    .verdict-severity {
        font-size: 9pt;
        color: #374151;
        margin-top: 3px;
    }
    .verdict-confidence {
        font-size: 9pt;
        color: #6b7280;
        margin-top: 2px;
    }

    /* ── Table ── */
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10pt;
    }
    table td {
        padding: 7px 10px;
        border-bottom: 1px solid #f3f4f6;
        vertical-align: top;
    }
    table td.key {
        width: 35%;
        color: #6b7280;
        font-weight: bold;
    }
    table td.val {
        color: #111827;
    }

    /* ── Text boxes ── */
    .text-box {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
        padding: 10px 14px;
        font-size: 10pt;
        color: #374151;
        line-height: 1.6;
    }

    .reg-box {
        background: #eff6ff;
        border: 1px solid #dbeafe;
        border-radius: 4px;
        padding: 10px 14px;
        font-size: 10pt;
        color: #1e40af;
        line-height: 1.6;
    }

    /* ── Disclaimer ── */
    .disclaimer {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 4px;
        padding: 10px 14px;
        font-size: 8.5pt;
        color: #92400e;
        margin-top: 24px;
    }

    /* ── Footer ── */
    .footer {
        margin-top: 28px;
        border-top: 1px solid #e5e7eb;
        padding-top: 10px;
        font-size: 8pt;
        color: #9ca3af;
        text-align: center;
    }
</style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-brand">BuktiTagih AI</div>
    <div class="header-sub">Platform Kecerdasan Bukti Digital — Laporan Analisis Evidence</div>
    <div class="header-meta">
        Dibuat: {{ $generated_at }}<br>
        Evidence ID: #{{ $evidence->evidence_id }}
    </div>
</div>

<!-- Verdict -->
<div class="section">
    <div class="section-title">Hasil Klasifikasi AI</div>
    @php
        $severityClass = strtolower($analysis->severity ?? 'pending');
        if (!in_array($severityClass, ['high','medium','low'])) $severityClass = 'pending';
    @endphp
    <div class="verdict-box {{ $severityClass }}">
        <div class="verdict-category">{{ $category_label }}</div>
        <div class="verdict-severity">Tingkat Keparahan: <strong>{{ $severity_label }}</strong></div>
        <div class="verdict-confidence">Tingkat Keyakinan AI: <strong>{{ $confidence_pct }}</strong></div>
    </div>
</div>

<!-- Alasan -->
<div class="section">
    <div class="section-title">Alasan Analisis AI</div>
    <div class="text-box">{{ $analysis->reason ?? 'Menunggu proses AI pipeline...' }}</div>
</div>

<!-- Regulasi -->
<div class="section">
    <div class="section-title">Referensi Regulasi</div>
    <div class="reg-box">{{ $analysis->regulation_reference ?? 'Referensi regulasi belum tersedia. Pipeline RAG belum terhubung.' }}</div>
</div>

<!-- Data Evidence -->
<div class="section">
    <div class="section-title">Informasi Bukti yang Dianalisis</div>
    <table>
        <tr>
            <td class="key">Evidence ID</td>
            <td class="val">#{{ $evidence->evidence_id }}</td>
        </tr>
        <tr>
            <td class="key">Nama File</td>
            <td class="val">{{ $evidence->file_name }}</td>
        </tr>
        <tr>
            <td class="key">Tipe File</td>
            <td class="val">{{ $evidence->file_type }}</td>
        </tr>
        <tr>
            <td class="key">Waktu Upload</td>
            <td class="val">{{ $evidence->upload_time }}</td>
        </tr>
        <tr>
            <td class="key">Hash File (SHA-256)</td>
            <td class="val" style="font-size:8pt; word-break:break-all;">{{ $evidence->hash_file }}</td>
        </tr>
    </table>
</div>

<!-- Disclaimer -->
<div class="disclaimer">
    <strong>Perhatian:</strong> Laporan ini dihasilkan oleh sistem AI BuktiTagih dan <strong>bukan merupakan nasihat hukum</strong>.
    Gunakan laporan ini sebagai bahan pendukung pengaduan ke OJK, AFPI, lembaga bantuan hukum, atau kuasa hukum.
    Keputusan hukum tetap berada di tangan profesional hukum yang berwenang.
</div>

<!-- Footer -->
<div class="footer">
    BuktiTagih AI — IBM SkillsBuild University Education National Hackathon 2026 &nbsp;·&nbsp;
    Laporan dibuat otomatis oleh sistem AI. Dokumen ini bersifat rahasia.
</div>

</body>
</html>
