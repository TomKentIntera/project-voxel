# platform_legacy Agent Instructions

## Purpose

`platform_legacy` is a reference-only folder. It exists for historical/legacy context and should not be used for active product development.

## Mandatory Rules

- Do NOT develop new features in `platform_legacy`.
- Do NOT implement bug fixes in `platform_legacy` unless explicitly requested for legacy-reference maintenance.
- Do NOT treat code in this folder as the primary source for current behavior.
- All active development must happen in:
  - `platform/backend`
  - `platform/frontend`

## When Working Here

- Prefer reading for reference only.
- If functionality needs to change, implement it in `platform/backend` or `platform/frontend`.
- Only update files in `platform_legacy` when explicitly asked to maintain or document legacy behavior.
