"""Tests for src/cleanup_service.py — CleanupConfig and _apply_owner_filter."""
from unittest.mock import MagicMock, patch
from datetime import datetime, timezone

import pytest

from src.cleanup_service import CleanupConfig, _apply_owner_filter, _utcnow


class TestCleanupConfig:
    """Verify the cleanup configuration constants."""

    def test_archive_after_days(self):
        assert CleanupConfig.ARCHIVE_AFTER_DAYS == 7

    def test_delete_after_days(self):
        assert CleanupConfig.DELETE_AFTER_DAYS == 14

    def test_min_messages_to_keep(self):
        assert CleanupConfig.MIN_MESSAGES_TO_KEEP == 20

    def test_preserve_recent_count(self):
        assert CleanupConfig.PRESERVE_RECENT_COUNT == 10

    def test_protected_keywords(self):
        assert "important" in CleanupConfig.PROTECTED_KEYWORDS
        assert "remember" in CleanupConfig.PROTECTED_KEYWORDS
        assert "save this" in CleanupConfig.PROTECTED_KEYWORDS
        assert "keep" in CleanupConfig.PROTECTED_KEYWORDS
        assert "bookmark" in CleanupConfig.PROTECTED_KEYWORDS

    def test_estimated_message_size(self):
        assert CleanupConfig.ESTIMATED_MESSAGE_SIZE_BYTES == 512

    def test_delete_after_greater_than_archive_after(self):
        assert CleanupConfig.DELETE_AFTER_DAYS > CleanupConfig.ARCHIVE_AFTER_DAYS


class TestApplyOwnerFilter:
    """Verify _apply_owner_filter applies strict per-user filtering."""

    def test_none_owner_returns_query_unchanged(self):
        mock_query = MagicMock()
        mock_db_session = MagicMock()
        result = _apply_owner_filter(mock_query, mock_db_session, None)
        assert result is mock_query
        mock_query.filter.assert_not_called()

    def test_with_owner_applies_filter(self):
        mock_query = MagicMock()
        mock_db_session = MagicMock()
        mock_db_session.owner = MagicMock()
        result = _apply_owner_filter(mock_query, mock_db_session, "user1")
        mock_query.filter.assert_called_once()
        assert result is mock_query.filter.return_value


class TestUtcnow:
    """Verify _utcnow returns a naive UTC datetime."""

    def test_returns_naive_datetime(self):
        result = _utcnow()
        assert isinstance(result, datetime)
        assert result.tzinfo is None

    def test_close_to_actual_utc(self):
        before = datetime.now(timezone.utc).replace(tzinfo=None)
        result = _utcnow()
        after = datetime.now(timezone.utc).replace(tzinfo=None)
        assert before <= result <= after
