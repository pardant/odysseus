# src/llm_helpers.py
"""Shared LLM interaction helpers.

Consolidates recurring patterns used across route handlers:
- Endpoint resolution with utility→default fallback
- JSON extraction from LLM output (strip code fences, parse)
- LLM request payload construction (token key, temperature quirks)
- Owner-slug generation for per-user file paths
"""

from __future__ import annotations

import json
import logging
import re
from typing import Any, Dict, List, Optional, Tuple

logger = logging.getLogger(__name__)


# ─── Endpoint resolution with fallback ────────────────────────────────────────

def resolve_endpoint_with_fallback(
    owner: Optional[str] = None,
    *,
    preferred: str = "utility",
    fallback: str = "default",
) -> Tuple[Optional[str], Optional[str], Optional[Dict[str, str]]]:
    """Resolve an LLM endpoint, trying *preferred* first then *fallback*.

    This eliminates the repeated pattern::

        url, model, headers = resolve_endpoint("utility", owner=owner)
        if not url or not model:
            url, model, headers = resolve_endpoint("default", owner=owner)

    Returns:
        (endpoint_url, model, headers) — may be (None, None, None) if nothing
        is configured.
    """
    from src.endpoint_resolver import resolve_endpoint

    url, model, headers = resolve_endpoint(preferred, owner=owner)
    if url and model:
        return url, model, headers
    url, model, headers = resolve_endpoint(fallback, owner=owner)
    return url, model, headers


# ─── JSON extraction from LLM output ─────────────────────────────────────────

_CODE_FENCE_START_RE = re.compile(r"^```(?:json|JSON)?\s*\n?")
_CODE_FENCE_END_RE = re.compile(r"\s*```\s*$")


def strip_code_fences(text: str) -> str:
    """Remove wrapping markdown code fences (```json ... ```) from LLM output."""
    text = text.strip()
    if text.startswith("```"):
        text = _CODE_FENCE_START_RE.sub("", text)
        text = _CODE_FENCE_END_RE.sub("", text)
    return text.strip()


def extract_json_object(text: str) -> Optional[Dict[str, Any]]:
    """Extract a JSON object from LLM output, tolerating code fences and stray text.

    Tries in order:
    1. Direct parse after stripping code fences.
    2. Greedy regex for the outermost ``{...}`` block.
    3. With trailing-comma repair.

    Returns None if no valid JSON object can be extracted.
    """
    text = strip_code_fences(text)

    # Direct parse
    try:
        obj = json.loads(text)
        if isinstance(obj, dict):
            return obj
    except (json.JSONDecodeError, ValueError):
        pass

    # Extract first {...} block (greedy to capture the outermost object)
    m = re.search(r"\{[\s\S]*\}", text)
    if m:
        fragment = m.group(0)
        for candidate in (fragment, re.sub(r",(\s*[}\]])", r"\1", fragment)):
            try:
                obj = json.loads(candidate)
                if isinstance(obj, dict):
                    return obj
            except (json.JSONDecodeError, ValueError):
                continue

    return None


def extract_json_array(text: str) -> Optional[List[Any]]:
    """Extract a JSON array from LLM output, tolerating code fences and stray text.

    Returns None if no valid JSON array can be extracted.
    """
    text = strip_code_fences(text)

    # Direct parse
    try:
        arr = json.loads(text)
        if isinstance(arr, list):
            return arr
    except (json.JSONDecodeError, ValueError):
        pass

    # Greedy regex for outermost [...] block
    m = re.search(r"\[[\s\S]*\]", text)
    if m:
        fragment = m.group(0)
        for candidate in (fragment, re.sub(r",(\s*[}\]])", r"\1", fragment)):
            try:
                arr = json.loads(candidate)
                if isinstance(arr, list):
                    return arr
            except (json.JSONDecodeError, ValueError):
                continue

    return None


# ─── LLM payload helpers ─────────────────────────────────────────────────────

def llm_token_key(model: str) -> str:
    """Return the correct max-tokens parameter name for a model.

    Some models (o-series, gpt-5) require ``max_completion_tokens`` instead
    of the standard ``max_tokens``.
    """
    from src.llm_core import _uses_max_completion_tokens

    return "max_completion_tokens" if _uses_max_completion_tokens(model) else "max_tokens"


def build_llm_payload(
    model: str,
    messages: List[Dict[str, Any]],
    *,
    max_tokens: int = 1024,
    temperature: float = 0.7,
    stream: bool = False,
    **extra: Any,
) -> Dict[str, Any]:
    """Build an OpenAI-compatible chat-completion payload with model quirks.

    Handles:
    - ``max_tokens`` vs ``max_completion_tokens`` based on model name.
    - Removing ``temperature`` for reasoning models that reject it.
    """
    from src.llm_core import _uses_max_completion_tokens, _restricts_temperature

    tok_key = "max_completion_tokens" if _uses_max_completion_tokens(model) else "max_tokens"

    payload: Dict[str, Any] = {
        "model": model,
        "messages": messages,
        tok_key: max_tokens,
        "stream": stream,
    }

    if not _restricts_temperature(model):
        payload["temperature"] = temperature

    payload.update(extra)
    return payload


def build_llm_headers(headers: Optional[Dict[str, str]] = None) -> Dict[str, str]:
    """Build request headers for an LLM call, merging any endpoint-specific headers."""
    req_headers: Dict[str, str] = {"Content-Type": "application/json"}
    if headers:
        req_headers.update(headers)
    return req_headers


# ─── Owner slug ───────────────────────────────────────────────────────────────

def owner_slug(owner: Optional[str]) -> str:
    """Generate a filesystem-safe slug from an owner identifier.

    Preserves alphanumerics, hyphens, dots, underscores, and ``@``.
    Everything else becomes ``_``. Defaults to ``"default"`` for None/empty.
    """
    return "".join(
        c if (c.isalnum() or c in "-_.@") else "_"
        for c in (owner or "default")
    )
