"""Diagnosis upload gambar + run flow Langflow (BuktiTagih AI).

Pemakaian (PowerShell):
    $env:LANGFLOW_API_KEY="<key Langflow, sama seperti di backend/.env>"
    $env:LANGFLOW_FLOW_ID="<LANGFLOW_FLOW_ID dari backend/.env>"
    python debug_langflow_upload.py "C:\\path\\ke\\gambar-uji.jpg"

Opsional: LANGFLOW_URL (default http://127.0.0.1:7860), LANGFLOW_CHAT_INPUT_ID.
Skrip tidak menyimpan key dan tidak memuat path gambar apa pun di kode.
"""
import json
import os
import sys

import requests

BASE = os.environ.get("LANGFLOW_URL", "http://127.0.0.1:7860")
API_KEY = os.environ["LANGFLOW_API_KEY"]
FLOW_ID = os.environ["LANGFLOW_FLOW_ID"]
IMG = sys.argv[1] if len(sys.argv) > 1 else None
HEADERS = {"x-api-key": API_KEY}


def short(r, limit=1200):
    try:
        return json.dumps(r.json(), indent=2, ensure_ascii=False)[:limit]
    except Exception:
        return r.text[:limit]


# 1. Cek flow dan cari ID komponen Chat Input di flow ini
chat_ids = []
r = requests.get(f"{BASE}/api/v1/flows/{FLOW_ID}", headers=HEADERS)
print(f"[1] GET flow {FLOW_ID}: HTTP {r.status_code}")
if r.ok:
    nodes = r.json().get("data", {}).get("nodes", [])
    chat_ids = [n["id"] for n in nodes if n.get("data", {}).get("type") == "ChatInput"]
    print("    Chat Input ID di flow ini:", chat_ids or "tidak ditemukan")
else:
    print("    Tidak bisa membaca flow (", short(r, 300), ")")
    print("    Lihat ID Chat Input langsung di Langflow: klik node Chat Input.")

chat_id = os.environ.get("LANGFLOW_CHAT_INPUT_ID") or (chat_ids[0] if chat_ids else None)
if chat_ids and chat_id not in chat_ids:
    print(f"    PERINGATAN: LANGFLOW_CHAT_INPUT_ID={chat_id} tidak ada di flow ini. Pakai: {chat_ids[0]}")
    chat_id = chat_ids[0]
if not chat_id:
    sys.exit("ID Chat Input tidak diketahui. Isi LANGFLOW_CHAT_INPUT_ID dulu.")

if not IMG:
    sys.exit("Beri path gambar sebagai argumen untuk lanjut ke upload dan run.")

# 2. Upload gambar
with open(IMG, "rb") as f:
    up = requests.post(
        f"{BASE}/api/v1/files/upload/{FLOW_ID}",
        headers=HEADERS,
        files={"file": (os.path.basename(IMG), f)},
    )
print(f"[2] Upload: HTTP {up.status_code}\n{short(up)}")
up.raise_for_status()
file_path = up.json().get("file_path") or up.json().get("path")
print("    file_path yang dipakai di tweaks:", file_path)

# 3. Run flow dengan tweaks Chat Input
body = {
    "input_value": "Analisis bukti terlampir.",
    "input_type": "chat",
    "output_type": "chat",
    # input_value cukup di level atas; jangan diulang di tweaks (Langflow menolak dengan HTTP 400)
    "tweaks": {chat_id: {"files": file_path}},
}
print("[3] Body run (tweaks):", json.dumps(body["tweaks"], ensure_ascii=False))
rr = requests.post(
    f"{BASE}/api/v1/run/{FLOW_ID}",
    headers={**HEADERS, "Content-Type": "application/json"},
    json=body,
    timeout=180,
)
print(f"    Run: HTTP {rr.status_code}")
try:
    text = rr.json()["outputs"][0]["outputs"][0]["results"]["message"]["text"]
    print("    Jawaban model:\n", text)
except Exception:
    print(short(rr, 2000))