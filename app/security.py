import re

from fastapi import HTTPException, status

# This is a fast, explainable first barrier. The model and retrieval boundaries are
# the defence-in-depth controls; no regex is treated as a complete solution.
INJECTION_PATTERNS = [
    r"ignore (all |any |the |previous |prior )?(instructions|rules|prompt)",
    r"(reveal|show|print|repeat|dump).{0,40}(system prompt|hidden prompt|instructions)",
    r"you are now|act as|developer message|jailbreak",
    r"<\s*/?(system|instruction|prompt)\s*>",
]


def reject_prompt_injection(text: str) -> None:
    if any(re.search(pattern, text, flags=re.IGNORECASE) for pattern in INJECTION_PATTERNS):
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="I can help with Global Digital Nexus questions, but I can’t process requests to override assistant instructions.",
        )


def safe_context(text: str) -> str:
    """Treat retrieved website text as untrusted data, not executable instructions."""
    text = re.sub(r"(?i)(ignore|disregard).{0,80}(instruction|prompt)", "[removed suspicious text]", text)
    return text.replace("<", "&lt;").replace(">", "&gt;")
