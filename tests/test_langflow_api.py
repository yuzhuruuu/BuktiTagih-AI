# @Author:xxx
# @Date:2026-09-27 22:27:39
# @LastModifiedBy:xxx
# @Last Modified time:2026-09-27 22:30:06
import requests
import os
import uuid

api_key = os.environ["LANGFLOW_API_KEY"]
flow_id = "e05721f1-c3a2-4a33-bbd2-d30dee3df995"
img_path = r"D:\HBB\BuktiTagih-AI\screenshotstes\testeks1.jpg"   # ganti sesuai lokasi gambar di laptopmu

headers = {"x-api-key": api_key}

# 1) upload gambar dulu, dapat path yang dikenali Langflow
with open(img_path, "rb") as f:
    up = requests.post(
        f"http://127.0.0.1:7860/api/v1/files/upload/{flow_id}",
        headers=headers,
        files={"file": f}
    )
print("Upload status:", up.status_code)
print("Upload response:", up.text)

up.raise_for_status()
file_path = up.json()["file_path"]   # <- ini yang otomatis jadi PATH_HASIL_UPLOAD
print("File path dari Langflow:", file_path)

# 2) jalankan flow, pakai file_path hasil upload
payload = {
    "output_type": "chat",
    "input_type": "chat",
    "input_value": "Analisis bukti terlampir.",
    "session_id": str(uuid.uuid4()),
    "tweaks": {
        "ChatInput-aPEX5": {
            "files": file_path
        }
    }
}

r = requests.post(
    f"http://127.0.0.1:7860/api/v1/run/{flow_id}",
    json=payload,
    headers=headers
)
print("Run status:", r.status_code)
print(r.text)