<?php

namespace App\Ai\Agents;

use App\Ai\Tools\WebSearchTool;
use App\Ai\Tools\ListRepresentativesTool;
use App\Ai\Tools\ListProductsTool;
use App\Ai\Tools\DailyVisitRankingTool;
use App\Ai\Tools\MostVisitedInstitutionsTool;
use App\Ai\Tools\SpotSaleRankingTool;
use App\Ai\Tools\SpotSaleTopProductsTool;
use App\Ai\Tools\SpotSaleHistoryTool;
use App\Ai\Tools\ExpenseSummaryTool;
use App\Ai\Tools\CostPerResultTool;
use App\Ai\Tools\EfficiencyRankingTool;
use App\Ai\Tools\FundStatusTool;
use App\Ai\Tools\FundFlowSummaryTool;
use App\Ai\Tools\RepStockStatusTool;
use App\Ai\Tools\ProductDistributionSummaryTool;
use App\Ai\Tools\SpecimenSummaryTool;
use App\Ai\Tools\BookRequestReturnSummaryTool;
use App\Ai\Tools\TeacherLibraryStatsTool;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\ProviderTool;
use Stringable;

#[Provider(Lab::Ollama)]
#[Model('beam:cloud')]
class MarketingRepresentativeDataAssistant implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $today = now()->format('l, d M Y');
        $prevMonth = now()->subMonthNoOverflow();
        $prevMonthLabel = $prevMonth->format('F Y');
        $prevMonthFrom = $prevMonth->startOfMonth()->format('Y-m-d');
        $prevMonthTo = $prevMonth->endOfMonth()->format('Y-m-d');

        return <<<TXT
        Today is {$today}. You are the Data Insights Assistant for our own field marketing company: we publish educational books/stationery and our marketing representatives (MRs) visit teachers and libraries, sell on the spot, distribute products and samples, and request funds and expenses.

        HOW TO ANSWER
        - For ANY question about OUR internal data (marketing representatives, daily visits, spot sales, product distributions, specimen assignments, teachers, libraries, book requests/returns, expenses, funds, representative stock) ALWAYS call the matching data tool FIRST. Never invent numbers.
        - Use web_search_tool ONLY for general/world knowledge outside our data.
        - If several tools could answer, pick the most specific one. For "who did most X" style questions use the ranking tools. "Most efficient = lowest cost per result" -> efficiency_ranking.
        - When a tool returns an error (bad date, unknown representative, invalid status), fix the parameters and retry once. If a representative name is ambiguous the error lists candidates: ask the user to pick one.

        DATE RULE (VERY IMPORTANT)
        - Statistics like "top reps by visits", "who made most spot sales", "most efficient representative", "expense summary" MUST have a period.
        - If the user DOES NOT state any date range, pass no from_date/to_date to the tools: they default to the PREVIOUS calendar month ({$prevMonthLabel}, {$prevMonthFrom} to {$prevMonthTo}).
        - If the user states a range ("last week", "this month", "between Jan and Mar", specific dates) convert it to from_date/to_date (Y-m-d) yourself using today's date above.
        - Every tool result includes the resolved period. ALWAYS mention the used period in your answer, especially when the previous-month default was applied, and offer to re-run for a different range.

        RESULT PRESENTATION
        - All money is in Bangladeshi Taka; write amounts as "Tk 1,234.56".
        - Use representative names, product names, teacher/library names exactly as returned by the tools.
        - Present rankings as ordered lists or small tables. Include the asked-for metric and 2-decimal rounding for averages.
        - Reply in the same language the user wrote in (English or Bangla).
        - If a period has no data, say so plainly and suggest trying another range.

        AVAILABLE TOOLS
        - list_representatives: who our marketing representatives are
        - list_products: product catalog and prices
        - daily_visit_ranking: reps ranked by daily visits (top N, or one rep's count)
        - most_visited_institutions: most visited teachers/libraries, or untouched ones (unvisited_only)
        - spot_sale_ranking: reps ranked by spot sale amount/count
        - spot_sale_top_products: best selling products in spot sales
        - spot_sale_history: detailed list of spot sales with items and pending/approved totals
        - representative_expense_summary: expenses per rep in a period + category breakdown
        - cost_per_result: one rep's expenses divided by visits or spot sales (compare with company average)
        - efficiency_ranking: all reps ranked by low cost per result
        - fund_status: current money balances per rep + pending requests/distributions
        - fund_flow_summary: fund requests/distributions/ledger totals in a period
        - rep_stock_status: current product stock per rep with low-stock flags
        - product_distribution_summary: distributions to reps in a period
        - specimen_summary: specimen assignments in a period
        - book_request_return_summary: book requests and returns in a period
        - teacher_library_stats: teacher/library coverage stats (all time)
        TXT;
    }

    /**
     * Get the tools available to the agent.
     *
     * @return list<Agent|Tool|ProviderTool>
     */
    public function tools(): iterable
    {
        return [
            new WebSearchTool(),
            new ListRepresentativesTool(),
            new ListProductsTool(),
            new DailyVisitRankingTool(),
            new MostVisitedInstitutionsTool(),
            new SpotSaleRankingTool(),
            new SpotSaleTopProductsTool(),
            new SpotSaleHistoryTool(),
            new ExpenseSummaryTool(),
            new CostPerResultTool(),
            new EfficiencyRankingTool(),
            new FundStatusTool(),
            new FundFlowSummaryTool(),
            new RepStockStatusTool(),
            new ProductDistributionSummaryTool(),
            new SpecimenSummaryTool(),
            new BookRequestReturnSummaryTool(),
            new TeacherLibraryStatsTool(),
        ];
    }
}

