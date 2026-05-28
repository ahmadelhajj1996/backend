<?php
namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Variation;
use App\Notifications\LowStockNotification;
use App\Notifications\NewOrderNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{

    public function index(Request $request)
    {
        try {

            $query = Order::with([
                'client',
                'items.product',
                'items.variation.images',
            ]);

            // Filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            if ($request->has('client_id')) {
                $query->where('client_id', $request->client_id);
            }

            if ($request->has('search')) {

                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortField = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $allowedSortFields = [
                'id',
                'order_number',
                'status',
                'grand_total',
                'created_at',
            ];

            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortOrder);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Pagination
            $perPage = $request->get('per_page', 15);

            $orders = $query->paginate($perPage);

            return $this->successResponse(
                $orders,
                'Orders retrieved successfully'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to retrieve orders: ' . $e->getMessage(),
                500
            );
        }
    }

    public function show($id)
    {
        try {

            $order = Order::with([
                'client',
                'items.product',
                'items.variation.images',
            ])->find($id);

            if (! $order) {
                return $this->notFoundResponse('Order not found');
            }

            return $this->successResponse(
                $order,
                'Order retrieved successfully'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to retrieve order: ' . $e->getMessage(),
                500
            );
        }
    }

    public function store(Request $request)
    {
        try {

            $validated = $this->validateOrder($request);

            $order = DB::transaction(function () use ($validated) {

                $order = Order::create([

                    'client_id'        => $validated['client_id'],

                    'subtotal'         => $validated['subtotal'],

                    'grand_total'      => $validated['grand_total'],

                    'item_count'       => $validated['item_count'],

                    'payment_method'   => $validated['payment_method'] ?? 'cash',

                    'payment_status'   => $validated['payment_status'] ?? 'pending',

                    'shipping_address' => $validated['shipping_address'],

                    'billing_address'  => $validated['billing_address'] ?? null,

                    'notes'            => $validated['notes'] ?? null,
                ]);

                foreach ($validated['items'] as $item) {

                    $variation = Variation::lockForUpdate()
                        ->find($item['variation_id']);

                    if (! $variation) {

                        throw ValidationException::withMessages([
                            'variation_id' => ['Variation not found'],
                        ]);
                    }

                    // Check stock
                    if ($variation->quantity < $item['quantity']) {

                        throw ValidationException::withMessages([
                            'stock' => [
                                "Insufficient stock for SKU {$variation->sku}",
                            ],
                        ]);
                    }

                    // Create item
                    $order->items()->create([

                        'product_id'          => $item['product_id'],

                        'variation_id'        => $item['variation_id'],

                        'unit_price'          => $item['unit_price'],

                        'quantity'            => $item['quantity'],

                        'subtotal'            => $item['subtotal'],

                        'selected_attributes' => $item['selected_attributes'] ?? [],
                    ]);

                    $variation->decrement(
                        'quantity',
                        $item['quantity']
                    );

                    $variation->increment(
                        'sold_count',
                        $item['quantity']
                    );

                    $variation->product()->increment(
                        'sold_count',
                        $item['quantity']
                    );

                    $variation->refresh();

                    $this->notifyLowStock($variation);

                }

                return $order->load([
                    'client',
                    'items.product',
                    'items.variation.images',
                ]);
            });

            // Notify admins
            $admins = Admin::all();

            foreach ($admins as $admin) {
                $admin->notify(new NewOrderNotification($order));
            }

            return $this->createdResponse(
                $order,
                'Order created successfully'
            );

        } catch (ValidationException $e) {

            return $this->validationErrorResponse(
                $e->errors(),
                'Validation failed'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to create order: ' . $e->getMessage(),
                500
            );
        }
    }

    public function update(Request $request, $id)
    {
        try {

            $order = Order::with('items')->find($id);

            if (! $order) {
                return $this->notFoundResponse('Order not found');
            }

            $validated = $this->validateOrder($request);

            $updatedOrder = DB::transaction(function () use ($order, $validated) {

                // Restore old stock
                foreach ($order->items as $oldItem) {

                    $variation = Variation::lockForUpdate()
                        ->find($oldItem->variation_id);

                    if ($variation) {

                        $variation->increment(
                            'quantity',
                            $oldItem->quantity
                        );

                        $variation->decrement(
                            'sold_count',
                            $oldItem->quantity
                        );

                        $variation->product()->decrement(
                            'sold_count',
                            $oldItem->quantity
                        );
                    }
                }

                // Delete old items
                $order->items()->delete();

                // Update order
                $order->update([

                    'client_id'        => $validated['client_id'],

                    'subtotal'         => $validated['subtotal'],

                    'grand_total'      => $validated['grand_total'],

                    'item_count'       => $validated['item_count'],

                    'payment_method'   => $validated['payment_method'] ?? 'cash',

                    'payment_status'   => $validated['payment_status'] ?? 'pending',

                    'shipping_address' => $validated['shipping_address'],

                    'billing_address'  => $validated['billing_address'] ?? null,

                    'notes'            => $validated['notes'] ?? null,
                ]);

                // Recreate items
                foreach ($validated['items'] as $item) {

                    $variation = Variation::lockForUpdate()
                        ->find($item['variation_id']);

                    if (! $variation) {

                        throw ValidationException::withMessages([
                            'variation_id' => ['Variation not found'],
                        ]);
                    }

                    if ($variation->quantity < $item['quantity']) {

                        throw ValidationException::withMessages([
                            'stock' => [
                                "Insufficient stock for SKU {$variation->sku}",
                            ],
                        ]);
                    }

                    $order->items()->create([

                        'product_id'          => $item['product_id'],

                        'variation_id'        => $item['variation_id'],

                        'unit_price'          => $item['unit_price'],

                        'quantity'            => $item['quantity'],

                        'subtotal'            => $item['subtotal'],

                        'selected_attributes' => $item['selected_attributes'] ?? [],
                    ]);

                    $variation->decrement(
                        'quantity',
                        $item['quantity']
                    );

                    $variation->increment(
                        'sold_count',
                        $item['quantity']
                    );

                    $variation->product()->increment(
                        'sold_count',
                        $item['quantity']
                    );

                    $variation->refresh();

                    $this->notifyLowStock($variation);

                }

                return $order->load([
                    'client',
                    'items.product',
                    'items.variation.images',
                ]);
            });

            return $this->updatedResponse(
                $updatedOrder,
                'Order updated successfully'
            );

        } catch (ValidationException $e) {

            return $this->validationErrorResponse(
                $e->errors(),
                'Validation failed'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to update order: ' . $e->getMessage(),
                500
            );
        }
    }

    public function destroy($id)
    {
        try {

            $order = Order::find($id);

            if (! $order) {
                return $this->notFoundResponse('Order not found');
            }

            DB::transaction(function () use ($order) {

                // Restore stock before delete
                foreach ($order->items as $item) {

                    $variation = Variation::find($item->variation_id);

                    if ($variation) {

                        $variation->increment(
                            'quantity',
                            $item->quantity
                        );

                        $variation->decrement(
                            'sold_count',
                            $item->quantity
                        );

                        $variation->product()->decrement(
                            'sold_count',
                            $item->quantity
                        );
                    }
                }

                $order->items()->delete();

                $order->delete();
            });

            return $this->deletedResponse(
                'Order deleted successfully'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to delete order: ' . $e->getMessage(),
                500
            );
        }
    }

    private function validateOrder(Request $request)
    {
        return $request->validate([

            'client_id'                           => 'required|exists:clients,id',

            'subtotal'                            => 'required|numeric|min:0',

            'grand_total'                         => 'required|numeric|min:0',

            'item_count'                          => 'required|integer|min:1',

            'payment_method'                      => [
                'sometimes',
                Rule::in(['cash', 'card', 'paypal']),
            ],

            'payment_status'                      => [
                'sometimes',
                Rule::in(['pending', 'paid', 'failed']),
            ],

            'shipping_address'                    => 'required|string',

            'billing_address'                     => 'nullable|string',

            'notes'                               => 'nullable|string',

            'items'                               => 'required|array|min:1',

            'items.*.product_id'                  => 'required|exists:products,id',

            'items.*.variation_id'                => 'required|exists:variations,id',

            'items.*.quantity'                    => 'required|integer|min:1',

            'items.*.unit_price'                  => 'required|numeric|min:0',

            'items.*.subtotal'                    => 'required|numeric|min:0',

            'items.*.selected_attributes'         => 'nullable|array',

            'items.*.selected_attributes.*.name'  => 'required|string',

            'items.*.selected_attributes.*.value' => 'required|string',
        ]);
    }

    private function notifyLowStock(Variation $variation)
    {

        $variation->load('product');

        if ($variation->quantity < 5) {

            $admins = Admin::all();

            foreach ($admins as $admin) {

                $admin->notify(
                    new LowStockNotification(
                        $variation,
                        'critical'
                    )
                );
            }

            return;
        }

        if ($variation->quantity < 10) {

            $admins = Admin::all();

            foreach ($admins as $admin) {

                $admin->notify(
                    new LowStockNotification(
                        $variation,
                        'low'
                    )
                );
            }
        }
    }

}
