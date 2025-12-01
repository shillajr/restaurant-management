<?php

namespace App\Http\Controllers;

use App\Models\CreditSale;
use App\Models\FinancialLedger;
use App\Models\FinancialLedgerPayment;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancialLedgerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAccess($request->user());

        $filters = $request->only(['status', 'type', 'search']);

        $ledgersQuery = FinancialLedger::with([
                'vendor',
                'creditSale',
                'payments' => function ($query) {
                    $query->orderByDesc('paid_at')->orderByDesc('created_at');
                },
                'payments.recorder',
            ])
            ->when($filters['status'] ?? null, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($filters['type'] ?? null, function ($query, $type) {
                return $query->where('ledger_type', $type);
            })
            ->when($filters['search'] ?? null, function ($query, $term) {
                $likeTerm = '%' . $term . '%';

                return $query->where(function ($searchQuery) use ($likeTerm) {
                    $searchQuery
                        ->where('ledger_code', 'like', $likeTerm)
                        ->orWhere('vendor_name', 'like', $likeTerm)
                        ->orWhere('contact_first_name', 'like', $likeTerm)
                        ->orWhere('contact_last_name', 'like', $likeTerm);
                });
            })
            ->orderByDesc('opened_at');

        $ledgers = $ledgersQuery->paginate(15)->withQueryString();

        $stats = [
            'total_outstanding' => (float) FinancialLedger::outstanding()->sum('outstanding_amount'),
            'total_principal' => (float) FinancialLedger::sum('principal_amount'),
            'total_paid' => (float) FinancialLedger::sum('paid_amount'),
            'liability_outstanding' => (float) FinancialLedger::where('ledger_type', FinancialLedger::TYPE_LIABILITY)->sum('outstanding_amount'),
            'receivable_outstanding' => (float) FinancialLedger::where('ledger_type', FinancialLedger::TYPE_RECEIVABLE)->sum('outstanding_amount'),
        ];

        $upcomingReminders = FinancialLedger::whereNotNull('next_reminder_due_at')
            ->orderBy('next_reminder_due_at')
            ->limit(5)
            ->get(['id', 'ledger_code', 'next_reminder_due_at', 'vendor_name', 'contact_first_name', 'contact_last_name']);

        return view('finance.ledgers.index', [
            'ledgers' => $ledgers,
            'stats' => $stats,
            'filters' => $filters,
            'upcomingReminders' => $upcomingReminders,
            'paymentMethods' => $this->paymentMethods(),
        ]);
    }

    public function createVendor(Request $request): View
    {
        $this->authorizeAccess($request->user());

        return view('finance.ledgers.create-vendor', [
            'purchaseOrdersPayload' => $this->buildPurchaseOrdersPayload(),
            'currencyCode' => config('app.currency', 'TZS'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request->user());

        $entryType = $request->input('entry_type');

        if (! in_array($entryType, ['customer_receivable', 'vendor_debt'], true)) {
            return back()
                ->withErrors(['entry_type' => 'Invalid ledger entry type selected.'])
                ->withInput();
        }

        if ($entryType === 'customer_receivable') {
            $validated = $request->validate([
                'customer_first_name' => ['required', 'string', 'max:255'],
                'customer_last_name' => ['required', 'string', 'max:255'],
                'customer_phone' => ['required', 'string', 'max:50'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ]);

            DB::transaction(function () use ($validated) {
                $amount = (float) $validated['amount'];
                $openedAt = Carbon::now();
                $nextReminder = $openedAt->copy()->addDays(7);

                $sale = CreditSale::create([
                    'sale_date' => $openedAt->toDateString(),
                    'currency' => config('app.currency', 'TZS'),
                    'total_amount' => $amount,
                    'customer_first_name' => $validated['customer_first_name'],
                    'customer_last_name' => $validated['customer_last_name'],
                    'customer_phone' => $validated['customer_phone'],
                    'customer_email' => null,
                    'notes' => $validated['notes'] ?? null,
                    'recorded_by' => Auth::id(),
                ]);

                $ledger = new FinancialLedger([
                    'ledger_type' => FinancialLedger::TYPE_RECEIVABLE,
                    'status' => FinancialLedger::STATUS_OPEN,
                    'credit_sale_id' => $sale->id,
                    'vendor_name' => $validated['customer_first_name'] . ' ' . $validated['customer_last_name'],
                    'contact_first_name' => $validated['customer_first_name'],
                    'contact_last_name' => $validated['customer_last_name'],
                    'contact_phone' => $validated['customer_phone'],
                    'principal_amount' => $amount,
                    'outstanding_amount' => $amount,
                    'paid_amount' => 0,
                    'opened_at' => $openedAt,
                    'next_reminder_due_at' => $nextReminder,
                    'notes' => $validated['notes'] ?? null,
                ]);

                $ledger->source()->associate($sale);
                $ledger->creditSale()->associate($sale);
                $ledger->save();
            });

            return redirect()
                ->route('financial-ledgers.index')
                ->with('success', 'Customer receivable recorded successfully.');
        }

        $validated = $request->validate([
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'po_item_keys' => ['required', 'array', 'min:1'],
            'po_item_keys.*' => ['string'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $purchaseOrder = PurchaseOrder::query()->findOrFail($validated['purchase_order_id']);
        $items = collect($purchaseOrder->items ?? [])->values();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'The selected purchase order has no items available for credit.',
            ]);
        }

        $selectedItems = collect($validated['po_item_keys'])
            ->map(function ($key) use ($items) {
                if (! is_numeric($key)) {
                    throw ValidationException::withMessages([
                        'po_item_keys' => 'Invalid item selection provided.',
                    ]);
                }

                $index = (int) $key;
                $item = $items->get($index);

                if (! $item) {
                    throw ValidationException::withMessages([
                        'po_item_keys' => 'One or more selected items are no longer available on this purchase order.',
                    ]);
                }

                $quantity = (float) ($item['quantity'] ?? 0);
                $unitPrice = (float) ($item['price'] ?? ($item['unit_price'] ?? 0));
                $lineTotal = (float) ($item['line_total'] ?? ($quantity * $unitPrice));

                return [
                    'index' => $index,
                    'label' => $item['item'] ?? ($item['item_id'] ?? 'Item ' . ($index + 1)),
                    'vendor' => $item['vendor'] ?? null,
                    'quantity' => $quantity,
                    'unit' => $item['unit'] ?? ($item['uom'] ?? null),
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            })
            ->values();

        if ($selectedItems->isEmpty()) {
            throw ValidationException::withMessages([
                'po_item_keys' => 'Select at least one line item to continue.',
            ]);
        }

        $principalAmount = $selectedItems->sum('line_total');

        if ($principalAmount <= 0) {
            throw ValidationException::withMessages([
                'po_item_keys' => 'The selected items do not have a payable total. Please confirm their pricing.',
            ]);
        }

        $uniqueVendors = $selectedItems->pluck('vendor')->filter()->unique();

        if ($uniqueVendors->count() > 1) {
            throw ValidationException::withMessages([
                'po_item_keys' => 'Please create a separate entry for items from different vendors.',
            ]);
        }

        $vendor = null;
        $vendorName = $uniqueVendors->first();

        if ($purchaseOrder->supplier_id) {
            $vendor = Vendor::find($purchaseOrder->supplier_id);
        }

        if (! $vendor && $vendorName) {
            $vendor = Vendor::query()
                ->whereRaw('LOWER(name) = ?', [strtolower($vendorName)])
                ->first();
        }

        $displayVendorName = $vendor?->name ?? $vendorName ?? 'Vendor for ' . $purchaseOrder->po_number;

        $itemSummary = $selectedItems->map(function (array $item) {
            $quantity = $item['quantity'];
            $quantityLabel = $quantity == (int) $quantity ? (string) (int) $quantity : number_format($quantity, 2);
            $unitSuffix = $item['unit'] ? ' ' . $item['unit'] : '';
            $lineLabel = number_format($item['line_total'], 2);

            return sprintf('%s — Qty %s%s (Total %s)', $item['label'], $quantityLabel, $unitSuffix, $lineLabel);
        })->implode('; ');

        $notesPayload = collect([$validated['notes'] ?? null, $itemSummary ? 'Credited items: ' . $itemSummary : null])
            ->filter()
            ->implode("\n\n");

        DB::transaction(function () use ($principalAmount, $purchaseOrder, $vendor, $displayVendorName, $notesPayload) {
            $openedAt = Carbon::now();
            $nextReminder = $openedAt->copy()->addDays(7);

            $ledger = new FinancialLedger([
                'ledger_type' => FinancialLedger::TYPE_LIABILITY,
                'status' => FinancialLedger::STATUS_OPEN,
                'purchase_order_id' => $purchaseOrder->id,
                'vendor_id' => $vendor?->id,
                'vendor_name' => $displayVendorName,
                'vendor_phone' => $vendor?->phone,
                'contact_phone' => $vendor?->phone,
                'contact_email' => $vendor?->email,
                'principal_amount' => $principalAmount,
                'outstanding_amount' => $principalAmount,
                'paid_amount' => 0,
                'opened_at' => $openedAt,
                'next_reminder_due_at' => $nextReminder,
                'notes' => $notesPayload ?: null,
            ]);

            if ($vendor) {
                $ledger->vendor()->associate($vendor);
            }

            $ledger->source()->associate($purchaseOrder);
            $ledger->save();
        });

        return redirect()
            ->route('financial-ledgers.index')
            ->with('success', 'Vendor debt recorded successfully.');
    }

    public function storePayment(Request $request, FinancialLedger $financialLedger)
    {
        $this->authorizePaymentAccess($request->user());

        if ((float) $financialLedger->outstanding_amount <= 0) {
            $message = 'This ledger is already settled.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 409);
            }

            return back()->withErrors(['amount' => $message]);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['nullable', 'date'],
            'payment_method' => ['required', 'in:' . implode(',', array_column($this->paymentMethods(), 'value'))],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $amount = (float) $validated['amount'];

        if ($amount - (float) $financialLedger->outstanding_amount > 0.01) {
            $message = 'The payment exceeds the outstanding amount.';

            throw ValidationException::withMessages([
                'amount' => $message,
            ]);
        }

        $paidAt = isset($validated['paid_at']) ? Carbon::parse($validated['paid_at']) : null;

        $payment = $financialLedger->registerPayment($amount, $paidAt, [
            'payment_method' => $validated['payment_method'],
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'recorded_by' => Auth::id(),
        ]);

        $financialLedger->refresh()->loadMissing(['payments' => function ($query) {
            $query->orderByDesc('paid_at')->orderByDesc('created_at');
        }, 'payments.recorder']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Payment recorded successfully.',
                'ledger' => $this->formatLedgerForResponse($financialLedger),
                'payment' => $this->formatPaymentForResponse($payment),
            ]);
        }

        return back()->with('success', 'Payment recorded successfully.');
    }

    protected function buildPurchaseOrdersPayload(): array
    {
        return PurchaseOrder::query()
            ->select(['id', 'po_number', 'status', 'workflow_status', 'items', 'grand_total', 'supplier_id', 'created_at'])
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function (PurchaseOrder $purchaseOrder) {
                $items = collect($purchaseOrder->items ?? [])->values();

                if ($items->isEmpty()) {
                    return null;
                }

                $itemPayload = $items->map(function ($item, int $index) {
                    $quantity = (float) ($item['quantity'] ?? 0);
                    $unitPrice = (float) ($item['price'] ?? ($item['unit_price'] ?? 0));
                    $lineTotal = (float) ($item['line_total'] ?? ($quantity * $unitPrice));

                    return [
                        'key' => (string) $index,
                        'label' => $item['item'] ?? ($item['item_id'] ?? 'Item ' . ($index + 1)),
                        'vendor' => $item['vendor'] ?? null,
                        'quantity' => $quantity,
                        'unit' => $item['unit'] ?? ($item['uom'] ?? null),
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ];
                })->filter(function (array $item) {
                    return $item['line_total'] > 0 || $item['quantity'] > 0;
                })->values();

                if ($itemPayload->isEmpty()) {
                    return null;
                }

                return [
                    'id' => $purchaseOrder->id,
                    'po_number' => $purchaseOrder->po_number,
                    'status' => $purchaseOrder->status,
                    'workflow_status' => $purchaseOrder->workflow_status,
                    'grand_total' => (float) $purchaseOrder->grand_total,
                    'created_at' => optional($purchaseOrder->created_at)->toDateTimeString(),
                    'supplier_id' => $purchaseOrder->supplier_id,
                    'vendor_names' => $itemPayload->pluck('vendor')->filter()->unique()->values()->all(),
                    'items' => $itemPayload->toArray(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function authorizeAccess($user): void
    {
        if (! $user) {
            abort(403, 'You are not authorised to manage financial ledgers.');
        }

        $allowed = $user->hasAnyRole(['admin', 'manager', 'purchasing'])
            || $user->can('view financial ledgers')
            || $user->can('approve purchase orders')
            || $user->can('create purchase orders');

        if ($allowed) {
            return;
        }

        abort(403, 'You are not authorised to manage financial ledgers.');
    }

    private function authorizePaymentAccess($user): void
    {
        if (! $user || ! $user->hasAnyRole(['admin', 'manager', 'finance'])) {
            abort(403, 'You are not authorised to record ledger payments.');
        }
    }

    private function paymentMethods(): array
    {
        return [
            ['value' => 'cash', 'label' => 'Cash'],
            ['value' => 'card', 'label' => 'Card'],
            ['value' => 'mobile_money', 'label' => 'Mobile Money'],
            ['value' => 'bank_transfer', 'label' => 'Bank Transfer'],
            ['value' => 'check', 'label' => 'Check'],
        ];
    }

    private function formatLedgerForResponse(FinancialLedger $ledger): array
    {
        $status = $this->statusBadgeForLedger($ledger);

        return [
            'id' => $ledger->id,
            'ledger_code' => $ledger->ledger_code,
            'ledger_type' => $ledger->ledger_type,
            'ledger_type_label' => $ledger->ledger_type === FinancialLedger::TYPE_LIABILITY ? 'Vendor liability' : 'Customer receivable',
            'principal' => (float) $ledger->principal_amount,
            'paid' => (float) $ledger->paid_amount,
            'outstanding' => (float) $ledger->outstanding_amount,
            'status' => $status,
            'formatted' => [
                'principal' => currency_format($ledger->principal_amount),
                'paid' => currency_format($ledger->paid_amount),
                'outstanding' => currency_format($ledger->outstanding_amount),
            ],
            'payments' => $ledger->payments
                ->sortByDesc('paid_at')
                ->take(10)
                ->values()
                ->map(fn ($payment) => $this->formatPaymentForResponse($payment))
                ->all(),
        ];
    }

    private function formatPaymentForResponse(FinancialLedgerPayment $payment): array
    {
        $methodLabels = collect($this->paymentMethods())->pluck('label', 'value');

        return [
            'id' => $payment->id,
            'amount' => (float) $payment->amount,
            'amount_formatted' => currency_format($payment->amount),
            'paid_at' => optional($payment->paid_at)->toDateString(),
            'paid_at_formatted' => optional($payment->paid_at)->format('M d, Y'),
            'payment_method' => $payment->payment_method,
            'payment_method_label' => $methodLabels[$payment->payment_method] ?? ucfirst(str_replace('_', ' ', (string) $payment->payment_method)),
            'reference' => $payment->reference,
            'notes' => $payment->notes,
            'recorded_by' => optional($payment->recorder)->name,
        ];
    }

    private function statusBadgeForLedger(FinancialLedger $ledger): array
    {
        $statusClasses = [
            FinancialLedger::STATUS_OPEN => 'bg-indigo-100 text-indigo-700',
            FinancialLedger::STATUS_CLOSED => 'bg-emerald-100 text-emerald-700',
            FinancialLedger::STATUS_ARCHIVED => 'bg-slate-200 text-slate-700',
        ];

        return [
            'label' => ucfirst($ledger->status),
            'class' => $statusClasses[$ledger->status] ?? 'bg-gray-100 text-gray-700',
        ];
    }
}
