import json, os
os.environ.setdefault("LANGFLOW_API_KEY", "x")
from run_entity_f1 import match, EVAL_TYPES

cases = {c["id"]: c for c in json.load(open("tests/f1_entity/entity_cases.json", encoding="utf-8"))}
res = json.load(open("tests/f1_entity/entity_results_baseline.json", encoding="utf-8"))["results"]
tp = fn = fp = 0
for r in res:
    exp = cases[r["id"]]["expected_entities"]
    act = [e for e in r.get("actual_entities", []) if e["entity_type"] in EVAL_TYPES]
    t = sum(match(e, act) for e in exp)
    tp += t; fn += len(exp) - t; fp += max(len(act) - t, 0)
p, rc = tp / (tp + fp), tp / (tp + fn)
print("TP", tp, "FN", fn, "FP", fp, "F1", round(2 * p * rc / (p + rc), 3))