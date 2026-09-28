'use client';

import { useEffect, useState } from 'react';
import { useSearchParams } from 'next/navigation';
import { apiService, AnalysisResult } from '@/app/services/api';
import styles from './bob.module.css';

interface Message {
  role: 'user' | 'bob' | 'system';
  text: string;
}

const BOB_KB: Record<string, string> = {
  HARASSMENT: `Kategori **Intimidasi (HARASSMENT)** berarti bukti kamu mengandung perilaku intimidatif dari pihak penagih — misalnya ancaman verbal, bahasa kasar berulang, atau tekanan psikologis yang berlebihan.\n\nIni melanggar:\n• POJK No. 22/POJK.07/2020 — Perlindungan Konsumen Sektor Jasa Keuangan\n• AFPI Code of Conduct — larangan penagihan yang merendahkan martabat`,
  THREAT: `Kategori **Ancaman (THREAT)** berarti terdapat ancaman nyata dalam bukti kamu — misalnya ancaman penyebaran data, ancaman fisik, atau ancaman hukum yang tidak berdasar.\n\nIni melanggar:\n• KUHP Pasal 335 — perbuatan tidak menyenangkan / ancaman\n• UU ITE Pasal 29 — ancaman kekerasan melalui media elektronik\n• POJK No. 22/POJK.07/2020`,
  DATA_EXPOSURE: `Kategori **Kebocoran Data (DATA_EXPOSURE)** berarti pihak penagih mengancam atau melakukan penyebaran data pribadimu — seperti KTP, foto, nomor telepon keluarga, atau informasi sensitif lainnya.\n\nIni melanggar:\n• UU No. 27 Tahun 2022 tentang Perlindungan Data Pribadi (UU PDP)\n• POJK No. 1/POJK.07/2013 — kerahasiaan data konsumen`,
  SPAM: `Kategori **Spam** berarti terdapat pengiriman pesan berulang secara masif yang melampaui batas wajar — misalnya ratusan pesan dalam sehari.\n\nIni melanggar:\n• AFPI Code of Conduct — penagihan hanya boleh dilakukan pada jam tertentu\n• POJK tentang pembatasan frekuensi kontak penagihan`,
  NORMAL: `Kategori **Normal** berarti sistem tidak menemukan indikasi pelanggaran dalam bukti yang kamu upload.\n\nJika kamu merasa ada yang tidak beres, coba upload bukti lain yang lebih spesifik.`,
  ojk: `Cara melaporkan ke OJK:\n\n1. Kunjungi **ojk.go.id** atau aplikasi Appek OJK\n2. Pilih menu "Pengaduan"\n3. Siapkan: KTP, bukti penagihan, laporan PDF dari BuktiTagih\n4. Isi form pengaduan dengan kronologi kejadian\n5. OJK akan menindaklanjuti dalam 20 hari kerja\n\nBisa juga hubungi hotline OJK: 157`,
  afpi: `AFPI (Asosiasi Fintech Pendanaan Bersama Indonesia) menerima pengaduan terkait anggotanya:\n\n1. Website: afpi.or.id\n2. Email: pengaduan@afpi.or.id\n3. Sertakan laporan PDF dari BuktiTagih sebagai bukti pendukung`,
  next_steps: `Langkah yang disarankan setelah menerima hasil analisis:\n\n1. Download laporan PDF dari halaman hasil\n2. Simpan semua bukti asli (screenshot, rekaman)\n3. Laporkan ke OJK melalui ojk.go.id atau hubungi 157\n4. Laporkan ke AFPI jika pinjolnya terdaftar AFPI\n5. Konsultasi ke LBH (Lembaga Bantuan Hukum) terdekat jika kasus serius\n6. Blokir nomor penagih setelah semua bukti sudah dikumpulkan`,
  default: `Saya Bob, asisten analisis BuktiTagih.\n\nSaya bisa membantu kamu memahami:\n• Hasil klasifikasi AI (Intimidasi, Ancaman, Kebocoran Data, Spam)\n• Regulasi yang dilanggar\n• Langkah tindak lanjut ke OJK atau AFPI\n• Cara menggunakan laporan PDF\n\nCoba gunakan tombol cepat di bawah, atau tanyakan langsung.`,
};

function getBobReply(msg: string, context?: AnalysisResult): string {
  const m = msg.toLowerCase();
  if (m.includes('harassment') || m.includes('intimidasi')) return BOB_KB.HARASSMENT;
  if (m.includes('threat') || m.includes('ancaman')) return BOB_KB.THREAT;
  if (m.includes('data_exposure') || m.includes('data exposure') || m.includes('kebocoran'))
    return BOB_KB.DATA_EXPOSURE;
  if (m.includes('spam')) return BOB_KB.SPAM;
  if (m.includes('normal') && m.includes('kategori')) return BOB_KB.NORMAL;
  if (m.includes('ojk') || m.includes('lapor') || m.includes('pengaduan')) return BOB_KB.ojk;
  if (m.includes('afpi')) return BOB_KB.afpi;
  if (m.includes('selanjutnya') || m.includes('langkah') || m.includes('harus'))
    return BOB_KB.next_steps;
  if (
    m.includes('regulasi') ||
    m.includes('aturan') ||
    m.includes('pasal') ||
    m.includes('dilanggar')
  ) {
    if (context?.category && BOB_KB[context.category]) return BOB_KB[context.category];
    return 'Untuk melihat regulasi yang relevan, lihat detail di halaman hasil analisis.';
  }
  if (context && (m.includes('hasil') || m.includes('analisis') || m.includes('maksud'))) {
    const cat = context.category;
    if (BOB_KB[cat])
      return `Berdasarkan hasil analisis bukti kamu (kategori: **${cat}**):\n\n${BOB_KB[cat]}`;
  }
  return BOB_KB.default;
}

export default function BobPage() {
  const searchParams = useSearchParams();
  const analysisId = searchParams.get('analysis_id');

  const [messages, setMessages] = useState<Message[]>([]);
  const [input, setInput] = useState('');
  const [loading, setLoading] = useState(false);
  const [context, setContext] = useState<AnalysisResult | null>(null);

  useEffect(() => {
    let welcome = `Halo! Saya **Bob**, asisten analisis BuktiTagih.\n\nSaya di sini untuk membantu kamu memahami hasil analisis dan menjelaskan langkah selanjutnya yang bisa kamu ambil.`;

    const loadContext = async () => {
      if (analysisId) {
        try {
          const data = await apiService.getAnalysis(parseInt(analysisId));
          setContext(data);
          if (data.category && data.category !== 'PENDING') {
            welcome += `\n\nSaya sudah membaca hasil analisis: bukti diidentifikasi sebagai **${data.category}**. Mau saya jelaskan lebih lanjut?`;
          } else {
            welcome += `\n\nHasil analisis masih diproses. Kamu tetap bisa bertanya seputar kategori pelanggaran atau langkah tindak lanjut.`;
          }
        } catch (_) {}
      } else {
        welcome += `\n\nKamu bisa tanya tentang kategori pelanggaran, regulasi yang berlaku, atau cara melaporkan ke OJK dan AFPI.`;
      }

      setMessages([{ role: 'bob', text: welcome }]);
    };

    loadContext();
  }, [analysisId]);

  const handleSend = async () => {
    const msg = input.trim();
    if (!msg) return;

    setInput('');
    setMessages((prev) => [...prev, { role: 'user', text: msg }]);
    setLoading(true);

    // Simulate typing delay
    await new Promise((r) => setTimeout(r, 600 + Math.random() * 500));

    const reply = getBobReply(msg, context || undefined);
    setMessages((prev) => [...prev, { role: 'bob', text: reply }]);
    setLoading(false);
  };

  const quickReplies = [
    'Apa itu HARASSMENT?',
    'Langkah selanjutnya?',
    'Cara lapor ke OJK?',
    'Apa itu DATA_EXPOSURE?',
    'Regulasi yang dilanggar?',
  ];

  return (
    <>
      <nav className={styles.nav}>
        <span className={styles.brand}>
          Bukti<span>Tagih</span>
        </span>
        <div className={styles.navRight}>
          <span className={styles.navLabel}>Bob — Asisten Analisis</span>
          <a href="/" className={styles.btnBack}>
            ← Kembali
          </a>
        </div>
      </nav>

      {analysisId && (
        <div className={styles.contextStrip}>
          Konteks aktif: Analysis <strong>{analysisId}</strong>
          &nbsp;·&nbsp; Kamu sedang mendiskusikan hasil analisis ini bersama Bob.
        </div>
      )}

      <div className={styles.chatWrapper}>
        <div className={styles.messages}>
          {messages.map((msg, i) => (
            <div key={i} className={`${styles.msgRow} ${styles[msg.role]}`}>
              {msg.role !== 'system' && <div className={styles.msgLabel}>{msg.role === 'bob' ? 'Bob' : 'Kamu'}</div>}
              <div
                className={`${styles.bubble} ${
                  msg.role === 'bob'
                    ? styles.bobBubble
                    : msg.role === 'user'
                      ? styles.userBubble
                      : styles.systemBubble
                }`}
                dangerouslySetInnerHTML={{
                  __html: msg.text
                    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\n/g, '<br>'),
                }}
              />
            </div>
          ))}
          {loading && (
            <div className={`${styles.msgRow} ${styles.bob}`}>
              <div className={styles.msgLabel}>Bob</div>
              <div className={`${styles.bubble} ${styles.bobBubble}`}>
                <span className={styles.typingDots}>
                  <span></span>
                  <span></span>
                  <span></span>
                </span>
              </div>
            </div>
          )}
        </div>

        <div className={styles.inputArea}>
          <div className={styles.quickReplies}>
            {quickReplies.map((reply, i) => (
              <button
                key={i}
                type="button"
                className={styles.qrBtn}
                onClick={() => {
                  setInput(reply);
                  setTimeout(() => handleSend(), 100);
                }}
              >
                {reply}
              </button>
            ))}
          </div>
          <div className={styles.inputRow}>
            <textarea
              className={styles.textarea}
              rows={2}
              placeholder="Tanyakan tentang hasil analisis atau situasimu…"
              maxLength={1000}
              value={input}
              onChange={(e) => setInput(e.target.value)}
              onKeyDown={(e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                  e.preventDefault();
                  handleSend();
                }
              }}
            />
            <button
              type="button"
              className={styles.btnSend}
              onClick={handleSend}
              disabled={loading || !input.trim()}
            >
              Kirim
            </button>
          </div>
          <div className={styles.inputHint}>Bob adalah asisten AI — bukan pengganti nasihat hukum profesional.</div>
        </div>
      </div>
    </>
  );
}
