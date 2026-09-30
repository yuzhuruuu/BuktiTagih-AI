/**
 * API Service Layer untuk BuktiTagih Frontend (JavaScript version)
 * Gunakan di vanilla HTML dengan: import { uploadEvidence, ... } from './api.js'
 */

const API_BASE = 'http://127.0.0.1:8000/api';

// ─────────────────────────────────────────────────────────────────────────
// Internal Helper
// ─────────────────────────────────────────────────────────────────────────

async function fetchWithErrorHandling(endpoint, options = {}) {
  try {
    const response = await fetch(endpoint, {
      headers: {
        'Accept': 'application/json',
        ...options.headers,
      },
      ...options,
    });

    const data = await response.json();

    if (!response.ok) {
      throw {
        message: data.message || `HTTP ${response.status}`,
        status: response.status,
      };
    }

    return data;
  } catch (error) {
    if (error instanceof TypeError) {
      throw {
        message: 'Gagal terhubung ke server. Periksa koneksi internet.',
        status: 0,
      };
    }
    throw error;
  }
}

// ─────────────────────────────────────────────────────────────────────────
// Evidence APIs
// ─────────────────────────────────────────────────────────────────────────

export async function uploadEvidence(file, userId) {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('user_id', userId);

  return fetchWithErrorHandling(`${API_BASE}/evidence/upload`, {
    method: 'POST',
    body: formData,
  });
}

export async function listEvidence() {
  const response = await fetchWithErrorHandling(`${API_BASE}/evidence`);
  return response.data || [];
}

export async function getEvidence(evidenceId) {
  const response = await fetchWithErrorHandling(`${API_BASE}/evidence/${evidenceId}`);
  return response.data;
}

// ─────────────────────────────────────────────────────────────────────────
// Analysis APIs
// ─────────────────────────────────────────────────────────────────────────

export async function startAnalysis(evidenceId) {
  return fetchWithErrorHandling(`${API_BASE}/analysis/start`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ evidence_id: evidenceId }),
  });
}

export async function getAnalysis(analysisId) {
  return fetchWithErrorHandling(`${API_BASE}/analysis/${analysisId}`);
}

export async function getAnalysisByEvidence(evidenceId) {
  return fetchWithErrorHandling(`${API_BASE}/analysis/by-evidence/${evidenceId}`);
}

// ─────────────────────────────────────────────────────────────────────────
// PDF Report APIs
// ─────────────────────────────────────────────────────────────────────────

export async function downloadPdfReport(evidenceId, filename) {
  const url = `${API_BASE}/report/${evidenceId}`;
  const link = document.createElement('a');
  link.href = url;
  link.download = filename || `BuktiTagih_Laporan_${evidenceId}.pdf`;
  link.click();
}

export function getPdfReportUrl(evidenceId) {
  return `${API_BASE}/report/${evidenceId}`;
}

// ─────────────────────────────────────────────────────────────────────────
// Utility Methods
// ─────────────────────────────────────────────────────────────────────────

export async function checkBackendHealth() {
  try {
    const response = await fetch(API_BASE, { method: 'HEAD' });
    return response.ok || response.status === 405;
  } catch {
    return false;
  }
}

export function getApiBase() {
  return API_BASE;
}
