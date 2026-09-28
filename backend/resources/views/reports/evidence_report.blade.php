<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 11pt;
        color: #11213d;
        background: linear-gradient(180deg, #f2f8ff 0%, #edf4ff 100%);
        padding: 36px 30px 28px;
    }

    .header {
        background: rgba(255,255,255,0.72);
        border: 1px solid rgba(88, 126, 255, 0.18);
        border-radius: 18px;
        padding: 18px 20px;
        margin-bottom: 22px;
        box-shadow: 0 10px 22px rgba(30, 72, 172, 0.06);
    }

    .header-brand {
        font-size: 22pt;
        font-weight: bold;
        color: #1d4ed8;
        letter-spacing: -0.5px;
    }

    .header-sub {
        font-size: 9pt;
        color: #59709a;
        margin-top: 2px;
    }

    .header-meta {
        text-align: right;
        font-size: 8.5pt;
        color: #59709a;
        margin-top: -34px;
    }

    .section {
        margin-bottom: 20px;
        background: rgba(255,255,255,0.68);
        border: 1px solid rgba(88, 126, 255, 0.15);
        border-radius: 16px;
        padding: 14px 16px;
    }

    .section-title {
        font-size: 8pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #59709a;
        border-bottom: 1px solid rgba(148, 163, 184, 0.2);
        padding-bottom: 6px;
        margin-bottom: 10px;
    }

    .verdict-box {
        background: rgba(219,234,254,0.55);
        border: 1px solid rgba(96,165,250,0.2);
        border-left: 4px solid #2563eb;
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 8px;
    }
    .verdict-box.high   { background: rgba(255,241,243,0.9); border-color: rgba(214,40,57,0.18); border-left-color: #d62839; }
    .verdict-box.medium { background: rgba(255,247,221,0.9); border-color: rgba(201,121,0,0.2); border-left-color: #c77b00; }
    .verdict-box.low    { background: rgba(235,255,247,0.9); border-color: rgba(27,156,109,0.18); border-left-color: #1b9c6d; }
    .verdict-box.pending{ background: rgba(255,255,255,0.7); border-color: rgba(148,163,184,0.2); border-left-color: #8aa0c1; }

    .verdict-category {
        font-size: 14pt;
        font-weight: bold;
        color: #11213d;
    }
    .verdict-severity {
        font-size: 9pt;
        color: #2d3d5f;
        margin-top: 3px;
    }
    .verdict-confidence {
        font-size: 9pt;
        color: #59709a;
        margin-top: 2px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10pt;
    }
    table td {
        padding: 7px 10px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.18);
        vertical-align: top;
    }
    table td.key {
        width: 35%;
        color: #59709a;
        font-weight: bold;
    }
    table td.val {
        color: #11213d;
    }

    .text-box,
    .reg-box {
        background: rgba(255,255,255,0.7);
        border: 1px solid rgba(96,165,250,0.18);
        border-radius: 10px;
        padding: 10px 12px;
        font-size: 10pt;
        color: #2d3d5f;
        line-height: 1.6;
    }

    .reg-box {
        background: rgba(219,234,254,0.45);
        border-color: rgba(96,165,250,0.24);
        color: #1d4ed8;
    }

    .disclaimer {
        background: rgba(255,247,221,0.8);
        border: 1px solid rgba(201,121,0,0.22);
        border-radius: 12px;
        padding: 10px 12px;
        font-size: 8.5pt;
        color: #7a5200;
        margin-top: 22px;
    }

    .footer {
        margin-top: 24px;
        border-top: 1px solid rgba(148,163,184,0.25);
        padding-top: 10px;
        font-size: 8pt;
        color: #7b8ba7;
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
