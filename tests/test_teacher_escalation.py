"""Tests for src/teacher_escalation.py — is_self_hosted and evaluate_turn_regex."""
import pytest

from src.teacher_escalation import (
    _SOTA_HOSTS,
    is_self_hosted,
    evaluate_turn_regex,
    _TOOL_ERROR_PATTERNS,
    _REPLY_GIVE_UP_PATTERNS,
)


class TestIsSelfHosted:
    """Test the is_self_hosted endpoint classification."""

    @pytest.mark.parametrize("url", [
        "https://api.openai.com/v1/chat/completions",
        "https://api.anthropic.com/v1/messages",
        "https://api.deepseek.com/v1/chat/completions",
        "https://api.mistral.ai/v1/models",
        "https://api.together.xyz/v1/completions",
        "https://api.groq.com/openai/v1/models",
        "https://openrouter.ai/api/v1/chat/completions",
        "https://generativelanguage.googleapis.com/v1/models",
    ])
    def test_known_sota_hosts_not_self_hosted(self, url):
        assert is_self_hosted(url) is False

    @pytest.mark.parametrize("url", [
        "http://localhost:8080/v1/chat/completions",
        "http://192.168.1.100:11434/v1/chat/completions",
        "http://my-server.local:8000/v1/completions",
        "https://my-llm.example.com/v1/chat",
        "http://10.0.0.5:5000/api/generate",
    ])
    def test_self_hosted_endpoints(self, url):
        assert is_self_hosted(url) is True

    def test_empty_string_is_self_hosted(self):
        assert is_self_hosted("") is True

    def test_none_like_empty_is_self_hosted(self):
        # Empty string triggers early return
        assert is_self_hosted("") is True

    def test_malformed_url_is_self_hosted(self):
        # urlparse might not extract a hostname
        assert is_self_hosted("not-a-url") is True

    def test_url_with_port_recognized(self):
        # SOTA host with a port should still be recognized
        assert is_self_hosted("https://api.openai.com:443/v1") is False


class TestEvaluateTurnRegex:
    """Test the Tier 1 regex-based failure detection."""

    def test_ok_when_no_issues(self):
        status, reason = evaluate_turn_regex(
            tool_results=[{"output": "File saved successfully"}],
            agent_reply="I've saved the file for you.",
        )
        assert status == "ok"
        assert reason is None

    def test_ok_with_empty_inputs(self):
        status, reason = evaluate_turn_regex([], "")
        assert status == "ok"
        assert reason is None

    def test_ok_with_none_tool_results(self):
        status, reason = evaluate_turn_regex(None, "All done.")
        assert status == "ok"
        assert reason is None

    # -- Tool error detection --

    def test_detects_tool_error_field(self):
        status, reason = evaluate_turn_regex(
            tool_results=[{"error": "Permission denied"}],
            agent_reply="Let me try again.",
        )
        assert status == "failure"
        assert "Permission denied" in reason

    def test_detects_unknown_action_pattern(self):
        status, reason = evaluate_turn_regex(
            tool_results=[{"output": "Unknown action 'switch_tab'"}],
            agent_reply="",
        )
        assert status == "failure"
        assert "Unknown action" in reason

    def test_detects_failed_to_pattern(self):
        status, reason = evaluate_turn_regex(
            tool_results=[{"results": "Failed to open file: /tmp/missing.txt"}],
            agent_reply="",
        )
        assert status == "failure"

    def test_detects_not_found_pattern(self):
        status, reason = evaluate_turn_regex(
            tool_results=[{"output": "Command not found"}],
            agent_reply="",
        )
        assert status == "failure"

    def test_detects_invalid_pattern(self):
        status, reason = evaluate_turn_regex(
            tool_results=[{"response": "Invalid argument: --nosuchflag"}],
            agent_reply="",
        )
        assert status == "failure"

    def test_detects_error_colon_pattern(self):
        status, reason = evaluate_turn_regex(
            tool_results=[{"output": "error: could not connect to server"}],
            agent_reply="",
        )
        assert status == "failure"

    # -- Agent give-up detection --

    def test_detects_no_tool(self):
        status, reason = evaluate_turn_regex(
            tool_results=[],
            agent_reply="I don't have a tool that can do that.",
        )
        assert status == "failure"
        assert "give-up" in reason

    def test_detects_cannot_do(self):
        status, reason = evaluate_turn_regex(
            tool_results=[],
            agent_reply="I can't do that operation in my current setup.",
        )
        assert status == "failure"

    def test_detects_not_sure_which(self):
        status, reason = evaluate_turn_regex(
            tool_results=[],
            agent_reply="I'm not sure which file you want me to edit.",
        )
        assert status == "failure"

    def test_detects_could_you_tell_me(self):
        status, reason = evaluate_turn_regex(
            tool_results=[],
            agent_reply="Could you tell me which session to switch to?",
        )
        assert status == "failure"

    def test_detects_unable_to_open(self):
        status, reason = evaluate_turn_regex(
            tool_results=[],
            agent_reply="I was unable to open that document.",
        )
        assert status == "failure"

    def test_detects_doesnt_exist(self):
        status, reason = evaluate_turn_regex(
            tool_results=[],
            agent_reply="That file doesn't exist in the workspace.",
        )
        assert status == "failure"

    # -- Non-dict entries ignored gracefully --

    def test_non_dict_tool_result_ignored(self):
        status, reason = evaluate_turn_regex(
            tool_results=["just a string", 42, None],
            agent_reply="Done.",
        )
        assert status == "ok"
        assert reason is None
