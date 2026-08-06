import logging
import time
from contextlib import asynccontextmanager

from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import StreamingResponse
from slowapi import Limiter, _rate_limit_exceeded_handler
from slowapi.errors import RateLimitExceeded
from slowapi.util import get_remote_address

from app.config import get_settings
from app.rag import answer, answer_stream, warm_retrieval_resources
from app.schemas import ChatRequest, ChatResponse
from app.security import reject_prompt_injection

logging.basicConfig(level=logging.INFO)
settings = get_settings()

limiter_kwargs = {"key_func": get_remote_address}
if settings.redis_url:
    limiter_kwargs["storage_uri"] = settings.redis_url

limiter = Limiter(**limiter_kwargs)


@asynccontextmanager
async def lifespan(_: FastAPI):
    logging.getLogger(__name__).info("Preloading GDN retrieval resources")
    warm_retrieval_resources(settings)
    logging.getLogger(__name__).info("GDN retrieval resources are ready")
    yield


app = FastAPI(title=settings.app_name, version="0.1.0", docs_url=None, redoc_url=None, lifespan=lifespan)
app.state.limiter = limiter
app.add_exception_handler(RateLimitExceeded, _rate_limit_exceeded_handler)


@app.middleware("http")
async def add_security_and_timing_headers(request: Request, call_next):
    start_time = time.perf_counter()
    response = await call_next(request)
    process_time = time.perf_counter() - start_time
    response.headers["X-Process-Time"] = f"{process_time:.4f}s"
    response.headers["X-Content-Type-Options"] = "nosniff"
    response.headers["X-Frame-Options"] = "DENY"
    response.headers["X-XSS-Protection"] = "1; mode=block"
    response.headers["Referrer-Policy"] = "strict-origin-when-cross-origin"
    return response


# Local widget previews are commonly served by a lightweight web server on an
# arbitrary localhost port. Keep that convenience in development only; deployed
# environments remain limited to the exact origins in ALLOWED_ORIGINS.
is_production = settings.environment.lower() == "production"
cors_origins = settings.allowed_origins if is_production else ["*"]
app.add_middleware(
    CORSMiddleware,
    allow_origins=cors_origins,
    allow_credentials=False,
    allow_methods=["*"] if not is_production else ["GET", "POST"],
    allow_headers=["*"],
)


@app.get("/health")
def health() -> dict[str, str | bool]:
    """Uptime and readiness health check."""
    return {
        "status": "healthy",
        "environment": settings.environment,
        "llm_provider": settings.llm_provider,
        "llm_model": settings.llm_model,
        "redis_configured": bool(settings.redis_url),
    }


@app.post("/chat", response_model=ChatResponse)
@limiter.limit(settings.rate_limit)
def chat(request: Request, payload: ChatRequest) -> ChatResponse:
    """The public surface: client sends a message and optional short local history."""
    reject_prompt_injection(payload.message)
    for item in payload.history:
        reject_prompt_injection(item.content)
    text, sources = answer(payload.message, payload.history, settings)
    return ChatResponse(answer=text, sources=sources)


@app.post("/chat/stream")
@limiter.limit(settings.rate_limit)
def chat_stream(request: Request, payload: ChatRequest) -> StreamingResponse:
    """Real-time SSE token streaming endpoint."""
    reject_prompt_injection(payload.message)
    for item in payload.history:
        reject_prompt_injection(item.content)
    generator = answer_stream(payload.message, payload.history, settings)
    return StreamingResponse(generator, media_type="text/event-stream")


