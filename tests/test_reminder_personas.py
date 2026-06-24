"""Tests for src/reminder_personas.py — persona lookup and synthesis prompt."""
import pytest

from src.reminder_personas import (
    PERSONAS,
    _DEFAULT_SYNTHESIS_TONE,
    synthesis_system_prompt,
)


class TestPersonasDict:
    def test_known_keys(self):
        expected = {"socrates", "razor", "nietzsche", "spark", "odysseus"}
        assert set(PERSONAS.keys()) == expected

    def test_all_values_are_nonempty_strings(self):
        for key, value in PERSONAS.items():
            assert isinstance(value, str), f"{key} value is not a string"
            assert len(value) > 10, f"{key} prompt is suspiciously short"


class TestSynthesisSystemPrompt:
    def test_known_persona_returns_persona_plus_suffix(self):
        result = synthesis_system_prompt("socrates")
        assert "questions" in result.lower()
        assert "under 18 words" in result

    def test_all_known_personas_include_reminder_suffix(self):
        for pid in PERSONAS:
            result = synthesis_system_prompt(pid)
            assert "one-line reminder" in result
            assert "under 18 words" in result

    def test_unknown_persona_returns_default(self):
        result = synthesis_system_prompt("unknown_persona_xyz")
        assert result == _DEFAULT_SYNTHESIS_TONE

    def test_empty_string_returns_default(self):
        assert synthesis_system_prompt("") == _DEFAULT_SYNTHESIS_TONE

    def test_none_returns_default(self):
        assert synthesis_system_prompt(None) == _DEFAULT_SYNTHESIS_TONE

    def test_case_insensitive_lookup(self):
        result = synthesis_system_prompt("SOCRATES")
        assert "questions" in result.lower()
        assert "under 18 words" in result

    def test_whitespace_trimmed(self):
        result = synthesis_system_prompt("  razor  ")
        assert "fewest words" in result.lower()
        assert "under 18 words" in result

    def test_custom_returns_default(self):
        # "custom" is a client-only persona not known server-side
        assert synthesis_system_prompt("custom") == _DEFAULT_SYNTHESIS_TONE
