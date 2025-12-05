<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\FinancialLedger;
use App\Models\LoyverseSale;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;

class ReportsController extends Controller
{
    private const DEFAULT_RANGE = 'today';

    public function index(Request $request)
    {
        abort_if($request->user()?->cannot('view reports'), 403);

        [$range, $startDate, $endDate, $rangeLabel] = $this->resolveRange($request);
        $rangeOptions = $this->rangeOptions();

        [$salesSummary, $salesByDate] = $this->summarizeSales($startDate, $endDate);
        [$expenseSummary, $expensesByDate, $topExpenseCategories] = $this->summarizeExpenses($startDate, $endDate);
        [$accountsReceivable, $vendorCredits] = $this->summarizeLedgerPositions($startDate, $endDate);
        $topSellingItems = $this->topSellingItems($startDate, $endDate);

        $daysInRange = max(1, $startDate->copy()->startOfDay()->diffInDays($endDate->copy()->startOfDay()) + 1);
        $netProfit = $salesSummary['total'] - $expenseSummary['total'];
        $profitMargin = $salesSummary['total'] > 0
            ? ($netProfit / $salesSummary['total']) * 100
            : 0.0;

        $netPosition = $salesSummary['total'] + $accountsReceivable - $expenseSummary['total'] - $vendorCredits;

        $dailyTrendCollection = $this->buildDailyTrend($startDate, $endDate, $salesByDate, $expensesByDate);
        $dailyTrend = $this->paginateDailyTrend($dailyTrendCollection, $request);

        $summary = [
            'total_sales' => $salesSummary['total'],
            'sales_count' => $salesSummary['count'],
            'average_daily_sales' => $salesSummary['total'] / $daysInRange,
            'total_expenses' => $expenseSummary['total'],
            'expense_count' => $expenseSummary['count'],
            'average_daily_expenses' => $expenseSummary['total'] / $daysInRange,
            'net_profit' => $netProfit,
            'profit_margin' => $profitMargin,
            'days' => $daysInRange,
            'sales_tax' => $salesSummary['tax'],
            'accounts_receivable' => $accountsReceivable,
            'vendor_credits' => $vendorCredits,
            'net_position' => $netPosition,
        ];

        $exportParams = array_filter([
            'range' => $range,
            'start_date' => $range === 'custom' ? $startDate->toDateString() : null,
            'end_date' => $range === 'custom' ? $endDate->toDateString() : null,
        ], fn ($value) => ! is_null($value));

        return view('reports.index', [
            'rangeOptions' => $rangeOptions,
            'selectedRange' => $range,
            'rangeLabel' => $rangeLabel,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'summary' => $summary,
            'dailyTrend' => $dailyTrend,
            'topExpenseCategories' => $topExpenseCategories,
            'topSellingItems' => $topSellingItems,
            'exportParams' => $exportParams,
        ]);
    }

    public function export(Request $request)
    {
        abort_if($request->user()?->cannot('view reports'), 403);

        $this->resolveRange($request);

        return response()->json([
            'message' => 'Report export is not available yet.',
        ], 501);
    }

    private function summarizeSales(Carbon $startDate, Carbon $endDate): array
    {
        $dateColumn = $this->saleDateColumn();
        $amountColumn = $this->saleAmountColumn();
        $taxColumn = $this->saleTaxColumn();

        $query = LoyverseSale::query()
            ->whereBetween($dateColumn, [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);

        $total = (clone $query)->sum($amountColumn);
        $count = (clone $query)->count();
        $taxTotal = $taxColumn ? (clone $query)->sum($taxColumn) : 0.0;

        $totalsByDate = (clone $query)
            ->selectRaw($dateColumn . ' as day, SUM(' . $amountColumn . ') as total')
            ->groupBy($dateColumn)
            ->orderBy($dateColumn)
            ->pluck('total', 'day')
            ->map(fn ($value) => (float) $value);

        return [[
            'total' => (float) $total,
            'count' => (int) $count,
            'tax' => (float) $taxTotal,
        ], $totalsByDate];
    }

    private function summarizeExpenses(Carbon $startDate, Carbon $endDate): array
    {
        $dateColumn = $this->expenseDateColumn();
        $categoryColumn = $this->expenseCategoryColumn();

        $query = Expense::query()
            ->whereBetween($dateColumn, [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);

        $total = (clone $query)->sum('amount');
        $count = (clone $query)->count();

        $totalsByDate = (clone $query)
            ->selectRaw($dateColumn . ' as day, SUM(amount) as total')
            ->groupBy($dateColumn)
            ->orderBy($dateColumn)
            ->pluck('total', 'day')
            ->map(fn ($value) => (float) $value);

        $topCategories = collect();

        if ($categoryColumn) {
            $topCategories = (clone $query)
                ->selectRaw('COALESCE(' . $categoryColumn . ", 'Uncategorized') as category, SUM(amount) as total")
                ->groupBy('category')
                ->orderByDesc('total')
                ->limit(5)
                ->get()
                ->map(fn ($row) => [
                    'category' => $row->category ?? 'Uncategorized',
                    'total' => (float) $row->total,
                ]);
        }

        return [[
            'total' => (float) $total,
            'count' => (int) $count,
        ], $totalsByDate, $topCategories];
    }

    private function summarizeLedgerPositions(Carbon $startDate, Carbon $endDate): array
    {
        $range = [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()];

        $receivable = FinancialLedger::query()
            ->where('ledger_type', FinancialLedger::TYPE_RECEIVABLE)
            ->whereBetween('opened_at', $range)
            ->sum('outstanding_amount');

        $vendorCredits = FinancialLedger::query()
            ->where('ledger_type', FinancialLedger::TYPE_LIABILITY)
            ->whereBetween('opened_at', $range)
            ->sum('outstanding_amount');

        return [
            (float) $receivable,
            (float) $vendorCredits,
        ];
    }

    private function topSellingItems(Carbon $startDate, Carbon $endDate): Collection
    {
        $dateColumn = $this->saleDateColumn();

        $sales = LoyverseSale::query()
            ->whereBetween($dateColumn, [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->select('items')
            ->cursor();

        $aggregated = [];

        foreach ($sales as $sale) {
            $lineItems = $sale->items;

            if (! is_array($lineItems)) {
                continue;
            }

            foreach ($lineItems as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $name = $this->resolveSaleItemName($item);

                if (! $name) {
                    continue;
                }

                $quantity = $this->resolveSaleItemQuantity($item);
                $revenue = $this->resolveSaleItemRevenue($item, $quantity);

                if (! isset($aggregated[$name])) {
                    $aggregated[$name] = [
                        'name' => $name,
                        'quantity' => 0.0,
                        'revenue' => 0.0,
                    ];
                }

                $aggregated[$name]['quantity'] += $quantity;
                $aggregated[$name]['revenue'] += $revenue;
            }
        }

        return collect($aggregated)
            ->map(function (array $data) {
                $data['quantity'] = round($data['quantity'], 2);
                $data['revenue'] = round($data['revenue'], 2);

                return $data;
            })
            ->sortByDesc('revenue')
            ->values()
            ->take(5);
    }

    private function resolveSaleItemName(array $item): ?string
    {
        $candidates = [
            $item['item_name'] ?? null,
            $item['name'] ?? null,
            $item['item'] ?? null,
            $item['product_name'] ?? null,
        ];

        foreach ($candidates as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function resolveSaleItemQuantity(array $item): float
    {
        $quantity = $item['quantity'] ?? $item['qty'] ?? $item['item_quantity'] ?? $item['quantity_sold'] ?? 0;

        return (float) $quantity;
    }

    private function resolveSaleItemRevenue(array $item, float $quantity): float
    {
        $lineTotal = $item['line_total'] ?? $item['total'] ?? $item['subtotal'] ?? $item['item_total'] ?? null;

        if (is_numeric($lineTotal)) {
            return (float) $lineTotal;
        }

        $unitPrice = $item['price'] ?? $item['unit_price'] ?? $item['price_per_unit'] ?? $item['item_price'] ?? null;

        if (is_numeric($unitPrice) && $quantity !== 0.0) {
            return (float) $unitPrice * $quantity;
        }

        return 0.0;
    }

    private function buildDailyTrend(Carbon $startDate, Carbon $endDate, Collection $salesByDate, Collection $expensesByDate): Collection
    {
        $period = CarbonPeriod::create($startDate->copy()->startOfDay(), $endDate->copy()->startOfDay());

        return collect($period)->map(function (Carbon $date) use ($salesByDate, $expensesByDate) {
            $key = $date->toDateString();
            $sales = (float) ($salesByDate[$key] ?? 0);
            $expenses = (float) ($expensesByDate[$key] ?? 0);

            return [
                'date' => $date,
                'sales' => $sales,
                'expenses' => $expenses,
                'profit' => $sales - $expenses,
            ];
        });
    }

    private function paginateDailyTrend(Collection $trend, Request $request): LengthAwarePaginator
    {
        $perPage = 30;
        $pageName = 'trend_page';
        $page = Paginator::resolveCurrentPage($pageName);

        $paginator = new LengthAwarePaginator(
            $trend->forPage($page, $perPage)->values(),
            $trend->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => $pageName,
            ]
        );

        return $paginator->appends($request->except($pageName));
    }

    private function resolveRange(Request $request): array
    {
        $range = $request->input('range', self::DEFAULT_RANGE);
        $definitions = $this->rangeDefinitions();

        if (! array_key_exists($range, $definitions)) {
            $range = self::DEFAULT_RANGE;
        }

        if ($range === 'custom') {
            $validated = $request->validate([
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            ]);

            $startDate = Carbon::parse($validated['start_date'])->startOfDay();
            $endDate = Carbon::parse($validated['end_date'])->endOfDay();
            $label = $this->formatCustomLabel($startDate, $endDate);
        } else {
            [$startDate, $endDate] = $definitions[$range]();
            $label = $this->labelFor($range);
        }

        return [$range, $startDate, $endDate, $label];
    }

    private function rangeDefinitions(): array
    {
        return [
            'today' => function (): array {
                $today = Carbon::today();

                return [$today->copy()->startOfDay(), $today->copy()->endOfDay()];
            },
            'yesterday' => function (): array {
                $yesterday = Carbon::yesterday();

                return [$yesterday->copy()->startOfDay(), $yesterday->copy()->endOfDay()];
            },
            'last_7_days' => function (): array {
                $end = Carbon::today();
                $start = $end->copy()->subDays(6);

                return [$start->startOfDay(), $end->endOfDay()];
            },
            'last_30_days' => function (): array {
                $end = Carbon::today();
                $start = $end->copy()->subDays(29);

                return [$start->startOfDay(), $end->endOfDay()];
            },
            'custom' => fn (): array => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
        ];
    }

    private function rangeOptions(): array
    {
        return [
            'today' => ['label' => 'Today'],
            'yesterday' => ['label' => 'Yesterday'],
            'last_7_days' => ['label' => 'Last 7 days'],
            'last_30_days' => ['label' => 'Last 30 days'],
            'custom' => ['label' => 'Custom range'],
        ];
    }

    private function labelFor(string $range): string
    {
        return match ($range) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'last_7_days' => 'Last 7 days',
            'last_30_days' => 'Last 30 days',
            default => 'Custom range',
        };
    }

    private function formatCustomLabel(Carbon $startDate, Carbon $endDate): string
    {
        return sprintf(
            'Custom (%s – %s)',
            $startDate->toFormattedDateString(),
            $endDate->toFormattedDateString()
        );
    }

    private function saleDateColumn(): string
    {
        return Schema::hasColumn('loyverse_sales', 'sale_date') ? 'sale_date' : 'date';
    }

    private function saleAmountColumn(): string
    {
        return Schema::hasColumn('loyverse_sales', 'total_amount') ? 'total_amount' : 'total_sales';
    }

    private function saleTaxColumn(): ?string
    {
        if (Schema::hasColumn('loyverse_sales', 'tax_amount')) {
            return 'tax_amount';
        }

        return Schema::hasColumn('loyverse_sales', 'tax') ? 'tax' : null;
    }

    private function expenseDateColumn(): string
    {
        return Schema::hasColumn('expenses', 'expense_date') ? 'expense_date' : 'date';
    }

    private function expenseCategoryColumn(): ?string
    {
        if (Schema::hasColumn('expenses', 'category')) {
            return 'category';
        }

        return Schema::hasColumn('expenses', 'ledger_code') ? 'ledger_code' : null;
    }
}
