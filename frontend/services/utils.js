/**
 * Utility functions untuk frontend BuktiTagih (JavaScript version)
 */

/**
 * Format bytes ke readable format (B, KB, MB)
 */
export function formatBytes(bytes) {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / 1048576).toFixed(1) + ' MB';
}

/**
 * Dapatkan atau generate session user ID
 * Disimpan di sessionStorage untuk identifikasi anonymous user
 */
export function getSessionUserId() {
  const key = 'buktitagih_uid';
  let uid = sessionStorage.getItem(key);
  
  if (!uid) {
    uid = crypto.randomUUID();
    sessionStorage.setItem(key, uid);
  }
  
  return uid;
}

/**
 * Tentukan icon file berdasarkan extension
 */
export function getFileIcon(filename) {
  if (/\.(jpg|jpeg|png)$/i.test(filename)) return '🖼️';
  if (/\.pdf$/i.test(filename)) return '📑';
  return '📄';
}

/**
 * Map kategori AI ke label Indonesia
 */
export const CATEGORY_LABELS = {
  HARASSMENT: 'Intimidasi',
  THREAT: 'Ancaman',
  DATA_EXPOSURE: 'Kebocoran Data',
  SPAM: 'Spam',
  NORMAL: 'Normal',
  PENDING: 'Menunggu Proses AI',
};

/**
 * Map severity ke label Indonesia
 */
export const SEVERITY_LABELS = {
  HIGH: 'Tinggi',
  MEDIUM: 'Sedang',
  LOW: 'Rendah',
  PENDING: 'Pending',
};

/**
 * Map severity ke CSS class
 */
export const SEVERITY_CLASSES = {
  HIGH: 'high',
  MEDIUM: 'medium',
  LOW: 'low',
  PENDING: 'pending',
};

/**
 * Delay helper untuk simulasi loading
 */
export function delay(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Parse URL search params dengan type safety
 */
export function getUrlParam(key, defaultValue) {
  if (typeof window === 'undefined') return defaultValue ?? null;
  const params = new URLSearchParams(window.location.search);
  return params.get(key) ?? defaultValue ?? null;
}
