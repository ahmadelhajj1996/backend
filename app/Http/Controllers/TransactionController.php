<?php

namespace App\Http\Controllers; 


use App\Enums\TransactionType;
use App\Http\Requests\TransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;


class TransactionController extends Controller
{
 

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $this->filters($request);

        $transactions = Transaction::query()
            ->filter($filters)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        return TransactionResource::collection($transactions);
    }

 
    public function store(TransactionRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $data['occurred_at'] ??= now();

            $transaction = Transaction::create($data);

            return (new TransactionResource($transaction))
                ->response()
                ->setStatusCode(201);

        } catch (\Throwable $e) {
            Log::error('Transaction creation failed', [
                'exception' => $e->getMessage(),
                'request'   => $request->validated(),
            ]);

            return response()->json([
                'error' => 'Failed to create transaction.',
            ], 500);
        }
    }

     

    public function show(Transaction $transaction): TransactionResource
    {
        return new TransactionResource($transaction);
    }

    
    
    public function update(
        TransactionRequest $request,
        Transaction $transaction
    ): TransactionResource {
        $transaction->update($request->validated());

        return new TransactionResource($transaction->fresh());
    }

 
    public function destroy(Transaction $transaction): JsonResponse
    {
        $transaction->delete();

        return response()->json(null, 204);
    }

 

    public function summary(Request $request): JsonResponse
    {
        $summary = Transaction::query()
            ->selectRaw(
                '
                COALESCE(
                    SUM(
                        CASE
                            WHEN type = ? THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_income,

                COALESCE(
                    SUM(
                        CASE
                            WHEN type = ? THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_expense
                ',
                [
                    TransactionType::Income->value,
                    TransactionType::Expense->value,
                ]
            )
            ->first();

        $totalIncome = (int) $summary->total_income;
        $totalExpense = (int) $summary->total_expense;

        $profit = $totalIncome - $totalExpense;

        $balance = $totalIncome - $totalExpense;

        return response()->json([
            'total_income'  => $this->formatMoney($totalIncome),
            'total_expense' => $this->formatMoney($totalExpense),
            'profit'        => $this->formatMoney($profit),
            'balance'       => $this->formatMoney($balance),
        ]);
    }

 

    public function incomes(Request $request): AnonymousResourceCollection
    {
        return $this->transactionsByType(
            $request,
            TransactionType::Income,
            'total_income'
        );
    }

 
    
    public function expenses(Request $request): AnonymousResourceCollection
    {
        return $this->transactionsByType(
            $request,
            TransactionType::Expense,
            'total_expense'
        );
    }

    /**
     * Get transactions by type with their overall total.
     */
    private function transactionsByType(
        Request $request,
        TransactionType $type,
        string $totalKey
    ): AnonymousResourceCollection {

        $filters = $this->filters($request);

        // The endpoint determines the transaction type.
        unset($filters['type']);

        // Calculate the total from ALL records of this type.
        // This is independent of pagination and filters.
        $total = Transaction::query()
            ->where('type', $type)
            ->sum('amount');

        // Retrieve paginated records using requested filters.
        $transactions = Transaction::query()
            ->where('type', $type)
            ->filter($filters)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        // Add total to the resource collection response.
        $response = TransactionResource::collection($transactions);

        $response->additional([
            $totalKey => $this->formatMoney((int) $total),
        ]);

        return $response;
    }

    /**
     * Validate transaction filters.
     */
    private function filters(Request $request): array
    {
        return $request->validate([
            'type' => [
                'nullable',
                Rule::enum(TransactionType::class),
            ],
            'from' => [
                'nullable',
                'date',
            ],
            'to' => [
                'nullable',
                'date',
                'after_or_equal:from',
            ],
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);
    }

    /**
     * Get a safe pagination value.
     */
    private function perPage(Request $request): int
    {
        return max(
            1,
            min(
                $request->integer('per_page', 25),
                100
            )
        );
    }

    /**
     * Format money from minor units to decimal representation.
     */
    private function formatMoney(int $amount): string
    {
        return number_format(
            $amount / 100,
            2,
            '.',
            ''
        );
    }
}
 