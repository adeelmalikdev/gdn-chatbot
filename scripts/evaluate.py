"""Run a repeatable quality check against the live GDN RAG pipeline.

This calls the configured LLM, so it consumes provider tokens.  It checks
retrieval/source policy and lightweight answer expectations; human review of
the generated report remains essential for tone and factual nuance.
"""
import argparse
import json
from concurrent.futures import ThreadPoolExecutor
from datetime import UTC, datetime
from pathlib import Path

from fastapi import HTTPException

from app.config import get_settings
from app.rag import answer, warm_retrieval_resources
from app.schemas import ChatMessage
from app.security import reject_prompt_injection

ROOT = Path(__file__).resolve().parents[1]
CASES_PATH = ROOT / "evaluations" / "gdn_eval_cases.json"
REPORTS_DIR = ROOT / "evaluations" / "reports"


def evaluate(case: dict, settings) -> dict:
    expected_rejection = case.get("expect_rejection", False)
    try:
        reject_prompt_injection(case["question"])
        if expected_rejection:
            return {"id": case["id"], "passed": False, "reason": "Expected request rejection"}
        history = [ChatMessage.model_validate(item) for item in case.get("history", [])]
        response, sources = answer(case["question"], history, settings)
        answer_lower = response.lower()
        expected_terms = case["expected_terms"]
        found_terms = [term for term in expected_terms if term.lower() in answer_lower]
        source_urls = [source.url.lower() for source in sources]
        source_ok = not case["wants_sources"] or any(case["source_hint"].lower() in url for url in source_urls)
        passed = bool(found_terms) and source_ok
        return {
            "id": case["id"], "passed": passed, "answer": response, "expected_terms": expected_terms,
            "found_terms": found_terms, "sources": source_urls, "source_ok": source_ok,
        }
    except HTTPException:
        return {"id": case["id"], "passed": expected_rejection, "reason": "Request rejected as expected"}
    except Exception as error:  # noqa: BLE001 # Surface provider/retrieval faults in the report.
        return {"id": case["id"], "passed": False, "reason": f"{type(error).__name__}: {error}"}


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--limit", type=int, default=None, help="Run only the first N cases")
    parser.add_argument("--workers", type=int, default=4, help="Concurrent live checks (default: 4)")
    args = parser.parse_args()
    settings = get_settings()
    cases = json.loads(CASES_PATH.read_text(encoding="utf-8"))[: args.limit]
    warm_retrieval_resources(settings)
    # Cases are independent. A small pool keeps evaluation practical without
    # creating an aggressive burst against the LLM provider.
    with ThreadPoolExecutor(max_workers=args.workers) as executor:
        results = list(executor.map(lambda case: evaluate(case, settings), cases))
    passed = sum(result["passed"] for result in results)
    report = {
        "created_at": datetime.now(UTC).isoformat(), "total": len(results), "passed": passed,
        "score": round(passed / len(results) * 100, 1) if results else 0, "results": results,
    }
    REPORTS_DIR.mkdir(parents=True, exist_ok=True)
    report_path = REPORTS_DIR / f"gdn-eval-{datetime.now(UTC).strftime('%Y%m%d-%H%M%S')}.json"
    report_path.write_text(json.dumps(report, indent=2), encoding="utf-8")
    print(f"Quality score: {report['score']}% ({passed}/{len(results)})")
    print(f"Report: {report_path}")
    for result in results:
        if not result["passed"]:
            print(f"FAIL {result['id']}: {result.get('reason') or result.get('found_terms')}")


if __name__ == "__main__":
    main()
