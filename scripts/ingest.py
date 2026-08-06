"""Crawl globaldigitalnexus.com and replace the local Chroma knowledge base."""
import argparse
import shutil
from pathlib import Path
from urllib.parse import urlparse

from firecrawl import Firecrawl
from langchain_chroma import Chroma
from langchain_core.documents import Document
from langchain_huggingface import HuggingFaceEmbeddings
from langchain_text_splitters import MarkdownHeaderTextSplitter, RecursiveCharacterTextSplitter

from app.config import get_settings
from app.security import safe_context

HEADERS = [("#", "h1"), ("##", "h2"), ("###", "h3")]


def document_from_firecrawl(page: object) -> Document | None:
    markdown = getattr(page, "markdown", None)
    metadata = getattr(page, "metadata", None)
    if not markdown:
        return None
    source = getattr(metadata, "source_url", None) or getattr(metadata, "url", None) or ""
    if urlparse(source).netloc not in {"globaldigitalnexus.com", "www.globaldigitalnexus.com"}:
        return None
    return Document(page_content=markdown, metadata={"source": source, "title": getattr(metadata, "title", None) or "Global Digital Nexus"})


def chunk_documents(documents: list[Document]) -> list[Document]:
    heading_splitter = MarkdownHeaderTextSplitter(headers_to_split_on=HEADERS, strip_headers=False)
    recursive_splitter = RecursiveCharacterTextSplitter(chunk_size=900, chunk_overlap=140, separators=["\n## ", "\n### ", "\n\n", "\n", ". ", " ", ""])
    chunks: list[Document] = []
    for document in documents:
        for section in heading_splitter.split_text(document.page_content):
            section.metadata.update(document.metadata)
            for chunk in recursive_splitter.split_documents([section]):
                heading = " > ".join(str(chunk.metadata[key]) for key in ("h1", "h2", "h3") if chunk.metadata.get(key))
                chunk.page_content = safe_context(f"Page: {document.metadata['title']}\nSection: {heading or 'General'}\n\n{chunk.page_content}")
                chunks.append(chunk)
    return chunks


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--limit", type=int, default=100)
    args = parser.parse_args()
    settings = get_settings()
    if not settings.firecrawl_api_key:
        raise SystemExit("Set FIRECRAWL_API_KEY in .env before ingesting.")
    client = Firecrawl(api_key=settings.firecrawl_api_key.get_secret_value())
    # Current v1/v2 SDK returns a typed crawl object; access its .data rather than v0 dict keys.
    result = client.crawl("https://globaldigitalnexus.com", limit=args.limit, scrape_options={"formats": ["markdown"], "only_main_content": True})
    documents = [document for page in (result.data or []) if (document := document_from_firecrawl(page))]
    chunks = chunk_documents(documents)
    if not chunks:
        raise SystemExit("Firecrawl returned no usable GDN pages; existing index was left untouched.")
    persist_dir = Path(settings.chroma_persist_directory)
    if persist_dir.exists():
        shutil.rmtree(persist_dir)
    embeddings = HuggingFaceEmbeddings(model_name="BAAI/bge-small-en-v1.5")
    Chroma.from_documents(chunks, embeddings, collection_name=settings.collection_name, persist_directory=str(persist_dir))
    print(f"Indexed {len(documents)} pages into {len(chunks)} retrieval chunks.")


if __name__ == "__main__":
    main()
