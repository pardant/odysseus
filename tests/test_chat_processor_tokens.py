"""Tests for src/chat_processor._content_tokens and _STOPWORDS."""
import pytest

from src.chat_processor import _content_tokens, _STOPWORDS


class TestStopwords:
    def test_stopwords_is_frozenset(self):
        assert isinstance(_STOPWORDS, frozenset)

    def test_common_stopwords_present(self):
        for word in ["the", "is", "and", "but", "not", "this", "that"]:
            assert word in _STOPWORDS

    def test_contractions_present(self):
        for word in ["don't", "doesn't", "didn't", "won't", "can't"]:
            assert word in _STOPWORDS


class TestContentTokens:
    def test_basic_extraction(self):
        tokens = _content_tokens("Python programming language")
        assert "python" in tokens
        assert "programming" in tokens
        assert "language" in tokens

    def test_removes_stopwords(self):
        tokens = _content_tokens("the quick brown fox is very fast")
        assert "the" not in tokens  # stopword
        assert "quick" in tokens  # not a stopword, >=3 chars
        assert "brown" in tokens
        assert "fox" in tokens
        assert "fast" in tokens
        assert "very" not in tokens  # stopword

    def test_filters_short_words(self):
        tokens = _content_tokens("I am a go to do it")
        # all words <= 2 chars or stopwords
        assert tokens == []

    def test_lowercases(self):
        tokens = _content_tokens("Python JavaScript TypeScript")
        assert "python" in tokens
        assert "javascript" in tokens
        assert "typescript" in tokens

    def test_hyphenated_words(self):
        tokens = _content_tokens("self-hosted open-source machine-learning")
        assert "self-hosted" in tokens
        assert "open-source" in tokens
        assert "machine-learning" in tokens

    def test_underscored_words(self):
        tokens = _content_tokens("my_variable some_function")
        assert "my_variable" in tokens
        assert "some_function" in tokens

    def test_empty_string(self):
        assert _content_tokens("") == []

    def test_only_stopwords(self):
        tokens = _content_tokens("the is are was were be been being")
        assert tokens == []

    def test_numbers_included(self):
        tokens = _content_tokens("python 3.12 version 100")
        assert "python" in tokens
        assert "version" in tokens
        assert "100" in tokens

    def test_special_characters_stripped(self):
        # hello, yes, no are all stopwords; only world and maybe survive
        tokens = _content_tokens("Hello! World? Yes. No, maybe...")
        assert "hello" not in tokens  # stopword
        assert "world" in tokens
        assert "yes" not in tokens  # stopword
        assert "maybe" in tokens
