'use client';

import { useEffect, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { apiService, AnalysisResult } from '@/app/services/api';
import styles from './result.module.css';

export default function ResultPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const evidenceId = searchParams.get('evidence_id');
  const analysisId = searchParams.get('analysis_id');

  const [analysis, setAnalysis] = useState<AnalysisResult | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const CATEGORY_LABEL: Record<string, string> = {
    HARASSMENT: 'Intimidasi',
    THREAT: 'Ancaman',
    DATA_EXPOSURE: 'Kebocoran Data',
    SPAM: 'Spam',
    NORMAL: 'Normal',
    PENDING: 'Menunggu Proses AI',
  };

  const SEVERITY_LABEL: Record<string, string> = {
    HIGH: 'Tinggi',
    MEDIUM: 'Sedang',
    LOW: 'Rendah',
    PENDING: 'Pending',
  };

  useEffect(() => {
    const loadAnalysis = async () => {
      try {
        setLoading(true);
        let data: AnalysisResult;

        if (analysisId) {
          data = await apiService.getAnalysis(parseInt(analysisId));
        } else if (evidenceId) {
          data = await apiService.getAnalysisByEvidence(parseInt(evidenceId));
        } else {
          throw new Error('Parameter tidak ditemukan di URL');
        }

        setAnalysis(data);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Gagal memuat data');
      } finally {
        setLoading(false);
      }
    };

    loadAnalysis();
  }, [evidenceId, analysisId]);

  if (loading) {
    return (
      <>
        <nav className={styles.nav}>
          <span className={styles.brand}>
            Bukti<span>Tagih</span>
          </span>
          <a href="/" className={styles.btnBack}>
            ← Upload Baru
          </a>
        </nav>
        <div className={styles.page}>
          <div className={styles.pageHeader}>
            <h1>Hasil Analisis Bukti</h1>
            <p className={styles.sub}>Memuat data analisis…</p>
          </div>
          <div className={styles.card}>
            <div className={`${styles.skeleton} ${styles.skeletonLarge}`}></div>
            <div className={`${styles.skeleton} ${styles.skeletonMedium}`}></div>
          </div>
        </div>
      </>
    );
  }

  if (error || !analysis) {
    return (
      <>
        <nav className={styles.nav}>
          <span className={styles.brand}>
            Bukti<span>Tagih</span>
          </span>
        </nav>
        <div className={styles.page}>
          <div className={styles.errorCard}>
            <div className={styles.errorTitle}>Gagal memuat hasil analisis</div>
            <div className={styles.errorSub}>{error}</div>
            <a href="/" className={styles.btnOutline}>
              ← Kembali ke Upload
            </a>
          </div>
        </div>
      </>
    );
  }

  const category = (analysis.category || 'PENDING').toUpperCase();
  const severity = (analysis.severity || 'PENDING').toUpperCase();
  const confidence = Math.round(parseFloat(analysis.confidence?.toString() || '0'));
  const severityClass = severity === 'HIGH' ? 'high' : severity === 'MEDIUM' ? 'medium' : severity === 'LOW' ? 'low' : 'pending';

  return (
    <>
      <nav className={styles.nav}>
        <span className={styles.brand}>
          Bukti<span>Tagih</span>
        </span>
        <a href="/" className={styles.btnBack}>
          ← Upload Baru
        </a>
      </nav>

      <div className={styles.page}>
        <div className={styles.pageHeader}>
          <h1>Hasil Analisis Bukti</h1>
          <p className={styles.sub}>
            Evidence #{evidenceId || analysis.evidence_id || '—'}
            {analysisId && ` · Analysis #${analysisId}`}
          </p>
        </div>

        <div className={styles.card}>
          <div className={styles.sectionLabel}>Hasil Klasifikasi</div>

          <div className={`${styles.verdictBanner} ${styles[severityClass]}`}>
            <div className={styles.verdictRow}>
              <span className={`${styles.badgeCat} ${styles[category]}`}>
                {CATEGORY_LABEL[category] || category}
              </span>
              <span className={`${styles.badgeSev} ${styles[severity]}`}>
                {SEVERITY_LABEL[severity] || severity}
              </span>
            </div>
          </div>

          <div style={{ marginBottom: '20px' }}>
            <div className={styles.confRow}>
              <span>Tingkat keyakinan analisis</span>
              <span className={styles.confVal}>{confidence}%</span>
            </div>
            <div className={styles.confTrack}>
              <div
                className={styles.confFill}
                style={{ width: `${confidence}%` }}
              ></div>
            </div>
          </div>

          <div className={styles.sectionLabel}>Alasan Analisis</div>
          <div className={styles.textBox}>
            {analysis.reason || 'Menunggu proses AI pipeline…'}
          </div>
        </div>

        <div className={styles.card}>
          <div className={styles.sectionLabel}>Referensi Regulasi</div>
          <div className={styles.regBox}>
            {analysis.regulation_reference || 'Referensi regulasi belum tersedia.'}
          </div>
          <div className={styles.regSource}>
            Sumber: POJK, AFPI, UU PDP — knowledge base BuktiTagih
          </div>
        </div>

        {analysis.evidence && (
          <div className={styles.card}>
            <div className={styles.sectionLabel}>Informasi Bukti</div>
            <table className={styles.metaTable}>
              <tbody>
                <tr>
                  <td className={styles.metaKey}>Evidence ID</td>
                  <td className={styles.metaVal}>#{analysis.evidence.evidence_id}</td>
                </tr>
                <tr>
                  <td className={styles.metaKey}>Nama File</td>
                  <td className={styles.metaVal}>{analysis.evidence.file_name}</td>
                </tr>
                <tr>
                  <td className={styles.metaKey}>Tipe File</td>
                  <td className={styles.metaVal}>{analysis.evidence.file_type}</td>
                </tr>
                <tr>
                  <td className={styles.metaKey}>Waktu Upload</td>
                  <td className={styles.metaVal}>{analysis.evidence.upload_time}</td>
                </tr>
              </tbody>
            </table>
          </div>
        )}

        <div className={styles.actions}>
          <a
            href={apiService.getPdfReportUrl(evidenceId ? parseInt(evidenceId) : analysis.evidence_id!)}
            target="_blank"
            rel="noopener noreferrer"
            className={styles.btnPdf}
            download={`BuktiTagih_Laporan_${evidenceId}.pdf`}
          >
            Download Laporan PDF
          </a>
          <a href={`/bob?analysis_id=${analysisId || analysis.analysis_id}`} className={styles.btnOutline}>
            Diskusi dengan Bob
          </a>
          <a href="/" className={styles.btnOutline}>
            Upload Bukti Lain
          </a>
        </div>

        <div className={styles.disclaimer}>
          <strong>Perhatian:</strong> Hasil analisis ini dihasilkan oleh sistem AI dan{' '}
          <em>bukan merupakan nasihat hukum</em>. Gunakan laporan ini sebagai bahan
          pendukung pengaduan ke OJK, AFPI, atau lembaga bantuan hukum.
        </div>
      </div>
    </>
  );
}
