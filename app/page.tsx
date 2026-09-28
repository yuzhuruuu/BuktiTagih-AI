'use client';

import { useState, useRef } from 'react';
import { useRouter } from 'next/navigation';
import { apiService } from './services/api';
import styles from './upload.module.css';

export default function UploadPage() {
  const router = useRouter();
  const [file, setFile] = useState<File | null>(null);
  const [progress, setProgress] = useState(0);
  const [status, setStatus] = useState<'idle' | 'uploading' | 'success' | 'error'>('idle');
  const [errorMsg, setErrorMsg] = useState('');
  const [evidenceId, setEvidenceId] = useState<number | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const dropZoneRef = useRef<HTMLDivElement>(null);

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    dropZoneRef.current?.classList.add(styles.over);
  };

  const handleDragLeave = () => {
    dropZoneRef.current?.classList.remove(styles.over);
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    dropZoneRef.current?.classList.remove(styles.over);
    if (e.dataTransfer.files[0]) {
      setFile(e.dataTransfer.files[0]);
    }
  };

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files?.[0]) {
      setFile(e.target.files[0]);
    }
  };

  const handleClearFile = () => {
    setFile(null);
    setStatus('idle');
    if (fileInputRef.current) fileInputRef.current.value = '';
  };

  const formatBytes = (bytes: number) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
  };

  const getFileIcon = (name: string) => {
    if (/\.(jpg|jpeg|png)$/i.test(name)) return '🖼️';
    if (/\.pdf$/i.test(name)) return '📑';
    return '📄';
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!file) return;

    setStatus('uploading');
    setErrorMsg('');
    setProgress(0);

    try {
      setProgress(20);
      const result = await apiService.uploadEvidence(file);
      setProgress(100);
      setEvidenceId(result.evidence_id);
      setStatus('success');

      // Wait untuk memastikan analysis selesai sebelum redirect
      // Poll setiap 2 detik, max 30 detik
      let retries = 0;
      const maxRetries = 15;
      let analysisComplete = false;

      while (retries < maxRetries && !analysisComplete) {
        await new Promise(r => setTimeout(r, 2000)); // Wait 2 sec
        
        try {
          const analysis = await apiService.getAnalysisByEvidence(result.evidence_id);
          if (analysis.category && analysis.category !== 'PENDING') {
            analysisComplete = true;
            // Redirect setelah analysis ready
            router.push(`/result?evidence_id=${result.evidence_id}`);
            return;
          }
        } catch (err) {
          // Retry jika fetch gagal
        }
        retries++;
      }

      // Fallback: redirect setelah timeout (analysis mungkin masih processing)
      // tapi setidaknya kita sudah tunggu
      router.push(`/result?evidence_id=${result.evidence_id}`);
    } catch (err) {
      setStatus('error');
      setErrorMsg(err instanceof Error ? err.message : 'Upload gagal');
      setProgress(0);
    }
  };

  return (
    <>
      <nav className={styles.nav}>
        <span className={styles.brand}>
          Bukti<span>Tagih</span>
        </span>
        <span className={styles.navSub}>Platform Bukti Digital Pinjol</span>
      </nav>

      <div className={styles.page}>
        <div className={styles.pageHeader}>
          <h1>Unggah Bukti Penagihan</h1>
          <p>
            Upload screenshot WhatsApp, chat export, atau PDF riwayat penagihan.
            Sistem akan menganalisis dan menyusun laporan bukti terstruktur.
          </p>
        </div>

        <div className={styles.steps}>
          <div className={`${styles.step} ${styles.active}`}>
            <span className={styles.stepNum}>1</span> Upload Bukti
          </div>
          <div className={styles.stepSep}></div>
          <div className={styles.step}>
            <span className={styles.stepNum}>2</span> Analisis AI
          </div>
          <div className={styles.stepSep}></div>
          <div className={styles.step}>
            <span className={styles.stepNum}>3</span> Hasil &amp; Laporan
          </div>
        </div>

        <div className={styles.card}>
          <form onSubmit={handleSubmit}>
            {!file ? (
              <div
                ref={dropZoneRef}
                className={styles.dropZone}
                onClick={() => fileInputRef.current?.click()}
                onDragOver={handleDragOver}
                onDragLeave={handleDragLeave}
                onDrop={handleDrop}
              >
                <div className={styles.dropIcon}>📄</div>
                <div className={styles.dropTitle}>Seret & lepas file di sini</div>
                <div className={styles.dropHint}>atau klik untuk memilih file</div>
                <div className={styles.dropHint} style={{ marginTop: '4px' }}>
                  JPG · PNG · PDF · Maks. 10 MB
                </div>
              </div>
            ) : (
              <div className={styles.filePreview}>
                <span className={styles.fileIcon}>{getFileIcon(file.name)}</span>
                <div className={styles.fileInfo}>
                  <div className={styles.fileName}>{file.name}</div>
                  <div className={styles.fileSize}>{formatBytes(file.size)}</div>
                </div>
                <button
                  type="button"
                  className={styles.fileRemove}
                  onClick={handleClearFile}
                  title="Hapus file"
                >
                  ✕
                </button>
              </div>
            )}

            <input
              ref={fileInputRef}
              type="file"
              accept=".jpg,.jpeg,.png,.pdf"
              onChange={handleFileSelect}
              style={{ display: 'none' }}
              required
            />

            {status !== 'idle' && (
              <div className={styles.statusArea}>
                {status === 'uploading' && (
                  <div className={styles.progressWrap}>
                    <div className={styles.progressHeader}>
                      <span>Mengunggah file…</span>
                      <span>{progress}%</span>
                    </div>
                    <div className={styles.progressBarBg}>
                      <div
                        className={styles.progressBarFill}
                        style={{ width: `${progress}%` }}
                      ></div>
                    </div>
                  </div>
                )}

                {status === 'success' && (
                  <div className={`${styles.alert} ${styles.alertSuccess}`}>
                    <div className={styles.alertTitle}>Bukti berhasil diproses</div>
                    <div className={styles.alertMeta}>
                      Evidence ID: <code>{evidenceId}</code>
                    </div>
                    <div style={{ marginTop: '8px', fontSize: '0.85rem', color: '#116329' }}>
                      Membawa Anda ke hasil analisis…
                    </div>
                  </div>
                )}

                {status === 'error' && (
                  <div className={`${styles.alert} ${styles.alertError}`}>
                    <div className={styles.alertTitle}>Terjadi kesalahan</div>
                    <div style={{ marginBottom: '8px' }}>{errorMsg}</div>
                    <button
                      type="button"
                      className={styles.btnRetry}
                      onClick={handleClearFile}
                    >
                      Coba Lagi
                    </button>
                  </div>
                )}
              </div>
            )}

            <button
              type="submit"
              className={styles.btnPrimary}
              disabled={!file || status === 'uploading'}
            >
              {status === 'uploading' ? 'Mengunggah…' : 'Analisis Bukti'}
            </button>
          </form>
        </div>

        <div className={styles.infoBox}>
          <strong>Privasi & Keamanan:</strong>
          File diproses secara anonim — tidak perlu akun. Hash SHA-256 digunakan
          untuk memastikan integritas file.
        </div>
      </div>
    </>
  );
}
