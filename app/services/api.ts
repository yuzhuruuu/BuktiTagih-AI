const API_BASE = process.env.NEXT_PUBLIC_API_BASE || 'http://127.0.0.1:8000/api';

export interface UploadResponse {
  evidence_id: number;
  upload_status: string;
  ai_process?: any;
}

export interface AnalysisResult {
  analysis_id: number;
  evidence_id?: number;
  category: string;
  severity: string;
  reason?: string;
  confidence: number;
  regulation_reference?: string;
  evidence?: any;
}

export interface Evidence {
  evidence_id: number;
  file_name: string;
  file_type: string;
  upload_time: string;
  hash_file: string;
}

class ApiService {
  private getSessionUserId(): string {
    if (typeof window === 'undefined') return '';
    let uid = sessionStorage.getItem('buktitagih_uid');
    if (!uid) {
      uid = crypto.randomUUID();
      sessionStorage.setItem('buktitagih_uid', uid);
    }
    return uid;
  }

  async uploadEvidence(file: File): Promise<UploadResponse> {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('user_id', this.getSessionUserId());

    const response = await fetch(`${API_BASE}/evidence/upload`, {
      method: 'POST',
      body: formData,
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || `HTTP ${response.status}`);
    }

    return response.json();
  }

  async getAnalysisByEvidence(evidenceId: number): Promise<AnalysisResult> {
    const response = await fetch(`${API_BASE}/analysis/by-evidence/${evidenceId}`);

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || `HTTP ${response.status}`);
    }

    return response.json();
  }

  async getAnalysis(analysisId: number): Promise<AnalysisResult> {
    const response = await fetch(`${API_BASE}/analysis/${analysisId}`);

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || `HTTP ${response.status}`);
    }

    return response.json();
  }

  async downloadPdfReport(evidenceId: number): Promise<Blob> {
    const response = await fetch(`${API_BASE}/report/${evidenceId}`);

    if (!response.ok) {
      throw new Error(`Failed to download report: HTTP ${response.status}`);
    }

    return response.blob();
  }

  getPdfReportUrl(evidenceId: number): string {
    return `${API_BASE}/report/${evidenceId}`;
  }
}

export const apiService = new ApiService();
