# @Author:xxx
# @Date:2026-09-28 05:18:22
# @LastModifiedBy:xxx
# @Last Modified time:2026-09-28 05:18:22
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

    cleaned = raw_text.strip()
    if cleaned.startswith("```"):
        cleaned = cleaned.strip("`")
        if "\n" in cleaned:
            cleaned = cleaned.split("\n", 1)[1]
        cleaned = cleaned.rsplit("```", 1)[0]
    return json.loads(cleaned)


def main():
    with open(CASES_PATH, encoding="utf-8") as f:
        cases_by_id = {c["id"]: c for c in json.load(f)}

    with open(RESULTS_PATH, encoding="utf-8") as f:
        old_results = json.load(f)

    # id yang perlu diulang: yang punya key "error"
    failed_ids = [r["id"] for r in old_results if "error" in r]
    print(f"Ada {len(failed_ids)} kasus yang perlu diulang: {failed_ids}")

    results_by_id = {r["id"]: r for r in old_results if "error" not in r}

    for cid in failed_ids:
        case = cases_by_id[cid]
        expected_cat = case["expected_category"]
        expected_sev = case["expected_severity"]

        print(f"[{cid}] Retry... (target: {expected_cat}/{expected_sev})")

        try:
            ai = run_case(case["input"])
        except requests.exceptions.HTTPError as e:
            print(f"  MASIH ERROR: {e}")
            results_by_id[cid] = {"id": cid, "error": str(e)}
            time.sleep(13)
            continue
        except Exception as e:
            print(f"  MASIH ERROR: {e}")
            results_by_id[cid] = {"id": cid, "error": str(e)}
            time.sleep(13)
            continue

        cat_match = ai.get("category") == expected_cat
        sev_match = ai.get("severity") == expected_sev
        status = "MATCH" if cat_match else "MISS"
        print(f"  -> hasil: {ai.get('category')}/{ai.get('severity')}  [{status}]")

        results_by_id[cid] = {
            "id": cid,
            "input": case["input"],
            "expected_category": expected_cat,
            "expected_severity": expected_sev,
            "actual_category": ai.get("category"),
            "actual_severity": ai.get("severity"),
            "category_match": cat_match,
            "severity_match": sev_match,
            "confidence": ai.get("confidence"),
        }

        time.sleep(13)

    merged = [results_by_id[cid] for cid in sorted(results_by_id)]
    with open(RESULTS_PATH, "w", encoding="utf-8") as f:
        json.dump(merged, f, indent=2, ensure_ascii=False)

    print("\n=== RINGKASAN RECALL PER KATEGORI (gabungan) ===")
    category_totals = defaultdict(int)
    category_correct = defaultdict(int)
    still_failed = []

    for r in merged:
        if "error" in r:
            still_failed.append(r["id"])
            continue
        category_totals[r["expected_category"]] += 1
        if r["category_match"]:
            category_correct[r["expected_category"]] += 1

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
    if still_failed:
        print(f"\nMasih gagal (belum ada hasil sama sekali): {still_failed}")
    print(f"Hasil disimpan di: {RESULTS_PATH}")


if __name__ == "__main__":
    main()