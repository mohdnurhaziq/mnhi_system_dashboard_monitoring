<?php

namespace App\Services\Llm;

class PromptOptimizer
{
    public function __construct(private OllamaClient $client) {}

    /**
     * Is the local LLM reachable so optimization can run?
     */
    public function available(): bool
    {
        return $this->client->isAvailable();
    }

    /**
     * Turn an auto-assembled context bundle into a focused, high-quality prompt
     * brief for an AI coding agent. Returns the brief text, or null on failure
     * (caller falls back to the plain template).
     */
    public function optimize(string $assembledContext): ?string
    {
        $brief = $this->client->generateText($assembledContext, $this->systemPrompt());

        return $brief !== null && trim($brief) !== '' ? trim($brief) : null;
    }

    private function systemPrompt(): string
    {
        return <<<'SYS'
        You are an expert prompt engineer. You are given an auto-generated context
        bundle about one software project: its metadata, detected issues/gaps,
        README, recent git history, file tree, and key-file excerpts, plus a task.

        Rewrite this into a single, focused, high-quality prompt to hand to an AI
        coding agent (Claude Code) that is working directly inside that project's
        repository. Follow these rules:
        - Open with a one-paragraph objective stating what to accomplish and why.
        - Give concrete, ordered steps — highest-severity / highest-impact first.
        - Add a short "Acceptance criteria" list the agent can verify when done.
        - Be specific to THIS project. Do NOT invent files, features, or facts.
        - Preserve exact file paths, commands, and identifiers verbatim.
        - Do not include the raw file dumps back verbatim; refer to them instead.
        - Output ONLY the finished prompt in Markdown. No preamble or meta-comment.
        SYS;
    }
}
