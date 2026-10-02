<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Analisis Bukti - BuktiTagih</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .brand {
            font-size: 24px;
            font-weight: 800;
            color: #1d4ed8;
            margin-bottom: 8px;
        }
        
        .brand span { color: #2563eb; }
        
        .meta {
            font-size: 12px;
            color: #666;
            text-align: right;
            margin-top: 15px;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #1d4ed8;
            border-left: 4px solid #2563eb;
            padding-left: 12px;
            margin-bottom: 15px;
        }
        
        .verdict-box {
            background: #f0f4ff;
            border: 2px solid #dbeafe;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }
        
        .verdict-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .verdict-label {
            font-weight: 600;
            color: #2563eb;
        }
        
        .verdict-value {
            font-size: 18px;
            font-weight: 700;
            color: #1d4ed8;
        }
        
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 12px;
        }
        
        .badge-high {
            background: rgba(214, 40, 57, 0.1);
            color: #8a1d2d;
            border: 1px solid rgba(214, 40, 57, 0.2);
        }
        
        .badge-medium {
            background: rgba(201, 121, 0, 0.1);
            color: #7a5200;
            border: 1px solid rgba(201, 121, 0, 0.22);
        }
        
        .badge-low {
            background: rgba(27, 156, 109, 0.1);
            color: #116329;
            border: 1px solid rgba(27, 156, 109, 0.2);
        }
        
        .text-box {
            background: #fafafa;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 15px;
            line-height: 1.8;
            white-space: pre-wrap;
            word-break: break-word;
        }
        
        .regulation-item {
            background: #f9fafb;
            border-left: 4px solid #2563eb;
            padding: 12px 15px;
            margin-bottom: 12px;
            border-radius: 4px;
        }
        
        .regulation-law {
            font-weight: 700;
            color: #1d4ed8;
            margin-bottom: 4px;
        }
        
        .regulation-article {
            font-size: 13px;
            color: #2563eb;
            margin-bottom: 4px;
            font-weight: 600;
        }
        
        .regulation-note {
            font-size: 13px;
            color: #555;
            line-height: 1.5;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .info-table td {
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .info-table td:first-child {
            width: 35%;
            font-weight: 600;
            color: #2563eb;
            background: #f9fafb;
        }
        
        .info-table tr:last-child td {
            border-bottom: none;
        }
        
        .footer {
            border-top: 1px solid #e0e0e0;
            margin-top: 40px;
            padding-top: 20px;
            font-size: 11px;
            color: #666;
            text-align: center;
        }
        
        .disclaimer {
            background: #fff7dd;
            border: 1px solid rgba(201, 121, 0, 0.22);
            border-radius: 6px;
            padding: 12px 15px;
            font-size: 12px;
            color: #7a5200;
            line-height: 1.6;
            margin-top: 20px;
        }
        
        @media print {
            body { padding: 0; }
            .container { box-shadow: none; }
        }
    </style>
</head>
<body>

<div class="container">
    
    <!-- Header -->
    <div class="header">
        <div class="brand">Bukti<span>Tagih</span></div>
        <div style="font-size: 13px; color: #666; margin-top: 8px;">
            Laporan Analisis Bukti Digital Penagihan Pinjol
        </div>
        <div class="meta">
            <div>Dihasilkan: {{ $generated_at }}</div>
            <div>Evidence ID: #{{ $evidence->evidence_id }}</div>
        </div>
    </div>

    <!-- Hasil Klasifikasi -->
    <div class="section">
        <div class="section-title">Hasil Klasifikasi</div>
        
        <div class="verdict-box">
            <div class="verdict-row">
                <span class="verdict-label">Kategori Pelanggaran:</span>
                <span class="badge badge-{{ strtolower($severity_label) === 'Tinggi' ? 'high' : (strtolower($severity_label) === 'Sedang' ? 'medium' : 'low') }}">
                    {{ $category_label }}
                </span>
            </div>
            
            <div class="verdict-row">
                <span class="verdict-label">Tingkat Keparahan:</span>
                <span class="verdict-value">{{ $severity_label }}</span>
            </div>
            
            <div class="verdict-row">
                <span class="verdict-label">Tingkat Keyakinan AI:</span>
                <span class="verdict-value">{{ $confidence_pct }}</span>
            </div>
        </div>
    </div>

    <!-- Alasan Analisis -->
    <div class="section">
        <div class="section-title">Alasan Analisis</div>
        <div class="text-box">{{ $analysis->reason ?? 'Analisis sedang diproses...' }}</div>
    </div>

    <!-- Referensi Regulasi — hanya tampil jika ada dan tidak NORMAL/SPAM kosong -->
    @if($analysis && $analysis->regulation_reference)
        @php
            $regulations = is_string($analysis->regulation_reference) 
                ? json_decode($analysis->regulation_reference, true) 
                : $analysis->regulation_reference;
            
            if (!is_array($regulations)) {
                $regulations = [];
            }
            
            // Filter out "no violation" references untuk kategori NORMAL/SPAM
            if (in_array($analysis->category, ['NORMAL', 'SPAM'])) {
                $regulations = [];
            }
        @endphp
        
        @if(count($regulations) > 0)
            <div class="section">
                <div class="section-title">Referensi Regulasi</div>
                
                @foreach($regulations as $reg)
                    <div class="regulation-item">
                        <div class="regulation-law">{{ $reg['law'] ?? $reg['title'] ?? 'Regulasi' }}</div>
                        @if(isset($reg['article']) || isset($reg['pasal']))
                            <div class="regulation-article">{{ $reg['article'] ?? $reg['pasal'] ?? '' }}</div>
                        @endif
                        <div class="regulation-note">{{ $reg['note'] ?? $reg['description'] ?? '' }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
    <div class="section">
        <div class="section-title">Informasi Bukti</div>
        
        <table class="info-table">
            <tr>
                <td>Evidence ID</td>
                <td>#{{ $evidence->evidence_id }}</td>
            </tr>
            <tr>
                <td>Nama File</td>
                <td>{{ $evidence->file_name }}</td>
            </tr>
            <tr>
                <td>Tipe File</td>
                <td>{{ $evidence->file_type }}</td>
            </tr>
            <tr>
                <td>Waktu Upload</td>
                <td>{{ $evidence->upload_time ?? $evidence->created_at }}</td>
            </tr>
            <tr>
                <td>Hash File (SHA-256)</td>
                <td style="font-family: monospace; font-size: 11px; word-break: break-all;">{{ $evidence->hash_file }}</td>
            </tr>
        </table>
    </div>

    <!-- Disclaimer -->
    <div class="disclaimer">
        <strong>Perhatian Penting:</strong> Laporan ini dihasilkan oleh sistem AI dan <strong>bukan merupakan nasihat hukum profesional</strong>. 
        Gunakan laporan ini sebagai bahan pendukung untuk pengaduan resmi ke OJK, AFPI, lembaga bantuan hukum, atau kuasa hukum. 
        Keputusan hukum final harus didasarkan pada konsultasi dengan profesional hukum yang berpengalaman.
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>BuktiTagih AI - Platform Analisis Bukti Digital Penagihan Pinjol</div>
        <div style="margin-top: 8px;">www.buktitagih.ai | Laporan ini dibuat pada {{ date('d F Y H:i:s') }} WIB</div>
    </div>

</div>

</body>
</html>
