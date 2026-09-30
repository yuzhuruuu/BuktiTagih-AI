/**
 * Migration Guide: Integrating Service Layer into Existing HTML Pages
 * 
 * Bagaimana cara mengganti inline API calls dengan service layer yang tersentralisir
 */

// ═════════════════════════════════════════════════════════════════════════════
// FILE: upload.html
// ═════════════════════════════════════════════════════════════════════════════

// BEFORE (inline script):
// ────────────────────────────────────────────────────────────────────────────
/*
const API_BASE = 'http://127.0.0.1:8000/api';

function formatBytes(b) {
    if (b < 1024) return b + ' B';
    if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
    return (b / 1048576).toFixed(1) + ' MB';
}

function getSessionUserId() {
    let uid = sessionStorage.getItem('buktitagih_uid');
    if (!uid) { uid = crypto.randomUUID(); sessionStorage.setItem('buktitagih_uid', uid); }
    return uid;
}

document.getElementById('uploadForm').addEventListener('submit', async e => {
    e.preventDefault();
    const file = fileInput._file;
    if (!file) return;

    submitBtn.disabled = true;
    setProgress(20, 'Mengunggah file…');

    try {
        const fd = new FormData();
        fd.append('file', file);
        fd.append('user_id', getSessionUserId());

        const res = await fetch(`${API_BASE}/evidence/upload`, { 
            method: 'POST', 
            body: fd 
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || `HTTP ${res.status}`);

        setProgress(100, 'Selesai!');
        // ... rest of handling
    } catch (err) {
        // error handling
    }
});
*/

// AFTER (using service layer):
// ────────────────────────────────────────────────────────────────────────────

import { uploadEvidence } from './services/api.js';
import { getSessionUserId } from './services/utils.js';

document.getElementById('uploadForm').addEventListener('submit', async e => {
    e.preventDefault();
    const file = fileInput._file;
    if (!file) return;

    submitBtn.disabled = true;
    setProgress(20, 'Mengunggah file…');

    try {
        const data = await uploadEvidence(file, getSessionUserId());

        setProgress(100, 'Selesai!');
        
        resEvidenceId.textContent = data.evidence_id;
        const analysisId = data.ai_process?.analysis_id ?? '';
        goToResult.href = `result.html?evidence_id=${data.evidence_id}${
            analysisId ? '&analysis_id=' + analysisId : ''
        }`;
        successAlert.classList.add('show');
        
    } catch (err) {
        progressWrap.style.display = 'none';
        errorMsg.textContent = err.message || 'Kesalahan yang tidak diketahui.';
        errorAlert.classList.add('show');
        statusArea.style.display = 'block';
        submitBtn.disabled = false;
    }
});

// KEY BENEFITS:
// ✓ Inline API_BASE constant dihapus → centralized di api.js
// ✓ formatBytes() & getSessionUserId() jadi import → reusable
// ✓ Error handling sudah standardized → konsisten di semua pages
// ✓ FormData handling tersembunyi → logic lebih clean
// ✓ Easier to test dan mock

// ═════════════════════════════════════════════════════════════════════════════
// FILE: result.html
// ═════════════════════════════════════════════════════════════════════════════

// BEFORE (inline script):
/*
const API_BASE = 'http://127.0.0.1:8000/api';
const params = new URLSearchParams(window.location.search);
const analysisId = params.get('analysis_id');
const evidenceId = params.get('evidence_id');

async function loadAnalysis() {
    if (!analysisId && !evidenceId) {
        showError('Parameter tidak ditemukan di URL. Kembali ke halaman upload.');
        return;
    }

    try {
        const url = analysisId
            ? `${API_BASE}/analysis/${analysisId}`
            : `${API_BASE}/analysis/by-evidence/${evidenceId}`;

        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || `HTTP ${res.status}`);

        // Parse & render data
        const cat = (data.category || 'PENDING').toUpperCase();
        // ... manual rendering logic
    } catch (err) {
        showError(err.message || 'Gagal mengambil data dari server.');
    }
}
*/

// AFTER (using service layer):
import { 
    getAnalysis, 
    getAnalysisByEvidence,
    downloadPdfReport
} from './services/api.js';
import { 
    getUrlParam, 
    CATEGORY_LABELS, 
    SEVERITY_LABELS 
} from './services/utils.js';

const analysisId = getUrlParam('analysis_id');
const evidenceId = getUrlParam('evidence_id');

async function loadAnalysis() {
    if (!analysisId && !evidenceId) {
        showError('Parameter tidak ditemukan di URL. Kembali ke halaman upload.');
        return;
    }

    try {
        let data;
        
        if (analysisId) {
            data = await getAnalysis(parseInt(analysisId));
        } else {
            data = await getAnalysisByEvidence(parseInt(evidenceId));
        }

        // Simple rendering dengan pre-built labels
        const cat = (data.category || 'PENDING').toUpperCase();
        const sev = (data.severity || 'PENDING').toUpperCase();
        
        document.getElementById('categoryBadge').textContent = CATEGORY_LABELS[cat] || cat;
        document.getElementById('severityBadge').textContent = SEVERITY_LABELS[sev] || sev;
        
        // Setup download
        document.getElementById('downloadBtn').onclick = (e) => {
            e.preventDefault();
            downloadPdfReport(data.evidence_id);
        };
        
        showResult();
        
    } catch (err) {
        showError(err.message);
    }
}

loadAnalysis();

// KEY IMPROVEMENTS:
// ✓ URL params parsing centralized (getUrlParam helper)
// ✓ API_BASE constant removed
// ✓ Error handling automatic
// ✓ Category/Severity labels imported → no duplication
// ✓ PDF download abstracted to function call
// ✓ Fetch error handling standardized

// ═════════════════════════════════════════════════════════════════════════════
// FILE: bob.html
// ═════════════════════════════════════════════════════════════════════════════

// BEFORE (inline script):
/*
const API_BASE = 'http://127.0.0.1:8000/api';
const params = new URLSearchParams(window.location.search);
const analysisId = params.get('analysis_id');

async function init() {
    let welcome = `Halo! Saya Bob...`;
    
    if (analysisId) {
        try {
            const res = await fetch(`${API_BASE}/analysis/${analysisId}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (res.ok) {
                analysisContext = await res.json();
                // ... logic
            }
        } catch (_) {}
    }
    
    addMessage('bob', welcome);
}
*/

// AFTER (using service layer):
import { getAnalysis } from './services/api.js';
import { getUrlParam } from './services/utils.js';

const analysisId = getUrlParam('analysis_id');

async function init() {
    let welcome = `Halo! Saya Bob...`;
    
    if (analysisId) {
        try {
            analysisContext = await getAnalysis(parseInt(analysisId));
            const cat = analysisContext.category;
            if (cat && cat !== 'PENDING') {
                welcome += `\n\nSaya sudah membaca hasil analisis: **${cat}**. Mau saya jelaskan?`;
            }
        } catch (error) {
            console.error('Failed to load context:', error);
        }
    }
    
    addMessage('bob', welcome);
}

init();

// KEY IMPROVEMENTS:
// ✓ Cleaner error handling
// ✓ No manual fetch() call
// ✓ URL parsing centralized
// ✓ Fewer variables to track (no `params`, no `res`)

// ═════════════════════════════════════════════════════════════════════════════
// STEP-BY-STEP MIGRATION CHECKLIST
// ═════════════════════════════════════════════════════════════════════════════

/*
✓ 1. Add imports di atas <script> tag:
     import { uploadEvidence, getAnalysis, ... } from './services/api.js';
     import { formatBytes, getUrlParam, ... } from './services/utils.js';

✓ 2. Remove constant definitions:
     - const API_BASE = '...'
     - Remove formatBytes() function
     - Remove getSessionUserId() function
     - Remove CATEGORY_LABELS object
     - Remove SEVERITY_LABELS object

✓ 3. Replace fetch() calls dengan service function calls:
     OLD: await fetch(`${API_BASE}/evidence/upload`, { ... })
     NEW: await uploadEvidence(file, userId)

✓ 4. Replace URL param parsing:
     OLD: const params = new URLSearchParams(window.location.search);
          const id = params.get('id');
     NEW: const id = getUrlParam('id');

✓ 5. Update error handling:
     OLD: if (!res.ok) throw new Error(data.message || `HTTP ${res.status}`);
     NEW: catch (err) { /* err already has .message & .status */ }

✓ 6. Use pre-built label mappings:
     OLD: CATEGORY_LABEL[cat] defined in HTML
     NEW: import CATEGORY_LABELS from utils

✓ 7. Test di browser console:
     - Buka DevTools (F12)
     - Test functionality step-by-step
     - Check network tab untuk API calls
*/

// ═════════════════════════════════════════════════════════════════════════════
// EXPECTED DIRECTORY STRUCTURE AFTER MIGRATION
// ═════════════════════════════════════════════════════════════════════════════

/*
frontend/
├── pages/
│   ├── upload.html       (refactored with imports)
│   ├── result.html       (refactored with imports)
│   └── bob.html          (refactored with imports)
├── services/
│   ├── index.ts          (barrel export)
│   ├── api.ts            (TypeScript version)
│   ├── utils.ts          (TypeScript version)
│   ├── api.js            (JavaScript version) ← use this for HTML
│   ├── utils.js          (JavaScript version) ← use this for HTML
│   └── README.md         (this file)
└── assets/
    ├── styles.css
    └── ...
*/

// ═════════════════════════════════════════════════════════════════════════════
// ENVIRONMENT-SPECIFIC CONFIGURATION
// ═════════════════════════════════════════════════════════════════════════════

/*
Untuk development vs production, bisa customize API_BASE:

// di api.js, tambah function:
export function setApiBase(newBase) {
    // Store di variable untuk di-override
}

// di top level script sebelum import services:
<script>
    // Override untuk production
    if (window.location.hostname !== 'localhost') {
        window.API_BASE = 'https://api.buktitagih.id/api';
    }
</script>

// atau gunakan environment variable di build time
*/
