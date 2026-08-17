from functools import lru_cache
from pathlib import Path
from typing import Annotated

from pydantic import Field, SecretStr, field_validator
from pydantic_settings import BaseSettings, NoDecode, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", env_file_encoding="utf-8", extra="ignore")

    app_name: str = "GDN Assistant API"
    environment: str = "development"
    firecrawl_api_key: SecretStr | None = None
    llm_provider: str = "xai"
    llm_model: str = "grok-3-mini"
    llm_base_url: str | None = None
    llm_api_key: SecretStr | None = None
    xai_api_key: SecretStr | None = None
    groq_api_key: SecretStr | None = None
    mistral_api_key: SecretStr | None = None
    redis_url: str | None = None
    langchain_tracing_v2: bool = False
    langchain_api_key: SecretStr | None = None
    langchain_project: str = "gdn-chatbot"
    # Environment variables for complex types are JSON-decoded by default.  This
    # explicitly keeps the friendly comma-separated .env format instead.
    allowed_origins: Annotated[list[str], NoDecode] = Field(
        default_factory=lambda: ["http://localhost:3000"]
    )
    rate_limit: str = "20/minute"
    chroma_persist_directory: Path = Path("data/chroma")
    collection_name: str = "gdn_website"
    retrieval_k: int = 5
    max_question_chars: int = 2_000
    max_history_messages: int = 8

    @field_validator("allowed_origins", mode="before")
    @classmethod
    def split_origins(cls, value: str | list[str]) -> list[str]:
        return [item.strip().rstrip("/") for item in value.split(",")] if isinstance(value, str) else value

    def provider_key(self) -> str:
        if self.llm_provider == "groq":
            key = self.groq_api_key or self.llm_api_key
        elif self.llm_provider == "xai":
            key = self.xai_api_key or self.llm_api_key
        elif self.llm_provider == "mistral":
            key = self.mistral_api_key or self.llm_api_key
        else:
            key = self.llm_api_key
        if not key:
            raise RuntimeError(f"Missing API key for LLM_PROVIDER={self.llm_provider}")
        return key.get_secret_value()

    def provider_base_url(self) -> str | None:
        if self.llm_provider == "groq":
            return "https://api.groq.com/openai/v1"
        elif self.llm_provider == "xai":
            return "https://api.x.ai/v1"
        elif self.llm_provider == "mistral":
            return "https://api.mistral.ai/v1"
        return self.llm_base_url


@lru_cache
def get_settings() -> Settings:
    return Settings()
