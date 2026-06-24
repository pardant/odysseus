"""Tests for src/session_actions.py — pure helper functions and constants."""
from datetime import datetime, timedelta, timezone
from unittest.mock import MagicMock

import pytest

from src.session_actions import (
    _THROWAWAY_NAMES,
    _THROWAWAY_MAX_MESSAGES,
    _FRESH_EMPTY_SESSION_GRACE,
    _FRESH_SESSION_GRACE,
    _utcnow_naive,
    _as_naive_utc,
    is_session_recently_active,
)


class TestThrowawayNames:
    """Verify the throwaway session names set."""

    def test_is_a_set(self):
        assert isinstance(_THROWAWAY_NAMES, set)

    def test_common_throwaway_names(self):
        for name in ("test", "asdf", "hello", "foo", "bar", "tmp", "scratch"):
            assert name in _THROWAWAY_NAMES

    def test_all_lowercase(self):
        for name in _THROWAWAY_NAMES:
            assert name == name.lower()

    def test_max_messages_is_small(self):
        assert _THROWAWAY_MAX_MESSAGES == 4


class TestGraceTimedeltas:
    def test_grace_periods_are_timedeltas(self):
        assert isinstance(_FRESH_EMPTY_SESSION_GRACE, timedelta)
        assert isinstance(_FRESH_SESSION_GRACE, timedelta)

    def test_grace_is_10_minutes(self):
        assert _FRESH_EMPTY_SESSION_GRACE == timedelta(minutes=10)
        assert _FRESH_SESSION_GRACE == timedelta(minutes=10)


class TestUtcnowNaive:
    def test_returns_naive_datetime(self):
        result = _utcnow_naive()
        assert isinstance(result, datetime)
        assert result.tzinfo is None

    def test_close_to_actual_utc(self):
        before = datetime.now(timezone.utc).replace(tzinfo=None)
        result = _utcnow_naive()
        after = datetime.now(timezone.utc).replace(tzinfo=None)
        assert before <= result <= after


class TestAsNaiveUtc:
    def test_none_returns_none(self):
        assert _as_naive_utc(None) is None

    def test_naive_datetime_returned_unchanged(self):
        dt = datetime(2024, 6, 15, 10, 30, 0)
        result = _as_naive_utc(dt)
        assert result is dt

    def test_aware_datetime_converted_to_naive_utc(self):
        # 10:30 AM in UTC+2 is 08:30 AM in UTC
        tz_plus2 = timezone(timedelta(hours=2))
        dt = datetime(2024, 6, 15, 10, 30, 0, tzinfo=tz_plus2)
        result = _as_naive_utc(dt)
        assert result.tzinfo is None
        assert result.hour == 8
        assert result.minute == 30

    def test_utc_aware_stripped(self):
        dt = datetime(2024, 6, 15, 10, 30, 0, tzinfo=timezone.utc)
        result = _as_naive_utc(dt)
        assert result.tzinfo is None
        assert result.hour == 10


class TestIsSessionRecentlyActive:
    def _make_row(self, **kwargs):
        """Create a mock session row with given attributes."""
        row = MagicMock()
        for attr in ("last_message_at", "last_accessed", "updated_at", "created_at"):
            setattr(row, attr, kwargs.get(attr, None))
        return row

    def test_recently_created_is_active(self):
        now = datetime(2024, 6, 15, 12, 0, 0)
        row = self._make_row(created_at=now - timedelta(minutes=5))
        assert is_session_recently_active(row, now=now) is True

    def test_old_session_not_active(self):
        now = datetime(2024, 6, 15, 12, 0, 0)
        row = self._make_row(created_at=now - timedelta(hours=1))
        assert is_session_recently_active(row, now=now) is False

    def test_recently_messaged_is_active(self):
        now = datetime(2024, 6, 15, 12, 0, 0)
        row = self._make_row(last_message_at=now - timedelta(minutes=3))
        assert is_session_recently_active(row, now=now) is True

    def test_recently_accessed_is_active(self):
        now = datetime(2024, 6, 15, 12, 0, 0)
        row = self._make_row(last_accessed=now - timedelta(minutes=9))
        assert is_session_recently_active(row, now=now) is True

    def test_exactly_at_grace_boundary(self):
        now = datetime(2024, 6, 15, 12, 0, 0)
        # 10 minutes is the grace period — at exactly the boundary, within grace
        row = self._make_row(updated_at=now - timedelta(minutes=10))
        assert is_session_recently_active(row, now=now) is True

    def test_just_past_grace_boundary(self):
        now = datetime(2024, 6, 15, 12, 0, 0)
        row = self._make_row(updated_at=now - timedelta(minutes=10, seconds=1))
        assert is_session_recently_active(row, now=now) is False

    def test_future_timestamp_is_active(self):
        now = datetime(2024, 6, 15, 12, 0, 0)
        row = self._make_row(created_at=now + timedelta(minutes=5))
        assert is_session_recently_active(row, now=now) is True

    def test_no_timestamps_not_active(self):
        now = datetime(2024, 6, 15, 12, 0, 0)
        row = self._make_row()
        assert is_session_recently_active(row, now=now) is False

    def test_custom_grace_period(self):
        now = datetime(2024, 6, 15, 12, 0, 0)
        row = self._make_row(created_at=now - timedelta(minutes=25))
        # Default 10-min grace -> not active
        assert is_session_recently_active(row, now=now) is False
        # Custom 30-min grace -> active
        assert is_session_recently_active(row, now=now, grace=timedelta(minutes=30)) is True

    def test_aware_now_parameter(self):
        # Pass an aware datetime as 'now' — function should handle it
        now = datetime(2024, 6, 15, 12, 0, 0, tzinfo=timezone.utc)
        row = self._make_row(created_at=datetime(2024, 6, 15, 11, 55, 0))
        assert is_session_recently_active(row, now=now) is True
