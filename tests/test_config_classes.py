"""Tests for src/config.py — Pydantic config classes and validate_config."""
import os
from pathlib import Path
from unittest.mock import patch

import pytest

from src.config import (
    DataConfig,
    LLMConfig,
    SearchConfig,
    SecurityConfig,
    AppConfig,
    IS_WINDOWS,
    create_directories,
    validate_config,
    config,
)


class TestISWindows:
    def test_matches_os_name(self):
        assert IS_WINDOWS == (os.name == "nt")


class TestDataConfig:
    def test_default_max_upload_size(self):
        cfg = DataConfig()
        assert cfg.max_upload_size == 10 * 1024 * 1024  # 10MB

    def test_default_chunk_size(self):
        cfg = DataConfig()
        assert cfg.chunk_size == 1000

    def test_default_chunk_overlap(self):
        cfg = DataConfig()
        assert cfg.chunk_overlap == 200

    def test_default_cleanup_days(self):
        cfg = DataConfig()
        assert cfg.cleanup_days == 30

    def test_allowed_extensions_include_common(self):
        cfg = DataConfig()
        for ext in [".txt", ".py", ".json", ".pdf", ".png"]:
            assert ext in cfg.allowed_extensions

    def test_paths_are_path_objects(self):
        cfg = DataConfig()
        assert isinstance(cfg.data_dir, Path)
        assert isinstance(cfg.uploads_dir, Path)
        assert isinstance(cfg.sessions_file, Path)


class TestLLMConfig:
    def test_defaults(self):
        cfg = LLMConfig()
        assert cfg.default_host == "localhost"
        assert cfg.openai_api_key is None
        assert cfg.max_context_messages == 90
        assert cfg.request_timeout == 20
        assert cfg.llm_stream_timeout == 30
        assert cfg.llm_max_tokens == 4096
        assert cfg.llm_temperature == 0.3

    def test_openai_compat_path(self):
        cfg = LLMConfig()
        assert cfg.openai_compat_path == "/v1/chat/completions"

    @patch.dict(os.environ, {"LLM_DEFAULT_HOST": "myserver.local"})
    def test_env_override(self):
        cfg = LLMConfig()
        assert cfg.default_host == "myserver.local"


class TestSearchConfig:
    def test_defaults(self):
        cfg = SearchConfig()
        assert cfg.searxng_instance == "http://localhost:8080"
        assert cfg.web_search_count == 10
        assert cfg.web_search_max_pages == 6
        assert cfg.web_search_max_workers == 4
        assert cfg.research_timeout == 300

    def test_api_keys_optional(self):
        cfg = SearchConfig()
        assert cfg.serpapi_key is None
        assert cfg.google_api_key is None
        assert cfg.google_cx is None


class TestSecurityConfig:
    def test_rate_limiting_defaults(self):
        cfg = SecurityConfig()
        assert cfg.max_concurrent_uploads == 3
        assert cfg.upload_rate_limit == 5
        assert cfg.upload_rate_window == 60

    def test_dangerous_file_types(self):
        cfg = SecurityConfig()
        assert "application/x-executable" in cfg.dangerous_file_types
        assert "application/x-msdownload" in cfg.dangerous_file_types

    def test_dangerous_extensions(self):
        cfg = SecurityConfig()
        assert ".exe" in cfg.dangerous_extensions
        assert ".dll" in cfg.dangerous_extensions
        assert ".bat" in cfg.dangerous_extensions
        assert ".sh" in cfg.dangerous_extensions

    def test_max_file_size(self):
        cfg = SecurityConfig()
        assert cfg.max_file_size == 10 * 1024 * 1024


class TestAppConfig:
    def test_global_config_exists(self):
        assert config is not None
        assert isinstance(config, AppConfig)

    def test_config_has_all_sub_configs(self):
        assert hasattr(config, "data")
        assert hasattr(config, "llm")
        assert hasattr(config, "search")
        assert hasattr(config, "security")

    def test_debug_default_false(self):
        assert config.debug is False

    def test_log_level_default_info(self):
        assert config.log_level == "INFO"


class TestCreateDirectories:
    def test_create_directories_idempotent(self, tmp_path):
        """create_directories does not fail if dirs already exist."""
        # This just verifies it doesn't raise
        create_directories()


class TestValidateConfig:
    def test_validate_config_runs_without_error(self):
        # validate_config() is already called at import time,
        # but verify calling it again doesn't raise
        validate_config()
