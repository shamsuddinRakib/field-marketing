# Plan.md — Database Insight Tools for the AI Chatbot

## 1. What I Understand

### Current state of the chatbot
- Laravel app lives in `core/`. You have integrated the **Laravel AI SDK** (`Laravel\Ai`).
- Agent: `core/app/Ai/Agents/MarketingRepresentativeDataAssistant.php`
  - Provider: Ollama, model `beam:cloud`.
  - Implements `Agent`, `Conversational`, `HasTools` via `Promptable` trait.
  - Currently has **one tool**: `core/app/Ai/Tools/WebSearchTool.php`.
- Tool contract (I will follow the exact same pattern):
  ```php
  class XxxTool implements Tool {
      public function description(): Stringable|string;          // tells the LLM when/how to use it
      public function handle(Request $request): Stringable|string; // returns data (returned to the LLM)
      public function schema(JsonSchema $schema): array;          // input JSON schema (params)
  }
  ```
- HTTP entry: `POST /chat/send` → `core/app/Http/Controllers/ai/ChatAssistantController.php` → `ChatAssistantController::ask()`.
- Conversation tables (`agent_conversations`, `agent_conversation_messages`) exist via the SDK migration.

### Data model that matters (verified against the live `field-marketing` DB)

| Domain | Table(s) | Key columns |
|---|---|---|
| Marketing representative | `marketing_representatives`, `users` | Reps are users: `marketing_representatives.user_id` → `users.id` (name lives on `users`). Also `employee_id`, `mobile`, `branch_id`, `district_id`, `division_id`, `status` (soft deletes) |
| Daily visits | `daily_visits` | `user_id`, `teacher_id`, `library_id`, `quantity`, `status` (pending/approved), `created_at` |
| Spot sales | `spot_sales`, `spot_sale_items` | Head: `user_id`, `total`, `teacher_id`/`library_id`, `status` (pending/approved). Items: `product_id`, `price`, `quantity`, `total` |
| Product distribution | `product_distributions`, `product_distribution_items` | Head: `user_id`, `status` (pending/delivered). Items: `product_id`, `quantity` |
| Specimen assignment | `assign_specimens`, `assign_specimen_items` | Head: `user_id`, `teacher_id`/`library_id`, `status`. Items: `product_id`, `quantity` |
| Teachers / Libraries | `teachers`, `libraries`, `institutions` | Names, district/upazila, status |
| Book requests | `book_requests` | `marketing_representative_id` (→ `marketing_representatives.id`), `product_id`, `quantity`, `request_date`, `status` (pending/approved/rejected) |
| Book returns | `book_returns` | `marketing_representative_id`, `institution_id`, `issued_quantity`, `returned_quantity`, `received_date`, `status` |
| Expense | `expenses`, `expense_items`, `expense_categories` | `user_id`, `expense_date`, `total_amount`, `status` (draft/posted/cancelled); items → `expense_category_id`, `amount` |
| Fund request | `fund_requests` | `user_id`, `amount`, `status` (pending/approved/rejected) |
| Fund balance | `current_funds` | `user_id`, `balance` (current credit per MR) |
| Fund distribution | `fund_distributions` | `user_id`, `amount`, `status` (pending/delivered) |
| Fund transfer ledger | `fund_transfer_ledgers` | `ref_type`, `txn_id`, `amount`, `direction` (IN/OUT) |
| MR stock | `current_stocks` | `user_id`, `product_id`, `current_stock` — the only per-rep stock table (via the `CurrentStock` model) |

### Important modeling notes (affect design)
1. **A representative is identified by `user_id`** in visits/sales/expenses/funds, but by `marketing_representatives.id` in `book_requests`/`book_returns` (`marketing_representative_id`). Tools must join through both paths.
2. Per-rep stock lives solely in `current_stocks` (`CurrentStock` model). Stock tools query **only** this table — no other stock source is considered (`mr_product_current_stocks` is not available and `mr_locations` is excluded from scope, as you decided).
3. Expenses have `status` draft/posted/cancelled — insight tools must use **posted** by default (with an option to include drafts).
4. Money is BDT; timestamps are `created_at` except expenses which also have a date column `expense_date` (we'll group/filter expenses on `expense_date`).

---

## 2. Design Principles for the Tools

1. **Read-only**: every tool only runs SELECT/aggregation queries. No inserts, no updates.
2. **Convert natural language → parameters**: the LLM decides dates/limits/rep names; tools do all heavy SQL. The LLM never writes raw SQL.
3. **Default time window = previous calendar month (never all-time)**. Ranking/comparison stats ("most spot sale", "top visits", "most efficient", etc.) **must** be scoped to a date range. If the user states a range → use it. If not → the tool defaults to the **previous calendar month** (e.g. asked on 21 Oct 2026 → 01 Sep–30 Sep 2026). This keeps answers stable and relevant as the data grows instead of silently spanning all history. Every time-ranged tool echoes the resolved range in its output, so the LLM always tells the user which period was used and can redo with an explicit range on request. Current-state tools (`fund_status`, `rep_stock_status`, lookups, `teacher_library_stats`) have no window — they show present values.
4. **Other common shared parameters** across tools:
   - `from_date`, `to_date` — optional `Y-m-d` strings. If both omitted → previous calendar month (rule #3). Today's date is injected into the agent instructions (`now()->format('Y-m-d')`) so the LLM resolves "this month", "last month" etc. correctly.
   - `limit` — default 5, max 50.
   - `representative` — optional name / employee_id / email / user_id; resolved with a shared helper (fuzzy + exact match). Ambiguous → return candidate list so the LLM asks for clarification.
5. **Output format**: compact JSON-ish string with **human names resolved** (rep name, product name, teacher/library name — never raw IDs alone). Numbers rounded to 2 decimals. Money returned as plain numbers; instruction tells the LLM to label it as BDT/Tk.
6. **Empty results handled**: tools return a clear message like `No spot sales found in the given range.` instead of an empty array — the LLM can then reply naturally.
7. **Grouped, not duplicated**: overlapping questions share one tool (e.g. one ranking tool covers both "top 5 visits" and "how many visits did rep X make").

---

## 3. Proposed Tools (17)

> **Scope note:** every tool below that lists `from_date`/`to_date` defaults to the **previous calendar month** when the user gives no range (§2 rule 3) and echoes the resolved period in its output. Current-state tools (A1, A2, F1, G1, H4) have no time window — they read present values only. `mr_locations` and `mr_product_current_stocks` are excluded everywhere.

### A. Reference / lookup tools

**A1. `ListRepresentativesTool`** — `list_representatives`
- Purpose: resolve who the representatives are before doing other queries (name, employee_id, mobile, branch, district/division, active status). Optional `search` term.
- Used for: "Who are our marketing representatives?", "Is there a rep named Sujon?", resolving ambiguous names in follow-up questions.

**A2. `ListProductsTool`** — `list_products`
- Purpose: product catalog with name, price, category/brand; optional `search`. Helps the LLM interprproduct names from sales/stock tools and quote prices.
- Used for: "What products do we sell?", "How much does product X cost?"

### B. Visits

**B1. `DailyVisitRankingTool`** — `daily_visit_ranking`
- Inputs: `from_date`, `to_date`, `limit`, optional `representative` (then returns only that rep's count + breakdown teacher vs library visits), optional `status` filter (pending/approved/all).
- Output: ranked list: `rank, rep_name, total_visits (+ per teacher/library split, distinct teachers, distinct libraries)`.
- Used for: "Top five reps by daily visits", "How many visits did Md. Rakib make last month?", "Who visited the most teachers?"

### B2. `MostVisitedInstitutionsTool` — `most_visited_institutions`
- Inputs: `from_date`, `to_date`, `limit`, optional `rep`.
- Output: most-visited teachers and libraries by a rep (or overall), with visit counts and last-visit date.
- Used for: "Which teachers does rep X visit most?", "Which libraries are untouched?" (list zero-visit libraries when `exclude_visited` style filter is handy).

### C. Spot sales

**C1. `SpotSaleRankingTool`** — `spot_sale_ranking`
- Inputs: `from_date`, `to_date`, `limit`, optional `representative`, optional `status` (pending/approved/all).
- Output: ranked list: `rank, rep_name, sales_count, total_amount (Tk), avg sale value`.
- Used for: **"Which representative made the most spot sale?"**, "Spot sales for rep X this week."

**C2. `SpotSaleTopProductsTool`** — `spot_sale_top_products`
- Inputs: `from_date`, `to_date`, `limit`, optional `representative`.
- Output: best-selling products by quantity and revenue (`product_name, qty, revenue`).
- Used for: "Which books sell most in spot sales?", "Product-wise performance this month."

**C3. `SpotSaleHistoryTool`** — `spot_sale_history`
- Inputs: `from_date`, `to_date`, `limit`, optional `representative`, optional `product`.
- Output: chronological/candidate list of spot sales with items (product, qty, price, total), status, customer (teacher/library name), pending vs approved summary.
- Used for: "Show me rep X's sales last week with details", "How many pending spot sales are there?"

### D. Expenses

**D1. `ExpenseSummaryTool`** — `representative_expense_summary`
- Inputs: `from_date`, `to_date`, optional `representative` (if omitted → totals per rep, ranked), optional `category` name, optional `status` (default posted).
- Output: for each rep: `total_expense, expense_count, avg expense, category breakdown, peak period`.
- Used for: **"Representative expense within a time range"**, "What did rep X spend on conveyance last month?", "Where is most money going?"

### E. Efficiency (cross-metric)

**E1. `CostPerResultTool`** — `cost_per_result`
- Inputs: `from_date`, `to_date`, required-or-default `representative` (or `all`), `result_type` = visits (default) / spot_sales.
- Output: per rep: `total_visits, total_expense, cost_per_visit (expense ÷ visits), visits_per_1000_tk, comparison vs All-MR average`. Guard: when visits = 0 → explicitly say "no visits recorded".
- Used for: **"Representative cost per result with a range"** — e.g. "What is Mr. X's cost per visit in Jan–Mar?"

**E2. `EfficiencyRankingTool`** — `efficiency_ranking`
- Inputs: `from_date`, `to_date`, `limit`, `result_type` = visits (default) / spot_sales, optional `min_visits` floor to filter out reps with too little data.
- Output: Ranked most efficient reps (lowest cost per visit / highest visits per Tk), including average and each rep's row.
- Used for: **"Most efficient representative in terms of cost per result within a time range"**.

### F. Funds

**F1. `FundStatusTool`** — `fund_status`
- Inputs: optional `representative`.
- Output: current `current_funds.balance` per rep (or the named rep), total available balance across all MRs, top receivers. Also `fund_requests`/`fund_distributions` quick totals.
- Used for: **"Current fund status"**, "How much money does rep X have now?"

**F2. `FundFlowSummaryTool`** — `fund_flow_summary`
- Inputs: `from_date`, `to_date`, optional `representative`.
- Output: in range: fund requests (count/amount per status per rep), fund distributions delivered (total disbursed), ledger IN/OUT totals.
- Used for: "How much did we disburse to reps this month?", "Which reps have pending fund requests?", "How much did rep X request in September?"

### G. Stock

**G1. `RepStockStatusTool`** — `rep_stock_status`
- Inputs: optional `representative`, optional `product` name, optional `only_low_stock` boolean + `threshold` (default 5).
- Output: per rep: product-wise `current_stock` (product name, brand/category if available), total units held; low-stock warnings; optionally never-assigned products.
- Used for: **"Representative product stock status"**, "Which reps are out of stock of product X?", "Show rep X's stock."

### H. Distribution / specimen / book modules

**H1. `ProductDistributionSummaryTool`** — `product_distribution_summary`
- Inputs: `from_date`, `to_date`, optional `representative`.
- Output: distributions per rep: doc count, total quantity distributed, top products, pending vs delivered.
- Used for: "How many products were distributed to representative X in Q1?", "Who received the most stock?"

**H2. `SpecimenSummaryTool`** — `specimen_summary`
- Inputs: `from_date`, `to_date`, optional `representative`, optional scope `teacher|library`.
- Output: specimens assigned per rep: specimen docs, total quantity, distinct teachers/libraries covered, top assigned products.
- Used for: "How many specimens has rep X assigned to teachers?", "Where do we distribute most specimens?"

**H3. `BookRequestReturnSummaryTool`** — `book_request_return_summary`
- Inputs: `from_date`, `to_date`, optional `representative`.
- Output: book requests (qty per status: pending/approved/rejected) and book returns (issued vs returned, return rate %, pending returns) per rep; `received_by` totals.
- Used for: "How many books did rep X request and return?", "Which rep has pending returns?"

**H4. `TeacherLibraryStatsTool`** — `teacher_library_stats`
- Inputs: optional `district`/`division` name, optional `type` (`teachers`|`libraries`), optional `rep`.
- Output: counts of teachers/libraries (active vs inactive), distribution by district/division, those with specimens assigned vs never assigned.
- Used for: "How many teachers do we have in Dhaka?", "Which libraries have we never assigned specimens to?"

---

### Question → tool map (sanity check)

| Your requested insight | Tool |
|---|---|
| Which rep made most spot sale | C1 `spot_sale_ranking` |
| Top 5 reps by daily visits | B1 `daily_visit_ranking` |
| Rep product stock status | G1 `rep_stock_status` |
| Current fund status | F1 `fund_status` |
| Rep expense within a range | D1 `representative_expense_summary` |
| Cost per result (visits ÷ expense) with range | E1 `cost_per_result` |
| Most efficient rep by cost per result in range | E2 `efficiency_ranking` |
| Extra value: teacher/library, distribution, specimen, book req/return, fund flow analytics | B2, C2, C3, F2, H1–H4, A1–A2 |

---

## 4. Implementation Plan

### Files to create
```
core/app/Ai/Concerns/FormatsAgentOutput.php      — JSON+money+empty-data helpers
core/app/Ai/Concerns/ResolvesTimeframe.php       — parse/validate from_date, to_date, defaults
core/app/Ai/Concerns/ResolvesUser.php            — representative-name → user_id resolution
core/app/Ai/Tools/ListRepresentativesTool.php
core/app/Ai/Tools/ListProductsTool.php
core/app/Ai/Tools/DailyVisitRankingTool.php
core/app/Ai/Tools/MostVisitedInstitutionsTool.php
core/app/Ai/Tools/SpotSaleRankingTool.php
core/app/Ai/Tools/SpotSaleTopProductsTool.php
core/app/Ai/Tools/SpotSaleHistoryTool.php
core/app/Ai/Tools/ExpenseSummaryTool.php
core/app/Ai/Tools/CostPerResultTool.php
core/app/Ai/Tools/EfficiencyRankingTool.php
core/app/Ai/Tools/FundStatusTool.php
core/app/Ai/Tools/FundFlowSummaryTool.php
core/app/Ai/Tools/RepStockStatusTool.php
core/app/Ai/Tools/ProductDistributionSummaryTool.php
core/app/Ai/Tools/SpecimenSummaryTool.php
core/app/Ai/Tools/BookRequestReturnSummaryTool.php
core/app/Ai/Tools/TeacherLibraryStatsTool.php
```

### Files to modify
- `core/app/Ai/Agents/MarketingRepresentativeDataAssistant.php`
  - Register all 17 tools in `tools()`.
  - Rewrite `instructions()`: dynamic today's date, persona ("business insights analyst for our field marketing company"), tool usage guidance (e.g. "always prefer a data tool for questions about our own data; use webSearchTool only for general knowledge"), money is BDT ("Tk"), answer in the user's language, show numbers in small tables/lists, explain the date range used.
- Nothing else — controller/route stay as-is.

### Implementation details
- Use Eloquent/Query Builder aggregations (`join users`, `groupBy`, `orderByRaw`, `SUM/COUNT`); every tool wrapped in try/catch → returns a graceful error string like the WebSearchTool does (never leaks stack traces to the LLM/PHP user).
- Representative resolution helper matches `users.name` (LIKE), `users.email`, `employees`, or numeric id; returns `(user_id, name)` or candidates list.
- Date params validated with `strtotime`-style check; non-past dates clamped; `from_date > to_date` → error message the LLM can recover from.
- **Default-range helper (`ResolvesTimeframe`)**: one place implements the previous-calendar-month default + date parsing, so every time-ranged tool behaves identically and each output includes `"period_used": {from, to, note: "default applied: previous month"}` when defaults were used.
- Expense default status = `posted` (documented in tool description so the LLM can override via param).
- Visits/sales count all statuses by default, `status` param to restrict (approved only if the user says "approved").

---

## 5. Edge Cases & Guards
- Division by zero in cost-per-result (0 visits / 0 expense) → explicit "no data" text, never INF/null.
- **Default range transparently applied**: when the user gave no range, output states the period used (previous month) — the LLM must mention it in the answer so users aren't misled; if the user wanted all-time or a custom range they can say so and the query is re-run.
- Rep name ambiguity → candidate list tool output; LLM asks user which one.
- Ranges with no data → friendly empty message with the range echoed.
- Large lists capped (`limit`, max 50) to protect the context window; big outputs summarised with a "… and N more" note.
- Soft-deleted reps excluded from rankings; still resolvable if user asks directly (join includes trashed `users`/`marketing_representatives` where sensible).

## 6. Verification Plan
1. `php artisan tinker` harness (temporary script in `log/`-style scratch or `core/scripts/`) calling every tool's `handle()` with: no params, a fixed range, a named rep, an invalid rep, an empty range — assert readable output, no exceptions.
2. Manually cross-check 3 numbers against direct MySQL queries (e.g. total expense of a rep, top spot-sale rep) before shipping.
3. End-to-end: hit `POST /chat/send` with your sample questions from the requirement list and compare the answers with tool output.
4. `php artisan route:list` / basic smoke test to confirm nothing else breaks.

## 7. Out of Scope (future iterations)
- Conversation memory injection (`Conversational::messages()` currently empty — can wire `conversation_id` later so follow-ups like "and his expenses?" work).
- Write-capable tools (create fund request, approve sales, etc.) — not allowed for now for safety.
- Chart/attachment output, printable report links.
