<?php

namespace App\Repositories\Eloquent;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Program;
use App\Models\PurchaseOrder;
use App\Models\TripReport;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use LogicException;

class DashboardRepository implements DashboardRepositoryInterface
{
    /**
     * @return array{
     *     top_customers: Collection<int, Customer>,
     *     total_programs: int,
     *     total_purchase_order_amount: int,
     *     total_billed: int,
     *     total_paid: int,
     *     balance_to_collect: int
     * }
     */
    public function customerOverview(): array
    {
        $topCustomers = Customer::query()
            ->has('purchaseOrders')
            ->withCount('purchaseOrders')
            ->withSum('purchaseOrders', 'amount')
            ->orderByDesc('purchase_orders_sum_amount')
            ->orderBy('name')
            ->orderBy('id')
            ->limit(5)
            ->get();

        $financialTotals = TripReport::query()
            ->join('invoices', function (JoinClause $join): void {
                $join->on('trip_reports.invoice_id', '=', 'invoices.id')
                    ->on('trip_reports.purchase_order_id', '=', 'invoices.purchase_order_id');
            })
            ->selectRaw(
                'COALESCE(SUM(trip_reports.amount), 0) as total_billed,
                COALESCE(SUM(CASE WHEN invoices.status = ? THEN trip_reports.amount ELSE 0 END), 0) as total_paid,
                COALESCE(SUM(CASE WHEN invoices.status = ? THEN trip_reports.amount ELSE 0 END), 0) as balance_to_collect',
                [InvoiceStatus::Paid->value, InvoiceStatus::Unpaid->value]
            )
            ->first();

        return [
            'top_customers' => $topCustomers,
            'total_programs' => Program::query()->count(),
            'total_purchase_order_amount' => (int) PurchaseOrder::query()->sum('amount'),
            'total_billed' => (int) ($financialTotals?->total_billed ?? 0),
            'total_paid' => (int) ($financialTotals?->total_paid ?? 0),
            'balance_to_collect' => (int) ($financialTotals?->balance_to_collect ?? 0),
        ];
    }

    /**
     * @return array{
     *     top_programs: Collection<int, Program>,
     *     top_billed_programs: Collection<int, Program>,
     *     top_paid_programs: Collection<int, Program>
     * }
     */
    public function programRankings(): array
    {
        $topPrograms = Program::query()
            ->join('purchase_orders', 'purchase_orders.program_id', '=', 'programs.id')
            ->select(['programs.id', 'programs.name', 'programs.customer_id'])
            ->selectRaw('COALESCE(SUM(purchase_orders.amount), 0) as purchase_order_total')
            ->groupBy(['programs.id', 'programs.name', 'programs.customer_id'])
            ->orderByDesc('purchase_order_total')
            ->orderBy('programs.name')
            ->orderBy('programs.id')
            ->limit(10)
            ->get();

        return [
            'top_programs' => $topPrograms,
            'top_billed_programs' => $this->topProgramsByInvoiceAmount(),
            'top_paid_programs' => $this->topProgramsByInvoiceAmount(InvoiceStatus::Paid),
        ];
    }

    /**
     * @return Collection<int, TripReport>
     */
    public function recentTripReports(): Collection
    {
        return TripReport::query()
            ->select([
                'id',
                'purchase_order_id',
                'trip_report_no',
                'driver',
                'report_date',
                'trip_start',
                'trip_end',
            ])
            ->orderByDesc('report_date')
            ->orderBy('driver')
            ->orderBy('id')
            ->limit(10)
            ->get();
    }

    /**
     * @return list<array{
     *     month: string,
     *     total_billed: int,
     *     total_collectible: int,
     *     total_paid: int
     * }>
     */
    public function monthReport(): array
    {
        $start = now()->startOfMonth()->subMonths(11);
        $end = now()->endOfMonth();
        $billedMonthExpression = $this->monthExpression('invoices.billed_at');
        $paidMonthExpression = $this->monthExpression('invoices.paid_at');

        $billedByMonth = TripReport::query()
            ->join('invoices', function (JoinClause $join): void {
                $join->on('trip_reports.invoice_id', '=', 'invoices.id')
                    ->on('trip_reports.purchase_order_id', '=', 'invoices.purchase_order_id');
            })
            ->whereBetween('invoices.billed_at', [$start, $end])
            ->selectRaw("{$billedMonthExpression} as month")
            ->selectRaw(
                'COALESCE(SUM(trip_reports.amount), 0) as total_billed,
                COALESCE(SUM(CASE WHEN invoices.status = ? THEN trip_reports.amount ELSE 0 END), 0) as total_collectible',
                [InvoiceStatus::Unpaid->value]
            )
            ->groupByRaw($billedMonthExpression)
            ->get()
            ->keyBy('month');

        $paidByMonth = TripReport::query()
            ->join('invoices', function (JoinClause $join): void {
                $join->on('trip_reports.invoice_id', '=', 'invoices.id')
                    ->on('trip_reports.purchase_order_id', '=', 'invoices.purchase_order_id');
            })
            ->where('invoices.status', InvoiceStatus::Paid)
            ->whereBetween('invoices.paid_at', [$start, $end])
            ->selectRaw("{$paidMonthExpression} as month")
            ->selectRaw('COALESCE(SUM(trip_reports.amount), 0) as total_paid')
            ->groupByRaw($paidMonthExpression)
            ->get()
            ->keyBy('month');

        return collect(range(0, 11))
            ->map(function (int $offset) use ($start, $billedByMonth, $paidByMonth): array {
                $month = $start->copy()->addMonths($offset)->format('Y-m');

                return [
                    'month' => $month,
                    'total_billed' => (int) ($billedByMonth->get($month)?->total_billed ?? 0),
                    'total_collectible' => (int) ($billedByMonth->get($month)?->total_collectible ?? 0),
                    'total_paid' => (int) ($paidByMonth->get($month)?->total_paid ?? 0),
                ];
            })
            ->all();
    }

    /**
     * @return Collection<int, Program>
     */
    private function topProgramsByInvoiceAmount(?InvoiceStatus $status = null): Collection
    {
        return Program::query()
            ->join('purchase_orders', 'purchase_orders.program_id', '=', 'programs.id')
            ->join('trip_reports', 'trip_reports.purchase_order_id', '=', 'purchase_orders.id')
            ->join('invoices', function (JoinClause $join): void {
                $join->on('trip_reports.invoice_id', '=', 'invoices.id')
                    ->on('invoices.purchase_order_id', '=', 'purchase_orders.id');
            })
            ->when($status !== null, fn ($query) => $query->where('invoices.status', $status))
            ->select(['programs.id', 'programs.name', 'programs.customer_id'])
            ->selectRaw('COALESCE(SUM(trip_reports.amount), 0) as invoice_amount_total')
            ->groupBy(['programs.id', 'programs.name', 'programs.customer_id'])
            ->orderByDesc('invoice_amount_total')
            ->orderBy('programs.name')
            ->orderBy('programs.id')
            ->limit(5)
            ->get();
    }

    private function monthExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'mysql' => "DATE_FORMAT({$column}, '%Y-%m')",
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM')",
            'sqlsrv' => "FORMAT({$column}, 'yyyy-MM')",
            default => throw new LogicException('Unsupported database driver for dashboard month reports.'),
        };
    }
}
