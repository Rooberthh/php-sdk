// Parallel Planner with Review, ending in pull requests — three-phase loop
//
// This template drives a multi-phase workflow:
//   Phase 1 (Plan):             An agent analyzes the ready issues, builds a
//                               dependency graph, and outputs a <plan> JSON
//                               listing the issues that can be worked now.
//   Phase 2 (Execute + Review): For each issue, a sandbox is created via
//                               createSandbox(). The implementer runs first
//                               (100 iterations). If it produces commits, a
//                               reviewer runs in the same sandbox on the same
//                               branch (1 iteration). All issue pipelines run
//                               concurrently via Promise.allSettled().
//   Phase 3 (Pull request):     Each reviewed branch is pushed and opened as a
//                               pull request that closes its issue on merge.
//                               Nothing is merged here — that stays a human call.
//
// The outer loop repeats up to MAX_ITERATIONS times so that issues the planner
// held back as blocked are picked up once they become unblocked.
//
// Usage:
//   npx tsx .sandcastle/main.mts
// Or add to package.json:
//   "scripts": { "sandcastle": "npx tsx .sandcastle/main.mts" }

import { execFileSync } from "node:child_process";
import * as sandcastle from "@ai-hero/sandcastle";
import { docker } from "@ai-hero/sandcastle/sandboxes/docker";
import { z } from "zod";

// The planner emits its plan as JSON inside <plan> tags; Output.object extracts
// and validates it against this schema. We use Zod here, but any Standard
// Schema validator works just as well — Valibot, ArkType, etc. See
// https://standardschema.dev.
const planSchema = z.object({
  issues: z.array(z.object({ id: z.string(), title: z.string() })),
});

// ---------------------------------------------------------------------------
// Configuration
// ---------------------------------------------------------------------------

// Maximum number of plan→execute→pull-request cycles before stopping.
// Raise this if your backlog is large; lower it for a quick smoke-test run.
const MAX_ITERATIONS = 10;

// Hooks run inside the sandbox before the agent starts each iteration.
// composer install ensures the sandbox always has fresh dependencies.
const hooks = {
  sandbox: { onSandboxReady: [{ command: "composer install --no-interaction --no-progress --prefer-dist" }] },
};

// Copy node_modules and vendor from the host into the worktree before each
// sandbox starts. Avoids a full install from scratch; the hook above handles
// platform-specific binaries and any packages added since the last copy.
const copyToWorktree = ["node_modules", "vendor"];

// Every issue is worked on its own branch, named from the issue number so a
// re-run picks up the same branch and an open pull request can be matched back
// to its issue.
const BRANCH_PREFIX = "sandcastle/issue-";

// Pull requests target the branch this script was started from.
const BASE_BRANCH = execFileSync("git", ["rev-parse", "--abbrev-ref", "HEAD"], { encoding: "utf8" }).trim();

// ---------------------------------------------------------------------------
// GitHub
// ---------------------------------------------------------------------------

type Issue = {
  number: number;
  title: string;
  body: string;
  labels: string[];
  comments: string[];
};

function gh(args: string[]): string {
  return execFileSync("gh", args, { encoding: "utf8" });
}

// Open issues labelled ready-for-agent, split by whether a pull request for
// them is already open. The label is filtered with jq rather than --label,
// which goes through GitHub's search index and can lag minutes behind label
// changes.
function issueQueue(): { ready: Issue[]; inReview: Issue[] } {
  const issues: Issue[] = JSON.parse(
    gh([
      "issue", "list", "--state", "open", "--limit", "100",
      "--json", "number,title,body,labels,comments",
      "--jq", '[.[] | select(any(.labels[]; .name == "ready-for-agent")) | {number, title, body, labels: [.labels[].name], comments: [.comments[].body]}]',
    ]),
  );

  const openBranches = new Set<string>(
    JSON.parse(gh(["pr", "list", "--state", "open", "--limit", "100", "--json", "headRefName", "--jq", "[.[].headRefName]"])),
  );

  const hasOpenPullRequest = (issue: Issue) => openBranches.has(`${BRANCH_PREFIX}${issue.number}`);

  return {
    ready: issues.filter((issue) => !hasOpenPullRequest(issue)),
    inReview: issues.filter(hasOpenPullRequest),
  };
}

// Push the branch and open a pull request that closes the issue when merged.
// The commit messages carry the agents' notes on what they did and why, so
// they become the description.
function openPullRequest(issue: { id: string; title: string }, branch: string): string {
  execFileSync("git", ["push", "--set-upstream", "origin", branch], { stdio: "inherit" });

  const commits = execFileSync(
    "git",
    ["log", "--reverse", "--format=### %s%n%n%b", `${BASE_BRANCH}..${branch}`],
    { encoding: "utf8" },
  );

  return gh([
    "pr", "create",
    "--base", BASE_BRANCH,
    "--head", branch,
    "--title", issue.title,
    "--body", `Closes #${issue.id}\n\n${commits.trim()}\n\n🤖 Generated with [Claude Code](https://claude.com/claude-code)`,
  ]).trim();
}

// ---------------------------------------------------------------------------
// Main loop
// ---------------------------------------------------------------------------

for (let iteration = 1; iteration <= MAX_ITERATIONS; iteration++) {
  console.log(`\n=== Iteration ${iteration}/${MAX_ITERATIONS} ===\n`);

  const queue = issueQueue();

  if (queue.ready.length === 0) {
    console.log("No ready issues without an open pull request. Exiting.");
    break;
  }

  // -------------------------------------------------------------------------
  // Phase 1: Plan
  //
  // The planning agent reads the ready issues, builds a dependency graph, and
  // selects the issues that can be worked in parallel right now. Issues with
  // an open pull request are shown too: they are not merged yet, so anything
  // that depends on them is still blocked.
  //
  // It outputs a <plan> JSON block — Output.object parses and validates it.
  // -------------------------------------------------------------------------
  const plan = await sandcastle.run({
    hooks,
    sandbox: docker(),
    name: "planner",
    // One iteration is enough: the planner just needs to read and reason,
    // not write code. (Structured output requires maxIterations: 1.)
    maxIterations: 1,
    agent: sandcastle.claudeCode("claude-opus-4-8"),
    promptFile: "./.sandcastle/plan-prompt.md",
    promptArgs: {
      READY_ISSUES: JSON.stringify(queue.ready, null, 2),
      IN_REVIEW_ISSUES: JSON.stringify(queue.inReview, null, 2),
    },
    // Extract and validate the <plan> JSON into a typed object. Throws
    // StructuredOutputError if the tag is missing, the JSON is malformed, or
    // validation fails — which aborts the loop.
    output: sandcastle.Output.object({ tag: "plan", schema: planSchema }),
  });

  // Only ever work issues from the ready list, whatever the planner returned.
  const readyIds = new Set(queue.ready.map((issue) => String(issue.number)));
  const issues = plan.output.issues.filter((issue) => readyIds.has(issue.id));

  if (issues.length === 0) {
    // Everything ready is blocked, most likely by issues still in review.
    console.log("No unblocked issues to work on. Exiting.");
    break;
  }

  console.log(
    `Planning complete. ${issues.length} issue(s) to work in parallel:`,
  );
  for (const issue of issues) {
    console.log(`  #${issue.id}: ${issue.title} → ${BRANCH_PREFIX}${issue.id}`);
  }

  // -------------------------------------------------------------------------
  // Phase 2: Execute + Review, then Phase 3: Pull request
  //
  // For each issue, create a sandbox via createSandbox() so the implementer
  // and reviewer share the same sandbox instance per branch. The implementer
  // runs first; if it produces commits, the reviewer runs in the same sandbox,
  // and once the sandbox is closed the branch is opened as a pull request.
  //
  // Promise.allSettled means one failing pipeline doesn't cancel the others.
  // -------------------------------------------------------------------------

  const settled = await Promise.allSettled(
    issues.map(async (issue) => {
      const branch = `${BRANCH_PREFIX}${issue.id}`;

      const sandbox = await sandcastle.createSandbox({
        branch,
        sandbox: docker(),
        hooks,
        copyToWorktree,
      });

      let commits = 0;

      try {
        // Run the implementer
        const implement = await sandbox.run({
          name: "implementer",
          maxIterations: 100,
          agent: sandcastle.claudeCode("claude-opus-4-8"),
          promptFile: "./.sandcastle/implement-prompt.md",
          promptArgs: {
            TASK_ID: issue.id,
            ISSUE_TITLE: issue.title,
            BRANCH: branch,
          },
        });

        commits = implement.commits.length;

        // Only review if the implementer produced commits
        if (commits > 0) {
          const review = await sandbox.run({
            name: "reviewer",
            maxIterations: 1,
            agent: sandcastle.claudeCode("claude-opus-4-8"),
            promptFile: "./.sandcastle/review-prompt.md",
            promptArgs: {
              BRANCH: branch,
            },
          });

          commits += review.commits.length;
        }
      } finally {
        await sandbox.close();
      }

      if (commits === 0) {
        return null;
      }

      return openPullRequest(issue, branch);
    }),
  );

  const pullRequests: string[] = [];

  for (const [i, outcome] of settled.entries()) {
    if (outcome.status === "rejected") {
      // Network error, sandbox crash, failed push, etc.
      console.error(`  ✗ #${issues[i]!.id} failed: ${outcome.reason}`);
    } else if (outcome.value === null) {
      console.log(`  - #${issues[i]!.id}: no commits, no pull request`);
    } else {
      pullRequests.push(outcome.value);
    }
  }

  console.log(`\n${pullRequests.length} pull request(s) opened:`);
  for (const url of pullRequests) {
    console.log(`  ${url}`);
  }

  if (pullRequests.length === 0) {
    // Re-planning would hand the same issues to the same agents, so stop
    // rather than spend the remaining iterations repeating this one.
    console.log("Nothing to review. Stopping.");
    break;
  }
}

console.log("\nAll done.");
