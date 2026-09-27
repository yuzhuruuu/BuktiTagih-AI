import json
import os
import time
import uuid
from collections import defaultdict

import requests

API_KEY = os.environ["LANGFLOW_API_KEY"]
BASE = "http://127.0.0.1:7860"
FLOW_ID = "e05721f1-c3a2-4a33-bbd2-d30dee3df995"
CASES_PATH = "tests/evidence_cases/cases.json"
RESULTS_PATH = "tests/evidence_cases/results.json"

HEADERS = {"x-api-key": API_KEY}


def run_case(text: str) -> dict:
    payload = {
        "output_type": "chat",
        "input_type": "chat",
        "input_value": text,
        "session_id": str(uuid.uuid4()),
    }
    r = requests.post(f"{BASE}/api/v1/run/{FLOW_ID}", json=payload, headers=HEADERS, timeout=120)
    r.raise_for_status()
    data = r.json()
    raw_text = data["outputs"][0]["outputs"][0]["results"]["message"]["text"]

    # bersihkan andai ada pembungkus ```json ... ```
    cleaned = raw_text.strip()
    if cleaned.startswith("```"):
        cleaned = cleaned.strip("`")
        if "\n" in cleaned:
            cleaned = cleaned.split("\n", 1)[1]
        cleaned = cleaned.rsplit("```", 1)[0]
    return json.loads(cleaned)


def main():
    with open(CASES_PATH, encoding="utf-8") as f:
        cases = json.load(f)

    results = []
    category_totals = defaultdict(int)
    category_correct = defaultdict(int)

    for case in cases:
        cid = case["id"]
        expected_cat = case["expected_category"]
        expected_sev = case["expected_severity"]
        category_totals[expected_cat] += 1

        print(f"[{cid}] Testing... (target: {expected_cat}/{expected_sev})")

        try:
            ai = run_case(case["input"])
        except requests.exceptions.HTTPError as e:
            print(f"  ERROR HTTP: {e}")
            if "429" in str(e):
                print("  Kena rate limit, tunggu 65 detik lalu retry sekali...")
                time.sleep(65)
                try:
                    ai = run_case(case["input"])
                except Exception as e2:
                    print(f"  Gagal lagi, dilewati: {e2}")
                    results.append({"id": cid, "error": str(e2)})
                    continue
            else:
                results.append({"id": cid, "error": str(e)})
                continue
        except Exception as e:
            print(f"  ERROR, dilewati: {e}")
            results.append({"id": cid, "error": str(e)})
            continue

        cat_match = ai.get("category") == expected_cat
        sev_match = ai.get("severity") == expected_sev

        if cat_match:
            category_correct[expected_cat] += 1

        status = "MATCH" if cat_match else "MISS"
        print(f"  -> hasil: {ai.get('category')}/{ai.get('severity')}  [{status}]")

        results.append({
            "id": cid,
            "input": case["input"],
            "expected_category": expected_cat,
            "expected_severity": expected_sev,
            "actual_category": ai.get("category"),
            "actual_severity": ai.get("severity"),
            "category_match": cat_match,
            "severity_match": sev_match,
            "confidence": ai.get("confidence"),
        })

        time.sleep(13)  # jaga-jaga kuota 5 request/menit

    with open(RESULTS_PATH, "w", encoding="utf-8") as f:
        json.dump(results, f, indent=2, ensure_ascii=False)

    print("\n=== RINGKASAN RECALL PER KATEGORI ===")
    total_all, correct_all = 0, 0
    for cat in sorted(category_totals):
        total = category_totals[cat]
        correct = category_correct[cat]
        recall = correct / total if total else 0
        total_all += total
        correct_all += correct
        print(f"{cat:15s} {correct}/{total}  recall={recall:.2f}")

    overall = correct_all / total_all if total_all else 0
    print(f"\nOverall recall kategori: {correct_all}/{total_all} = {overall:.2f}")
    print("Target >= 0.90:", "TERCAPAI" if overall >= 0.90 else "BELUM TERCAPAI")
    print(f"\nHasil detail (termasuk yang meleset) disimpan di: {RESULTS_PATH}")


if __name__ == "__main__":
    main()