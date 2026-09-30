/**
 * API Service Layer untuk BuktiTagih Frontend
 * Centralize semua komunikasi dengan backend API
 */

const API_BASE = 'http://127.0.0.1:8000/api';

// ─────────────────────────────────────────────────────────────────────────
// Type Definitions
// ─────────────────────────────────────────────────────────────────────────

export interface Evidence {
  evidence_id: number;
  user_id: string;
  file_name: string;
  file_type: string;
  hash_file: string;
  file: string;
  upload_time: string;
  created_at: string;
  updated_at: string;
}

export interface AiAnalysis {
  analysis_id: number;
  evidence_id: number;
  category: 'HARASSMENT' | 'THREAT' | 'DATA_EXPOSURE' | 'SPAM' | 'NORMAL' | 'PENDING';
  severity: 'HIGH' | 'MEDIUM' | 'LOW' | 'PENDING';
  reason: string | null;
  regulation_reference: string | null;
  confidence: number;
  evidence?: Evidence;
  created_at?: string;
  updated_at?: string;
}

export interface AiEntity {
  entity_type: string;
  entity_value: string;
  confidence?: number;
}

export interface UploadResponse {
  evidence_id: number;
  upload_status: string;
  ai_process: {
    status: string;
    message?: string;
    category?: string;
    severity?: string;
    reason?: string;
    confidence?: number;
    regulation_reference?: string;
    entities?: AiEntity[];
    analysis_id?: number;
  };
}

export interface AnalysisResponse extends AiAnalysis {
  analysis_id: number;
  category: string;
  severity: string;
  reason: string;
  evidence: Evidence;
  regulation_reference: string;
  confidence: number;
}

export interface ApiError {
  message: string;
  status: number;
}

// ─────────────────────────────────────────────────────────────────────────
// Internal Helper
// ─────────────────────────────────────────────────────────────────────────

async function fetchWithErrorHandling<T>(
  endpoint: string,
  options?: RequestInit
): Promise<T> {
  try {
    const response = await fetch(endpoint, {
      headers: {
        'Accept': 'application/json',
        ...options?.headers,
      },
      ...options,
    });

    const data = await response.json();

    if (!response.ok) {
      throw {
        message: data.message || `HTTP ${response.status}`,
        status: response.status,
      } as ApiError;
    }

    return data as T;
  } catch (error) {
    if (error instanceof TypeError) {
      // Network error
      throw {
        message: 'Gagal terhubung ke server. Periksa koneksi internet.',
        status: 0,
      } as ApiError;
    }
    throw error;
  }
}

// ─────────────────────────────────────────────────────────────────────────
// Public API Methods
// ─────────────────────────────────────────────────────────────────────────

/**
 * Upload file bukti penagihan
 * @param file File yang akan diupload
 * @param userId User ID (biasanya dari sessionStorage)
 * @returns Upload response dengan evidence_id dan AI analysis result
 */
export async function uploadEvidence(
  file: File,
  userId: string
): Promise<UploadResponse> {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('user_id', userId);

  return fetchWithErrorHandling<UploadResponse>(
    `${API_BASE}/evidence/upload`,
    {
      method: 'POST',
      body: formData,
      // Jangan set Content-Type, browser akan set multipart/form-data auto
    }
  );
}

/**
 * Dapatkan list semua evidence yang sudah diupload
 * @returns Array of evidence records
 */
export async function listEvidence(): Promise<Evidence[]> {
  const response = await fetchWithErrorHandling<{
    status: string;
    data: Evidence[];
  }>(`${API_BASE}/evidence`);

  return response.data || [];
}

/**
 * Dapatkan detail evidence berdasarkan ID
 * @param evidenceId Evidence ID
 * @returns Evidence record
 */
export async function getEvidence(evidenceId: number): Promise<Evidence> {
  const response = await fetchWithErrorHandling<{
    status: string;
    data: Evidence;
  }>(`${API_BASE}/evidence/${evidenceId}`);

  return response.data;
}

/**
 * Mulai proses analisis untuk suatu evidence
 * (Trigger Langflow AI pipeline)
 * @param evidenceId Evidence ID yang akan dianalisis
 * @returns Analysis record dengan status PENDING
 */
export async function startAnalysis(evidenceId: number): Promise<AiAnalysis> {
  return fetchWithErrorHandling<AiAnalysis>(
    `${API_BASE}/analysis/start`,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ evidence_id: evidenceId }),
    }
  );
}

/**
 * Dapatkan hasil analisis berdasarkan analysis ID
 * @param analysisId Analysis ID
 * @returns Analysis record dengan hasil klasifikasi
 */
export async function getAnalysis(analysisId: number): Promise<AnalysisResponse> {
  return fetchWithErrorHandling<AnalysisResponse>(
    `${API_BASE}/analysis/${analysisId}`
  );
}

/**
 * Dapatkan hasil analisis berdasarkan evidence ID
 * Fallback ketika analysis_id tidak diketahui
 * @param evidenceId Evidence ID
 * @returns Latest analysis record untuk evidence tersebut
 */
export async function getAnalysisByEvidence(
  evidenceId: number
): Promise<AnalysisResponse> {
  return fetchWithErrorHandling<AnalysisResponse>(
    `${API_BASE}/analysis/by-evidence/${evidenceId}`
  );
}

/**
 * Download laporan PDF untuk evidence tertentu
 * @param evidenceId Evidence ID
 * @param filename Optional custom filename (default: BuktiTagih_Laporan_[id].pdf)
 */
export async function downloadPdfReport(
  evidenceId: number,
  filename?: string
): Promise<void> {
  const url = `${API_BASE}/report/${evidenceId}`;
  const link = document.createElement('a');
  link.href = url;
  link.download = filename || `BuktiTagih_Laporan_${evidenceId}.pdf`;
  link.click();
}

/**
 * Get download URL untuk PDF report (tanpa auto-download)
 * Berguna untuk iframe embed atau preview
 * @param evidenceId Evidence ID
 * @returns URL string untuk akses PDF
 */
export function getPdfReportUrl(evidenceId: number): string {
  return `${API_BASE}/report/${evidenceId}`;
}

// ─────────────────────────────────────────────────────────────────────────
// Utility Methods
// ─────────────────────────────────────────────────────────────────────────

/**
 * Check konektivitas ke backend
 * @returns true jika backend accessible
 */
export async function checkBackendHealth(): Promise<boolean> {
  try {
    const response = await fetch(API_BASE, { method: 'HEAD' });
    return response.ok || response.status === 405; // 405 = method not allowed, tapi server ok
  } catch {
    return false;
  }
}

/**
 * Get base API URL
 * Useful untuk debugging atau override di environment tertentu
 */
export function getApiBase(): string {
  return API_BASE;
}

/**
 * Configure API base URL (untuk environment berbeda)
 * Call ini sebelum menggunakan API methods
 */
let configuredApiBase = API_BASE;

export function setApiBase(newBase: string): void {
  configuredApiBase = newBase;
}

export function getConfiguredApiBase(): string {
  return configuredApiBase;
}
