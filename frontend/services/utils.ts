/**
 * Utility functions untuk frontend BuktiTagih
 */

/**
 * Format bytes ke readable format (B, KB, MB)
 */
export function formatBytes(bytes: number): string {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / 1048576).toFixed(1) + ' MB';
}

/**
 * Dapatkan atau generate session user ID
 * Disimpan di sessionStorage untuk identifikasi anonymous user
 */
export function getSessionUserId(): string {
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
export function getFileIcon(filename: string): string {
  if (/\.(jpg|jpeg|png)$/i.test(filename)) return '🖼️';
  if (/\.pdf$/i.test(filename)) return '📑';
  return '📄';
}

/**
 * Map kategori AI ke label Indonesia
 */
export const CATEGORY_LABELS: Record<string, string> = {
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
export const SEVERITY_LABELS: Record<string, string> = {
  HIGH: 'Tinggi',
  MEDIUM: 'Sedang',
  LOW: 'Rendah',
  PENDING: 'Pending',
};

/**
 * Map severity ke CSS class
 */
export const SEVERITY_CLASSES: Record<string, string> = {
  HIGH: 'high',
  MEDIUM: 'medium',
  LOW: 'low',
  PENDING: 'pending',
};

/**
 * Delay helper untuk simulasi loading
 */
export function delay(ms: number): Promise<void> {
  return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Parse URL search params dengan type safety
 */
export function getUrlParam(key: string, defaultValue?: string): string | null {
  if (typeof window === 'undefined') return defaultValue ?? null;
  const params = new URLSearchParams(window.location.search);
  return params.get(key) ?? defaultValue ?? null;
}
