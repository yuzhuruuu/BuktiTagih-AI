/**
 * Integration guide untuk API service layer
 * Cara menggunakan api.ts dan utils.ts di HTML pages
 */

// ═════════════════════════════════════════════════════════════════════════════
// SETUP (di setiap HTML page)
// ═════════════════════════════════════════════════════════════════════════════

// Import functions yang dibutuhkan
import {
  uploadEvidence,
  getAnalysisByEvidence,
  getAnalysis,
  downloadPdfReport,
  type UploadResponse,
  type AnalysisResponse,
} from './api.ts';

import {
  formatBytes,
  getSessionUserId,
  getFileIcon,
  CATEGORY_LABELS,
  SEVERITY_LABELS,
  getUrlParam,
} from './utils.ts';

// ═════════════════════════════════════════════════════════════════════════════
// CONTOH 1: Upload Page (upload.html)
// ═════════════════════════════════════════════════════════════════════════════

// Setup form handler
const uploadForm = document.getElementById('uploadForm') as HTMLFormElement;
const fileInput = document.getElementById('evidenceFile') as HTMLInputElement;
const submitBtn = document.getElementById('submitBtn') as HTMLButtonElement;

uploadForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const file = fileInput.files?.[0];
  if (!file) return;

  submitBtn.disabled = true;
  
  try {
    // Get atau generate session user ID
    const userId = getSessionUserId();
    
    // Upload file
    const result: UploadResponse = await uploadEvidence(file, userId);
    
    // Handle response
    const evidenceId = result.evidence_id;
    const analysisId = result.ai_process?.analysis_id;
    
    // Redirect ke result page
    const resultUrl = `result.html?evidence_id=${evidenceId}${
      analysisId ? `&analysis_id=${analysisId}` : ''
    }`;
    window.location.href = resultUrl;
    
  } catch (error: any) {
    console.error('Upload failed:', error);
    alert(`Error: ${error.message}`);
    submitBtn.disabled = false;
  }
});

// ═════════════════════════════════════════════════════════════════════════════
// CONTOH 2: Result Page (result.html)
// ═════════════════════════════════════════════════════════════════════════════

// Parse URL params
const analysisId = getUrlParam('analysis_id');
const evidenceId = getUrlParam('evidence_id');

async function loadAnalysis() {
  if (!analysisId && !evidenceId) {
    showError('Parameter tidak ditemukan');
    return;
  }

  try {
    // Fetch analysis result
    let analysis: AnalysisResponse;
    
    if (analysisId) {
      analysis = await getAnalysis(parseInt(analysisId));
    } else {
      analysis = await getAnalysisByEvidence(parseInt(evidenceId));
    }
    
    // Render hasil
    const categoryLabel = CATEGORY_LABELS[analysis.category] || analysis.category;
    const severityLabel = SEVERITY_LABELS[analysis.severity] || analysis.severity;
    
    document.getElementById('categoryBadge')!.textContent = categoryLabel;
    document.getElementById('severityBadge')!.textContent = severityLabel;
    document.getElementById('confidencePct')!.textContent = 
      Math.round(parseFloat(String(analysis.confidence))) + '%';
    document.getElementById('reasonBox')!.textContent = 
      analysis.reason || 'Menunggu proses AI...';
    
    // Setup download button
    const downloadBtn = document.getElementById('downloadBtn') as HTMLAnchorElement;
    downloadBtn.onclick = (e) => {
      e.preventDefault();
      downloadPdfReport(analysis.evidence_id);
    };
    
    showResult();
    
  } catch (error: any) {
    console.error('Load analysis failed:', error);
    showError(error.message);
  }
}

// Call on page load
loadAnalysis();

// ═════════════════════════════════════════════════════════════════════════════
// CONTOH 3: Chat Page (bob.html)
// ═════════════════════════════════════════════════════════════════════════════

const chatAnalysisId = getUrlParam('analysis_id');

async function initChat() {
  if (!chatAnalysisId) {
    console.warn('No analysis_id provided');
    return;
  }

  try {
    const analysis = await getAnalysis(parseInt(chatAnalysisId));
    
    // Use analysis data untuk context Bob
    console.log('Chat context:', analysis);
    
    // Bisa ditampilkan ke user
    document.getElementById('ctxAnalysisId')!.textContent = chatAnalysisId;
    document.querySelector('.context-strip')?.classList.add('show');
    
  } catch (error: any) {
    console.error('Failed to load chat context:', error);
  }
}

initChat();

// ═════════════════════════════════════════════════════════════════════════════
// REFERENCE: Error Handling Pattern
// ═════════════════════════════════════════════════════════════════════════════

/*
Error object dari API service:
{
  message: string,    // Human-readable error message
  status: number      // HTTP status code (0 = network error)
}

Contoh handling:
*/

try {
  const result = await uploadEvidence(file, userId);
} catch (error: any) {
  if (error.status === 0) {
    // Network error
    console.error('Cannot reach server');
  } else if (error.status === 400) {
    // Validation error
    console.error('Invalid request:', error.message);
  } else if (error.status === 404) {
    // Not found
    console.error('Resource not found:', error.message);
  } else if (error.status === 500) {
    // Server error
    console.error('Server error:', error.message);
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// REFERENCE: Type Safety
// ═════════════════════════════════════════════════════════════════════════════

/*
Jika menggunakan TypeScript, semua types sudah tersedia di api.ts:

- Evidence           : Record bukti (file metadata)
- AiAnalysis         : Record analisis (hasil AI)
- AnalysisResponse   : Analysis dengan evidence embedded
- UploadResponse     : Response dari upload endpoint
- AiEntity           : Extracted entity dari document

Type hints membantu IDE autocomplete dan catch errors lebih awal.
*/

// ═════════════════════════════════════════════════════════════════════════════
// REFERENCE: Migrating dari inline script
// ═════════════════════════════════════════════════════════════════════════════

/*
Old way (inline di HTML script tag):
const API_BASE = 'http://127.0.0.1:8000/api';
const res = await fetch(API_BASE + '/evidence/upload', { method: 'POST', body: fd });

New way (using service layer):
import { uploadEvidence } from './api.ts';
const res = await uploadEvidence(file, userId);

Benefits:
✓ Centralized API management
✓ Type safety (TypeScript)
✓ Error handling standardized
✓ Easy to test and mock
✓ Environment-specific config (dev, staging, prod)
*/
