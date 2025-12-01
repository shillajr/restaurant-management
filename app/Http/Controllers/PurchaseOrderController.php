<?php

namespace App\Http\Controllers;

use App\Models\ChefRequisition;
use App\Models\Entity;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\SmsNotificationService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\Mail;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['requisition.chef', 'approver', 'creator']);

        $user = Auth::user();
        if ($user && $user->can('send purchase orders') && ! $user->can('approve purchase orders')) {
            $query->whereIn('workflow_status', ['approved', 'sent_to_vendor', 'completed']);
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by workflow status
        if ($request->filled('workflow_status')) {
            $query->where('workflow_status', $request->workflow_status);
        }
        
        // Search by PO number or requisition ID
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('po_number', 'like', '%' . $request->search . '%')
                  ->orWhere('requisition_id', 'like', '%' . $request->search . '%');
            });
        }
        
        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Sort
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $purchaseOrders = $query->paginate(15)->withQueryString();

        return view('purchase-orders.index', compact('purchaseOrders'));
    }

    /**
     * Show the form for creating a new resource.
     * Redirect to purchase orders index - POs are created from requisitions
     */
    public function create(Request $request)
    {
        return redirect()->route('purchase-orders.index')
            ->with('info', 'Purchase Orders are generated from approved requisitions. Please go to the requisition and click "Generate Purchase Order".');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        \Log::info('PurchaseOrderController@store - Request received', [
            'user_id' => auth()->id(),
            'request_data' => $request->all(),
            'session_id' => session()->getId(),
        ]);
        
        $user = $request->user();

        if (! $user || (! $user->can('approve purchase orders') && ! $user->can('approve requisitions'))) {
            abort(403, 'You are not authorized to generate purchase orders.');
        }

        try {
            // Lean validation and creation from approved requisition
            $validated = $request->validate([
                'requisition_id' => 'required|exists:chef_requisitions,id',
                'requested_delivery_date' => 'nullable|date',
                'notes' => 'nullable|string',
                'assigned_to' => 'nullable|exists:users,id',
            ]);

            $req = ChefRequisition::with('purchaseOrder')->findOrFail($validated['requisition_id']);
            
            // Verify requisition is approved
            if ($req->status !== 'approved') {
                return back()->with('error', 'Only approved requisitions can generate a Purchase Order.');
            }
            
            // Check if PO already exists
            if ($req->purchaseOrder) {
                return redirect()->route('purchase-orders.show', $req->purchaseOrder->id)
                    ->with('info', 'A Purchase Order already exists for this requisition.');
            }

            $items = collect($req->items ?? []);
            
            // Ensure there are items
            if ($items->isEmpty()) {
                return back()->with('error', 'Cannot create Purchase Order: No items found in requisition.');
            }
            
            $totalQuantity = $items->sum(function ($i) { return (float)($i['quantity'] ?? 0); });
            $subtotal = $items->sum(function ($i) { return (float)($i['quantity'] ?? 0) * (float)($i['price'] ?? 0); });
            $grandTotal = $subtotal;

            $assignedToId = $validated['assigned_to'] ?? null;
            if (! $assignedToId) {
                $assignedToId = Auth::id();
            }

            $po = PurchaseOrder::create([
                'po_number' => PurchaseOrder::generatePONumber(),
                'requisition_id' => $req->id,
                'created_by' => $req->chef_id,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'generated_by' => Auth::id(),
                'assigned_to' => $assignedToId,
                'requested_delivery_date' => $validated['requested_delivery_date'] ?? $req->requested_for_date,
                'items' => $items->map(function ($it) {
                    $qty = (float)($it['quantity'] ?? 0);
                    $price = (float)($it['price'] ?? 0);
                    return [
                        'item' => $it['item'] ?? ($it['item_id'] ?? ''),
                        'item_id' => $it['item_id'] ?? null,
                        'vendor' => $it['vendor'] ?? null,
                        'unit' => $it['uom'] ?? ($it['unit'] ?? ''),
                        'uom' => $it['uom'] ?? ($it['unit'] ?? ''),
                        'price' => $price,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'line_total' => $qty * $price,
                    ];
                })->values()->toArray(),
                'total_quantity' => $totalQuantity,
                'subtotal' => $subtotal,
                'tax' => 0,
                'other_charges' => 0,
                'grand_total' => $grandTotal,
                'status' => 'open',
                'workflow_status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            activity()
                ->performedOn($po)
                ->causedBy(Auth::user())
                ->withProperties([
                    'po_number' => $po->po_number,
                    'requisition_id' => $req->id
                ])
                ->log('Purchase Order generated from approved requisition');

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json($po, 201);
            }

            return redirect()->route('purchase-orders.show', $po->id)
                ->with('success', "Purchase Order {$po->po_number} generated successfully from requisition.");
                
        } catch (\Exception $e) {
            \Log::error('Error generating Purchase Order: ' . $e->getMessage(), [
                'requisition_id' => $request->input('requisition_id'),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Failed to generate Purchase Order: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        $relations = [
            'requisition.chef',
            'approver',
            'assignedTo',
            'generator',
        ];

        if (Schema::hasTable('financial_ledgers')) {
            $relations[] = 'creditLedgers.payments.recorder';
            $relations[] = 'creditLedgers.vendor';
            $relations[] = 'creditLedgers.creditSale';
        } else {
            $purchaseOrder->setRelation('creditLedgers', collect());
        }

        $purchaseOrder->loadMissing($relations);
        
        // Get items grouped by vendor
        $itemsByVendor = $purchaseOrder->getItemsByVendor();
        $vendorStats = $purchaseOrder->getVendorStats();
        
        return view('purchase-orders.show', compact('purchaseOrder', 'itemsByVendor', 'vendorStats'));
    }
    
    /**
     * Update the status of the specified purchase order.
     */
    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'workflow_status' => 'required|in:pending,sent_to_vendor,returned,approved,rejected'
        ]);

        $oldWorkflowStatus = $purchaseOrder->workflow_status;
        $newWorkflowStatus = $request->workflow_status;

        $lockedTransitionMap = [
            'approved' => ['approved', 'sent_to_vendor', 'cancelled'],
            'sent_to_vendor' => ['sent_to_vendor', 'completed', 'cancelled'],
            'completed' => ['completed'],
            'cancelled' => ['cancelled'],
        ];

        if (isset($lockedTransitionMap[$oldWorkflowStatus]) && ! in_array($newWorkflowStatus, $lockedTransitionMap[$oldWorkflowStatus], true)) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'Approved purchase orders cannot move back to an earlier stage. Cancel the PO instead.');
        }

        $purchaseOrder->update([
            'workflow_status' => $newWorkflowStatus
        ]);

        // Log the workflow status change
        activity()
            ->performedOn($purchaseOrder)
            ->causedBy(auth()->user())
            ->withProperties([
                'old_workflow_status' => $oldWorkflowStatus,
                'new_workflow_status' => $newWorkflowStatus
            ])
            ->log('PO workflow status updated');
        
        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order workflow status updated successfully to ' . ucfirst(str_replace('_', ' ', $newWorkflowStatus)) . '.');
    }

    /**
     * Approve a purchase order and notify the purchasing team.
     */
    public function approve(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('approve', $purchaseOrder);

        $approver = Auth::user();
        $approver->loadMissing('entity.notificationSettings', 'entity.integrationSettings');
        $entity = $approver->entity;
        $smsService = app(SmsNotificationService::class);
        $whatsAppService = app(WhatsAppNotificationService::class);

        DB::transaction(function () use ($purchaseOrder, $approver) {
            $purchaseOrder->update([
                'status' => 'open',
                'workflow_status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);
        });

        $purchaseOrder->refresh();

        if ($entity) {
            $this->notifyPurchasingTeam($entity, $approver, $purchaseOrder, $smsService, $whatsAppService);
        }

        activity()
            ->performedOn($purchaseOrder)
            ->causedBy($approver)
            ->log('PO approved and purchasing team notified');

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'PO approved. Purchasing team notified.');
    }

    /**
     * Dispatch an approved purchase order to vendors.
     */
    public function sendToVendors(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('send', $purchaseOrder);

        if ($purchaseOrder->workflow_status !== 'approved') {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'Only approved purchase orders can be sent to vendors.');
        }

        if ((int) Auth::id() === (int) ($purchaseOrder->generated_by ?? 0)) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'A different team member must send this purchase order to the vendor.');
        }

        $sender = Auth::user();
        $sender->loadMissing('entity.notificationSettings', 'entity.integrationSettings');
        $entity = $sender->entity;
        $whatsAppService = app(WhatsAppNotificationService::class);

        DB::transaction(function () use ($purchaseOrder) {
            $purchaseOrder->update([
                'workflow_status' => 'sent_to_vendor',
            ]);
        });

        $purchaseOrder->refresh();
        $this->notifyVendors($purchaseOrder, $sender, $entity, $whatsAppService);

        activity()
            ->performedOn($purchaseOrder)
            ->causedBy($sender)
            ->log('PO sent to vendors');

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'PO sent to vendors successfully.');
    }

    /**
     * Notify the purchasing contacts via SMS, WhatsApp, and email.
     */
    protected function notifyPurchasingTeam(?Entity $entity, $approver, PurchaseOrder $purchaseOrder, SmsNotificationService $smsService, WhatsAppNotificationService $whatsAppService): void
    {
        if (! $entity) {
            return;
        }

        $notifications = $entity->notificationSettings;

        if (! $notifications || ! $notifications->notify_purchase_orders) {
            return;
        }

        $emails = collect($notifications->purchase_order_notification_emails ?? [])->filter();
        $phones = collect($notifications->purchase_order_notification_phones ?? [])->filter();

        if ($emails->isEmpty() && $phones->isEmpty()) {
            return;
        }

        $vendorCount = count($purchaseOrder->getItemsByVendor());
        $totalFormatted = number_format((float) $purchaseOrder->grand_total, 2);
        $poNumber = $purchaseOrder->po_number;

        $smsMessage = "PO {$poNumber} approved by {$approver->name}. Total {$totalFormatted}. Please log in to send to vendors.";
        $whatsAppMessage = implode("\n", [
            "PO {$poNumber} has been approved and is ready to send to vendors.",
            'Approved by: ' . $approver->name,
            'Vendors: ' . $vendorCount,
            'Total: ' . $totalFormatted,
            '',
            'Log in to the dashboard to dispatch the PO.',
        ]);

        foreach ($phones as $phone) {
            $smsService->sendPurchaseOrderMessage($entity, $phone, $smsMessage);
            $whatsAppService->sendPurchaseOrderMessage($entity, $phone, $whatsAppMessage);
        }

        if ($emails->isNotEmpty()) {
            $emailBody = implode("\n", [
                "Purchase Order {$poNumber} has been approved.",
                'Approved by: ' . $approver->name,
                'Total Amount: ' . $totalFormatted,
                'Vendors: ' . $vendorCount,
                '',
                'Sign in to the dashboard to review and send the PO to vendors.',
            ]);

            foreach ($emails as $email) {
                Mail::raw($emailBody, function ($message) use ($email, $poNumber) {
                    $message->to($email)
                        ->subject('Purchase Order ' . $poNumber . ' Ready for Dispatch');
                });
            }
        }
    }

    /**
     * Notify vendors (email + WhatsApp) when a purchaser dispatches a PO.
     */
    protected function notifyVendors(PurchaseOrder $purchaseOrder, $sender, ?Entity $entity, WhatsAppNotificationService $whatsAppService): void
    {
        $itemsByVendor = $purchaseOrder->getItemsByVendor();

        if (empty($itemsByVendor)) {
            return;
        }

        $vendorNames = collect($itemsByVendor)
            ->pluck('vendor_name')
            ->filter()
            ->unique()
            ->all();

        $vendorsByName = Vendor::query()
            ->whereIn('name', $vendorNames)
            ->get()
            ->keyBy(fn (Vendor $vendor) => strtolower($vendor->name));

        $fallbackVendor = $purchaseOrder->supplier_id
            ? Vendor::find($purchaseOrder->supplier_id)
            : null;

        foreach ($itemsByVendor as $vendorSection) {
            $vendorName = $vendorSection['vendor_name'] ?? null;
            $vendor = null;

            if ($vendorName) {
                $vendor = $vendorsByName->get(strtolower($vendorName));
            }

            if (! $vendor && $fallbackVendor) {
                $vendor = $fallbackVendor;
            }

            $displayName = $vendor?->name ?? ($vendorName ?: 'Vendor');

            $lines = collect($vendorSection['items'] ?? [])
                ->values()
                ->map(function (array $item, int $index) {
                    $quantity = (float) ($item['quantity'] ?? 0);
                    $unit = trim((string) ($item['uom'] ?? ($item['unit'] ?? '')));
                    $unitSuffix = $unit !== '' ? ' ' . $unit : '';
                    $price = (float) ($item['price'] ?? 0);
                    $lineTotal = $price * $quantity;

                    return sprintf(
                        '%d) %s | Qty: %s%s | Unit: %s | Line: %s',
                        $index + 1,
                        $item['item'] ?? ($item['item_id'] ?? 'Item'),
                        $this->formatQuantity($quantity),
                        $unitSuffix,
                        $this->formatMoney($price),
                        $this->formatMoney($lineTotal)
                    );
                })
                ->all();

            if ($vendor && $vendor->email) {
                $emailLines = [
                    "Dear {$displayName},",
                    '',
                    'Please find your purchase order details below:',
                    "PO Number: {$purchaseOrder->po_number}",
                    "Linked Requisition: {$purchaseOrder->requisition_id}",
                    'Sent By: ' . $sender->name,
                ];

                if (! empty($lines)) {
                    $emailLines[] = '';
                    $emailLines[] = 'Items:';
                    foreach ($lines as $line) {
                        $emailLines[] = $line;
                    }
                }

                $emailLines[] = '';
                $emailLines[] = 'Subtotal: ' . $this->formatMoney((float) ($vendorSection['vendor_subtotal'] ?? 0));
                $emailLines[] = '';
                $emailLines[] = 'Kindly confirm receipt and arrange delivery as instructed.';
                $emailLines[] = 'Regards,';
                $emailLines[] = $sender->name;

                $body = implode("\n", $emailLines);

                Mail::raw($body, function ($message) use ($vendor, $purchaseOrder, $displayName) {
                    $message->to($vendor->email, $displayName)
                        ->subject('Purchase Order ' . $purchaseOrder->po_number . ' Details');
                });
            }

            $phones = $this->extractVendorPhones($vendor);

            if (! $entity || empty($phones)) {
                continue;
            }

            $messageLines = [
                "Purchase Order {$purchaseOrder->po_number} for {$displayName}",
            ];

            if ($purchaseOrder->requisition_id) {
                $messageLines[] = 'Requisition: ' . $purchaseOrder->requisition_id;
            }

            $messageLines[] = 'Sent by: ' . $sender->name;

            if (! empty($lines)) {
                $messageLines[] = '';
                $messageLines[] = 'Items:';
                foreach ($lines as $line) {
                    $messageLines[] = $line;
                }
            }

            $messageLines[] = '';
            $messageLines[] = 'Subtotal: ' . $this->formatMoney((float) ($vendorSection['vendor_subtotal'] ?? 0));
            $messageLines[] = '';
            $messageLines[] = 'Please confirm receipt once you have this order.';

            $whatsAppMessage = implode("\n", $messageLines);

            foreach ($phones as $phone) {
                $whatsAppService->sendPurchaseOrderMessage($entity, $phone, $whatsAppMessage);
            }
        }
    }

    /**
     * Extract the vendor's phone numbers as an array.
     */
    protected function extractVendorPhones(?Vendor $vendor): array
    {
        if (! $vendor || ! $vendor->is_active) {
            return [];
        }

        $raw = $vendor->phone;
        $numbers = [];

        if (is_array($raw)) {
            $numbers = $raw;
        } elseif (is_string($raw)) {
            $trimmed = trim($raw);

            if ($trimmed !== '' && str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) {
                $decoded = json_decode($trimmed, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $numbers = $decoded;
                }
            }

            if (empty($numbers)) {
                $numbers = preg_split('/[,\\n;\\/|]+/', $raw) ?: [];
            }
        }

        return collect($numbers)
            ->map(function ($value) {
                if (is_string($value) || is_numeric($value)) {
                    return trim((string) $value);
                }

                return null;
            })
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    protected function formatQuantity(float $quantity): string
    {
        $formatted = number_format($quantity, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }

    protected function formatMoney(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Reject PO with reason and revert requisition.
     */
    public function reject(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if (in_array($purchaseOrder->workflow_status, ['approved', 'sent_to_vendor', 'completed', 'cancelled'], true)) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'Approved purchase orders cannot be rejected. Cancel the PO instead.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        DB::transaction(function () use ($purchaseOrder, $validated) {
            $purchaseOrder->update([
                'status' => 'open',
                'workflow_status' => 'rejected',
                'notes' => $validated['rejection_reason'],
            ]);

            if ($purchaseOrder->requisition) {
                $purchaseOrder->requisition->update(['status' => 'rejected']);
            }

            activity()
                ->performedOn($purchaseOrder)
                ->causedBy(Auth::user())
                ->withProperties(['rejection_reason' => $validated['rejection_reason']])
                ->log('PO rejected');
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'PO rejected and requisition updated.');
    }

    /**
     * Return PO for changes and revert to requisition stage.
     */
    public function returnForChanges(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if (in_array($purchaseOrder->workflow_status, ['approved', 'sent_to_vendor', 'completed', 'cancelled'], true)) {
            return redirect()->route('purchase-orders.show', $purchaseOrder)
                ->with('error', 'Approved purchase orders cannot be returned for changes. Cancel the PO instead.');
        }

        $validated = $request->validate([
            'return_reason' => 'required|string|max:1000',
        ]);

        DB::transaction(function () use ($purchaseOrder, $validated) {
            $purchaseOrder->update([
                'workflow_status' => 'returned',
                'notes' => $validated['return_reason'],
            ]);

            if ($purchaseOrder->requisition) {
                $purchaseOrder->requisition->update(['status' => 'changes_requested']);
            }

            activity()
                ->performedOn($purchaseOrder)
                ->causedBy(Auth::user())
                ->withProperties(['return_reason' => $validated['return_reason']])
                ->log('PO returned for changes');
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'PO returned for changes; requisition updated.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);
        
        if ($purchaseOrder->status === 'completed') {
            return redirect()->route('purchase-orders.index')
                ->with('error', 'Completed purchase orders cannot be edited.');
        }
        
        $suppliers = Supplier::where('is_active', true)->get();
        
        return view('purchase-orders.edit', compact('purchaseOrder', 'suppliers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);
        
        if ($purchaseOrder->status === 'completed') {
            return redirect()->route('purchase-orders.index')
                ->with('error', 'Completed purchase orders cannot be updated.');
        }

        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:vendors,id',
            'items' => 'required|array|min:1',
            'items.*.item' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string',
            'items.*.estimated_price' => 'nullable|numeric|min:0',
            'estimated_total' => 'nullable|numeric|min:0',
            'delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $purchaseOrder->update([
            'supplier_id' => $validated['supplier_id'] ?? null,
            'items' => $validated['items'],
            'estimated_total' => $validated['estimated_total'] ?? null,
            'delivery_date' => $validated['delivery_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        activity()
            ->performedOn($purchaseOrder)
            ->causedBy(Auth::user())
            ->log('Purchase order updated');

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase order updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('delete', $purchaseOrder);
        
        if ($purchaseOrder->status === 'completed') {
            return redirect()->route('purchase-orders.index')
                ->with('error', 'Completed purchase orders cannot be deleted.');
        }

        DB::transaction(function () use ($purchaseOrder) {
            // Revert requisition status if needed
            if ($purchaseOrder->chefRequisition) {
                $purchaseOrder->chefRequisition()->update(['status' => 'approved']);
            }

            activity()
                ->performedOn($purchaseOrder)
                ->causedBy(Auth::user())
                ->log('Purchase order deleted');

            $purchaseOrder->delete();
        });

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase order deleted successfully.');
    }

    /**
     * Mark the purchase order as purchased/completed.
     */
    public function markPurchased(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('markPurchased', $purchaseOrder);
        
        if ($purchaseOrder->status === 'completed' || $purchaseOrder->workflow_status === 'completed') {
            return back()->with('error', 'This purchase order is already marked as completed.');
        }

        if ($purchaseOrder->workflow_status !== 'sent_to_vendor') {
            return back()->with('error', 'Send this purchase order to the vendor before recording the purchase details.');
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:vendors,id',
            'invoice_number' => 'required|string|max:100',
            'total_amount' => 'required|numeric|min:0',
            'receipt' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',
            'purchased_date' => 'nullable|date',
        ]);

        DB::transaction(function () use ($request, $purchaseOrder, $validated) {
            // Store receipt file if provided
            $receiptPath = $request->hasFile('receipt') ? $request->file('receipt')->store('receipts', 'public') : null;

            // Update purchase order
            $purchaseOrder->update([
                'supplier_id' => $validated['supplier_id'],
                'invoice_number' => $validated['invoice_number'],
                'total_amount' => $validated['total_amount'],
                'receipt_path' => $receiptPath,
                'purchased_at' => $validated['purchased_date'] ?? now(),
                'status' => 'closed',
                'workflow_status' => 'completed',
            ]);

            // Create expense record tied to this purchase order
            $primaryVendor = collect($purchaseOrder->items ?? [])->pluck('vendor')->first();

            $purchaseOrder->expense()->create([
                'created_by' => Auth::id(),
                'category' => 'Purchases',
                'vendor' => $primaryVendor,
                'description' => 'Purchase Order #' . ($purchaseOrder->po_number ?? $purchaseOrder->id) . ' - Invoice: ' . $validated['invoice_number'],
                'amount' => $validated['total_amount'],
                'expense_date' => $validated['purchased_date'] ?? now(),
                'invoice_number' => $validated['invoice_number'],
                'receipt_path' => $receiptPath,
                'items' => $purchaseOrder->items,
            ]);

            activity()
                ->performedOn($purchaseOrder)
                ->causedBy(Auth::user())
                ->withProperties([
                    'invoice_number' => $validated['invoice_number'],
                    'total_amount' => $validated['total_amount'],
                ])
                ->log('Purchase order marked as purchased');
        });

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'message' => 'Purchase order marked as purchased',
                'purchase_order' => $purchaseOrder
            ], 200);
        }

        return back()->with('success', 'Purchase marked as completed and expense recorded.');
    }

    /**
     * Quick completion action for purchasers when detailed expense entry is not required.
     */
    public function markCompleted(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('markPurchased', $purchaseOrder);

        if (in_array($purchaseOrder->workflow_status, ['completed'], true)) {
            return back()->with('info', 'This purchase order is already completed.');
        }

        if ($purchaseOrder->workflow_status !== 'sent_to_vendor') {
            return back()->with('error', 'Send this purchase order to the vendor before marking it as completed.');
        }

        DB::transaction(function () use ($purchaseOrder) {
            $primaryVendor = collect($purchaseOrder->items ?? [])->pluck('vendor')->first();
            $calculatedAmount = $purchaseOrder->total_amount
                ?? ($purchaseOrder->grand_total
                    ?? collect($purchaseOrder->items ?? [])->sum(function ($item) {
                        return ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
                    }));
            $purchasedAt = $purchaseOrder->purchased_at ?? now();

            $purchaseOrder->update([
                'status' => 'closed',
                'workflow_status' => 'completed',
                'purchased_at' => $purchasedAt,
                'total_amount' => $calculatedAmount,
            ]);

            if (! $purchaseOrder->expense) {
                $purchaseOrder->expense()->create([
                    'created_by' => Auth::id(),
                    'category' => 'Purchases',
                    'vendor' => $primaryVendor,
                    'description' => 'Purchase Order #' . ($purchaseOrder->po_number ?? $purchaseOrder->id) . ' completed.',
                    'amount' => $calculatedAmount ?? 0,
                    'expense_date' => $purchasedAt,
                    'invoice_number' => $purchaseOrder->invoice_number,
                    'receipt_path' => $purchaseOrder->receipt_path,
                    'items' => $purchaseOrder->items,
                ]);
            }

            activity()
                ->performedOn($purchaseOrder)
                ->causedBy(Auth::user())
                ->log('Purchase order marked as completed');
        });

        return back()->with('success', 'Purchase order marked as completed.');
    }

    /**
     * Download the receipt/invoice file.
     */
    public function downloadReceipt(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);
        
        if (!$purchaseOrder->receipt_path || !Storage::disk('public')->exists($purchaseOrder->receipt_path)) {
            return back()->with('error', 'Receipt file not found.');
        }

        return Storage::disk('public')->download($purchaseOrder->receipt_path);
    }
}
