import json
import os
import re
import time
import uuid

import requests

API_KEY = os.environ["LANGFLOW_API_KEY"]
BASE = "http://127.0.0.1:7860"
FLOW_ID = "e05721f1-c3a2-4a33-bbd2-d30dee3df995"
CHAT_INPUT_ID = "ChatInput-aPEX5"
CASES_PATH = "tests/f1_entity/entity_cases.json"
OUT_PATH = "tests/f1_entity/entity_results.json"

HEADERS = {"x-api-key": API_KEY}

# Hanya tipe ini yang dinilai; tipe lain (key_event, location) diabaikan.
EVAL_TYPES = {"victim", "phone_number", "organization", "actor", "date_time"}
RETRY_STATUS = {429, 500, 502, 503}
# Free tier Gemini = 5 RPM. Jeda antar kasus bisa diubah: $env:CASE_DELAY="45"
CASE_DELAY = int(os.environ.get("CASE_DELAY", "30"))


def normalize(s: str) -> str:
    return re.sub(r"[^a-z0-9]", "", s.lower())


def normalize_phone(s: str) -> str:
    """Samakan format nomor ke bentuk 62xxxxxxxx."""
    d = re.sub(r"\D", "", s)
    if d.startswith("62"):
        return d
    if d.startswith("0"):
        return "62" + d[1:]
    if d.startswith("8"):
        return "62" + d
    return d


def post_run_with_retry(payload):
    """Panggil flow; ulangi sampai 3x kalau 429/5xx (rate limit atau error sementara)."""
    last = None
    for attempt in range(3):
        r = requests.post(f"{BASE}/api/v1/run/{FLOW_ID}", json=payload, headers=HEADERS, timeout=180)
        if r.status_code not in RETRY_STATUS:
            r.raise_for_status()
            return r
        last = r
        if attempt < 2:
            wait = 65  # tunggu jendela 1 menit RPM lewat
            print(f"[HTTP {r.status_code}, coba lagi dalam {wait}s]", end=" ", flush=True)
            time.sleep(wait)
    last.raise_for_status()


def run_image_case(image_path: str):
    with open(image_path, "rb") as f:
        up = requests.post(f"{BASE}/api/v1/files/upload/{FLOW_ID}", headers=HEADERS, files={"file": f})
    up.raise_for_status()
    file_path = up.json()["file_path"]

    payload = {
        "output_type": "chat",
        "input_type": "chat",
        "input_value": "Analisis bukti terlampir.",
        "session_id": str(uuid.uuid4()),
        "tweaks": {CHAT_INPUT_ID: {"files": file_path}},
    }
    r = post_run_with_retry(payload)
    raw = r.json()["outputs"][0]["outputs"][0]["results"]["message"]["text"]

    cleaned = raw.strip()
    if cleaned.startswith("```"):
        cleaned = cleaned.strip("`")
        if "\n" in cleaned:
            cleaned = cleaned.split("\n", 1)[1]
        cleaned = cleaned.rsplit("```", 1)[0]
    return json.loads(cleaned)


def match(expected, actual_list):
    """True kalau ada entity di actual_list dengan tipe sama & value mirip (longgar).

    phone_number dinormalkan ke format 62... dulu supaya beda prefix
    (+62 / 0 / tanpa prefix) tidak dianggap salah.
    """
    etype = expected["entity_type"]
    norm = normalize_phone if etype == "phone_number" else normalize
    exp_norm = norm(expected["entity_value"])
    for a in actual_list:
        if a.get("entity_type") != etype:
            continue
        a_norm = norm(a.get("entity_value", "") or "")
        if exp_norm and a_norm and (exp_norm in a_norm or a_norm in exp_norm):
            return True
    return False


def main():
    with open(CASES_PATH, encoding="utf-8") as f:
        cases = json.load(f)

    results = []
    tp, fn, fp = 0, 0, 0
    errors = 0

    for case in cases:
        cid = case["id"]
        img = case["image_file"]
        expected_entities = case["expected_entities"]
        print(f"[{cid}] {img} ...", end=" ", flush=True)

        try:
            ai = run_image_case(img)
        except Exception as e:
            detail = ""
            resp = getattr(e, "response", None)
            if resp is not None:
                detail = resp.text[:300].replace("\n", " ")
            print(f"ERROR: {e} {detail}")
            # Kasus error dihitung sebagai miss, bukan dilewati diam-diam.
            errors += 1
            fn += len(expected_entities)
            results.append({"id": cid, "error": str(e), "detail": detail,
                            "false_negative": len(expected_entities)})
            time.sleep(CASE_DELAY)
            continue

        actual_entities = [e for e in ai.get("entities", []) if e.get("entity_type") in EVAL_TYPES]

        case_tp = sum(1 for e in expected_entities if match(e, actual_entities))
        case_fn = len(expected_entities) - case_tp
        case_fp = max(len(actual_entities) - case_tp, 0)

        tp += case_tp
        fn += case_fn
        fp += case_fp

        print(f"benar={case_tp}/{len(expected_entities)}, entity_ekstra={case_fp}")

        results.append({
            "id": cid,
            "expected_entities": expected_entities,
            "actual_entities": actual_entities,
            "true_positive": case_tp,
            "false_negative": case_fn,
            "false_positive": case_fp,
        })
        time.sleep(CASE_DELAY)

    precision = tp / (tp + fp) if (tp + fp) else 0
    recall = tp / (tp + fn) if (tp + fn) else 0
    f1 = 2 * precision * recall / (precision + recall) if (precision + recall) else 0

    summary = {"tp": tp, "fn": fn, "fp": fp, "errors": errors, "cases": len(cases),
               "precision": round(precision, 3), "recall": round(recall, 3), "f1": round(f1, 3)}

    with open(OUT_PATH, "w", encoding="utf-8") as f:
        json.dump({"summary": summary, "results": results}, f, indent=2, ensure_ascii=False)

    print(f"\n=== RINGKASAN F1 ENTITY ===")
    print(f"Precision: {precision:.2f}  Recall: {recall:.2f}  F1: {f1:.2f}")
    print(f"Kasus error: {errors}/{len(cases)}")
    if errors:
        print("HASIL TIDAK VALID: ada kasus error. Entity di kasus error dihitung sebagai miss (FN).")
        print("Perbaiki penyebab error lalu jalankan ulang.")
    else:
        print("Target F1 >= 0.90:", "TERCAPAI" if f1 >= 0.90 else "BELUM TERCAPAI")
    print(f"Detail: {OUT_PATH}")


if __name__ == "__main__":
    main()