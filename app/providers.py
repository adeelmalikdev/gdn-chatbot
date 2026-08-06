from langchain_openai import ChatOpenAI

from app.config import Settings


def get_chat_model(settings: Settings) -> ChatOpenAI:
    """One OpenAI-compatible adapter; change provider through environment only."""
    return ChatOpenAI(
        model=settings.llm_model,
        api_key=settings.provider_key(),
        base_url=settings.provider_base_url(),
        temperature=0.2,
        max_retries=2,
        timeout=45,
    )
