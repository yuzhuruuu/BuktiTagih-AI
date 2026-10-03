# @Author:xxx
# @Date:2026-09-28 10:28:49
# @LastModifiedBy:xxx
# @Last Modified time:2026-09-28 10:28:49
import json
import os
import statistics
import time
import uuid
from collections import defaultdict

import requests

API_KEY = os.environ["LANGFLOW_API_KEY"]
MODEL_LABEL = os.environ.get("BENCH_MODEL", "tidak-dicatat")
BASE = "http://127.0.0.1:7860"
FLOW_ID = "e05721f1-c3a2-4a33-bbd2-d30dee3df995"
CASES_PATH = "tests/evidence_cases/cases.json"
OUT_PATH = "tests/evidence_cases/benchmark_results.json"

HEADERS = {"x-api-key": API_KEY}


def run_case(text):
    payload = {
        "output_type": "chat",
        "input_type": "chat",
        "input_value": text,
        "session_id": str(uuid.uuid4()),
    }
    start = time.time()
    r = requests.post(f"{BASE}/api/v1/run/{FLOW_ID}", json=payload, headers=HEADERS, timeout=180)
    elapsed = time.time() - start
    r.raise_for_status()
    raw = r.json()["outputs"][0]["outputs"][0]["results"]["message"]["text"]

    cleaned = raw.strip()
    if cleaned.startswith("```"):
        cleaned = cleaned.strip("`")
        if "\n" in cleaned:
            cleaned = cleaned.split("\n", 1)[1]
        cleaned = cleaned.rsplit("```", 1)[0]
    return json.loads(cleaned), elapsed


def main():
    with open(CASES_PATH, encoding="utf-8") as f:
        cases = json.load(f)

    # Sengaja TANPA retry: kuota gratis terbatas, kasus yang gagal cukup dicatat
    results = []
    durations = []
    totals = defaultdict(int)
    correct = defaultdict(int)
    sev_ok = 0
    sev_n = 0

    for case in cases:
        cid = case["id"]
        exp_cat = case["expected_category"]
        exp_sev = case["expected_severity"]
        totals[exp_cat] += 1
        print(f"[{cid}] target {exp_cat}/{exp_sev} ...", end=" ", flush=True)

        try:
            ai, elapsed = run_case(case["input"])
        except Exception as e:
            print(f"ERROR: {e}")
            results.append({"id": cid, "error": str(e)})
            time.sleep(13)
            continue

        cat_match = ai.get("category") == exp_cat
        sev_match = ai.get("severity") == exp_sev
        if cat_match:
            correct[exp_cat] += 1
        sev_n += 1
        if sev_match:
            sev_ok += 1
        durations.append(elapsed)

        print(f"{ai.get('category')}/{ai.get('severity')} "
              f"[{'MATCH' if cat_match else 'MISS'}] {elapsed:.1f}s")

        results.append({
            "id": cid,
            "input": case["input"],
            "expected_category": exp_cat,
            "expected_severity": exp_sev,
            "actual_category": ai.get("category"),
            "actual_severity": ai.get("severity"),
            "category_match": cat_match,
            "severity_match": sev_match,
            "confidence": ai.get("confidence"),
            "entities": ai.get("entities", []),
            "duration_seconds": round(elapsed, 2),
        })
        time.sleep(13)  # jaga batas 5 request/menit

    summary = {"model": MODEL_LABEL, "n_cases": len(cases), "n_ok": len(durations)}
    if durations:
        summary["avg_seconds"] = round(sum(durations) / len(durations), 2)
        summary["median_seconds"] = round(statistics.median(durations), 2)
        summary["min_seconds"] = round(min(durations), 2)
        summary["max_seconds"] = round(max(durations), 2)
        summary["est_100_sequential_minutes"] = round(summary["avg_seconds"] * 100 / 60, 1)

    with open(OUT_PATH, "w", encoding="utf-8") as f:
        json.dump({"summary": summary, "results": results}, f, indent=2, ensure_ascii=False)

    print(f"\n=== RINGKASAN (model: {MODEL_LABEL}) ===")
    tot_all = sum(totals[c] for c in totals)
    ok_all = sum(correct[c] for c in totals)
    for cat in sorted(totals):
        print(f"{cat:15s} {correct[cat]}/{totals[cat]}  recall={correct[cat]/totals[cat]:.2f}")
    print(f"Recall kategori keseluruhan: {ok_all}/{tot_all} = {ok_all/tot_all:.2f}")
    if sev_n:
        print(f"Akurasi severity: {sev_ok}/{sev_n} = {sev_ok/sev_n:.2f}")
    if durations:
        print(f"Waktu: rata-rata {summary['avg_seconds']}s, median {summary['median_seconds']}s, "
              f"tercepat {summary['min_seconds']}s, terlama {summary['max_seconds']}s")
        print(f"Estimasi 100 pesan berurutan: {summary['est_100_sequential_minutes']} menit (target <=3)")
    print(f"Kasus gagal: {len(cases) - len(durations)}")
    print(f"Detail: {OUT_PATH}")


if __name__ == "__main__":
    main()