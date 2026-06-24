"""Tests for src/assistant_log.py — legacy tag regex and session manager setter."""
import re

import pytest

from src.assistant_log import (
    _LEGACY_TAG_RE,
    set_session_manager,
    log_to_assistant,
    _session_manager,
)


class TestLegacyTagRegex:
    """Verify the regex that extracts **[Category]** from content."""

    def test_matches_standard_tag(self):
        m = _LEGACY_TAG_RE.match("**[Download]** Started downloading file")
        assert m is not None
        assert m.group(1) == "Download"

    def test_matches_with_leading_whitespace(self):
        m = _LEGACY_TAG_RE.match("  **[Alert]** Something happened")
        assert m is not None
        assert m.group(1) == "Alert"

    def test_no_match_without_bold(self):
        m = _LEGACY_TAG_RE.match("[Download] Started downloading")
        assert m is None

    def test_no_match_when_tag_in_middle(self):
        m = _LEGACY_TAG_RE.match("Some text **[Download]** more text")
        assert m is None

    def test_tag_max_length_40(self):
        tag = "A" * 40
        m = _LEGACY_TAG_RE.match(f"**[{tag}]** text")
        assert m is not None
        assert m.group(1) == tag

    def test_tag_too_long_no_match(self):
        tag = "A" * 41
        m = _LEGACY_TAG_RE.match(f"**[{tag}]** text")
        assert m is None

    def test_empty_tag_no_match(self):
        m = _LEGACY_TAG_RE.match("**[]** text")
        assert m is None

    def test_various_categories(self):
        for cat in ("Email", "Task", "Research", "Calendar", "Note"):
            m = _LEGACY_TAG_RE.match(f"**[{cat}]** Something")
            assert m is not None
            assert m.group(1) == cat


class TestSetSessionManager:
    def test_sets_global(self):
        sentinel = object()
        set_session_manager(sentinel)
        import src.assistant_log as mod
        assert mod._session_manager is sentinel
        # Cleanup
        set_session_manager(None)

    def test_reset_to_none(self):
        set_session_manager("something")
        set_session_manager(None)
        import src.assistant_log as mod
        assert mod._session_manager is None


class TestLogToAssistant:
    def test_returns_none_noop(self):
        result = log_to_assistant("owner1", "some content")
        assert result is None

    def test_accepts_all_params_without_error(self):
        # Should not raise
        log_to_assistant(
            "owner1",
            "**[Test]** Hello",
            role="system",
            category="Test",
        )
