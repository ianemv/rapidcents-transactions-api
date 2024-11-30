<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Services\TransactionService;


class TransactionController extends Controller
{
    public function __construct(
        private TransactionService $transactionService
    ) {}

    public function store(TransactionRequest $request)
    {
        $transaction = $this->transactionService->processTransaction($request->validated());

        return $this->transactionService->processTransaction($request->validated());
    }

    public function show(Transaction $transaction)
    {
        return response()->json($transaction);
    }
}
