/**
 * Regulation Reference Mapping
 * Simplified RAG: hardcoded regulation references per category
 * 
 * Digunakan di AiAnalysisService untuk enrich response dengan regulation
 */

export const REGULATION_REFERENCES = {
  HARASSMENT: [
    {
      law: "POJK No. 22/POJK.07/2020",
      article: "Perlindungan Konsumen Sektor Jasa Keuangan",
      note: "Larangan perilaku intimidatif dan merendahkan martabat dalam penagihan"
    },
    {
      law: "AFPI Code of Conduct",
      article: "Standar Etika Penagihan",
      note: "Penagihan hanya boleh dilakukan dengan cara yang hormat dan profesional"
    }
  ],
  THREAT: [
    {
      law: "KUHP",
      article: "Pasal 335",
      note: "Perbuatan yang menyebabkan ketakutan atau kecemasan bagi orang lain"
    },
    {
      law: "UU ITE",
      article: "Pasal 29",
      note: "Ancaman kekerasan melalui media elektronik"
    },
    {
      law: "POJK No. 22/POJK.07/2020",
      article: "Perlindungan Konsumen",
      note: "Larangan penggunaan kekerasan atau ancaman dalam penagihan"
    }
  ],
  DATA_EXPOSURE: [
    {
      law: "UU No. 27 Tahun 2022",
      article: "Perlindungan Data Pribadi (UU PDP)",
      note: "Perlindungan data pribadi termasuk identitas, lokasi, dan informasi sensitif"
    },
    {
      law: "POJK No. 1/POJK.07/2013",
      article: "Kerahasiaan Data Konsumen",
      note: "Lembaga jasa keuangan wajib menjaga kerahasiaan data konsumen"
    }
  ],
  SPAM: [
    {
      law: "AFPI Code of Conduct",
      article: "Pembatasan Frekuensi Kontak",
      note: "Penagihan hanya boleh dilakukan pada jam yang wajar dan tidak berlebihan"
    },
    {
      law: "UU ITE",
      article: "Pasal 27",
      note: "Larangan penggunaan sistem informasi untuk mengganggu atau membuat malu"
    }
  ],
  NORMAL: [
    {
      law: "Tidak ada indikasi pelanggaran",
      article: "Proses penagihan normal",
      note: "Bukti yang diupload tidak menunjukkan tanda-tanda pelanggaran regulasi"
    }
  ]
};

/**
 * Get regulation references untuk category tertentu
 */
export function getRegulationReference(category) {
  return REGULATION_REFERENCES[category] || REGULATION_REFERENCES.NORMAL;
}
