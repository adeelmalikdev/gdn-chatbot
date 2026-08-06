"""Parse latest evaluation reports and render a formatted summary."""
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
REPORTS_DIR = ROOT / "evaluations" / "reports"


def get_latest_report() -> Path | None:
    if not REPORTS_DIR.exists():
        return None
    files = sorted(REPORTS_DIR.glob("gdn-eval-*.json"))
    return files[-1] if files else None


def main() -> None:
    report_file = get_latest_report()
    if not report_file:
        print("No evaluation reports found in evaluations/reports/")
        print("Run `python -m scripts.evaluate` first to generate a report.")
        return

    data = json.loads(report_file.read_text(encoding="utf-8"))
    print(f"\n==================================================")
    print(f"       GDN Chatbot Benchmark Summary              ")
    print(f"==================================================")
    print(f" Report File : {report_file.name}")
    print(f" Created At  : {data.get('created_at', 'Unknown')}")
    print(f" Total Cases : {data.get('total', 0)}")
    print(f" Passed      : {data.get('passed', 0)}")
    print(f" Score       : {data.get('score', 0)}%")
    print(f"==================================================\n")

    results = data.get("results", [])
    failed = [r for r in results if not r.get("passed")]

    if failed:
        print(f"Failed Cases ({len(failed)}):")
        for f in failed:
            print(f" - [{f['id']}] Reason: {f.get('reason') or f.get('found_terms')}")
    else:
        print("🎉 All evaluation cases passed successfully!")


if __name__ == "__main__":
    main()
