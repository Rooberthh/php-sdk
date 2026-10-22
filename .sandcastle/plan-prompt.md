# ISSUES

Here are the issues that are ready for work:

<ready-issues-json>

{{READY_ISSUES}}

</ready-issues-json>

These issues already have an open pull request waiting for human review. They are **not merged yet**, so their changes are not on the base branch:

<in-review-issues-json>

{{IN_REVIEW_ISSUES}}

</in-review-issues-json>

# TASK

Analyze the ready issues and build a dependency graph. For each ready issue, determine whether it **blocks** or **is blocked by** any other ready or in-review issue.

An issue B is **blocked by** issue A if:

- B requires code or infrastructure that A introduces
- B and A modify overlapping files or modules, making concurrent work likely to produce merge conflicts
- B's requirements depend on a decision or API shape that A will establish

An in-review issue counts as open: anything it blocks stays blocked until its pull request is merged.

A ready issue is **unblocked** if it has zero blocking dependencies on other ready or in-review issues.

# OUTPUT

Output your plan as a JSON object wrapped in `<plan>` tags, using the issue number as the `id`:

<plan>
{"issues": [{"id": "42", "title": "Fix auth bug"}]}
</plan>

Include only unblocked ready issues. Never include an in-review issue. If every ready issue is blocked only by other ready issues, include the single highest-priority candidate (the one with the fewest or weakest dependencies). If they are blocked by in-review issues, include nothing.

Always emit the `<plan>` tags, even when there is nothing to do. If there are no issues to work on at all, output `<plan>{"issues": []}</plan>` so the run can exit cleanly.
