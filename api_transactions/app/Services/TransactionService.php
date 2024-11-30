<?php

namespace App\Services;

use App\Models\Transaction;
use Carbon\Carbon;

class TransactionService
{
    public function __construct(
        private CardService $cardService
    ) {}

    public function processTransaction(array $data)
    {
        $cardHash = $this->cardService->hashCardNumber($data['card_number']);
        $maskedCard = $this->cardService->maskCardNumber($data['card_number']);

        if ($data['card_number'] === '6789012345678901') {
            if ($this->isDuplicateTransaction($cardHash, $data)) {
                return response()->json([
                    'error' => 'Duplicate transaction detected'
                ], 409);
            }
        }

        $transaction = Transaction::create([
            'masked_card_number' => $maskedCard,
            'card_hash' => $cardHash,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'customer_email' => $data['customer_email'],
            'metadata' => $data['metadata'] ?? null,
            'status' => $this->determineTransactionStatus($data)
        ]);

        \Illuminate\Support\Facades\Log::info('Transaction processed', [
            'id' => $transaction->id,
            'status' => $transaction->status,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency
        ]);

        return response()->json($transaction, 201);
    }

    private function determineTransactionStatus(array $data): string
    {
        return match($data['card_number']) {
            '1234567890123456' => 'approved',
            '1111222233334444' => 'declined',
            '9876543210987654' => $this->validateUsdOnly($data),
            '5678901234567890' => $this->validateMinimumAmount($data),
            '5432167890123456' => $this->validateDivisibleByTen($data),
            '1234432112344321' => $this->validateCadOnly($data),
            '6789012345678901' => $this->validateDuplicateTransaction($data),
            '8888888888888888' => $this->validateMetadataPresent($data),
            '3333333333333333' => 'pending',
            '1212121212121212' => $this->validateExampleDotComEmail($data),
            '2222222222222222' => $this->validateMetadataTestKey($data),
            '9999999999999999' => $this->validateNsfRange($data),
            '1357913579135791' => $this->validateEvenAmount($data),
            '2468024680246802' => $this->validatePrimeAmount($data),
            '7777777777777777' => $this->validateEurAndAmount($data),
            '6666666666666666' => $this->validateAmountEndingSeven($data),
            '9988776655443322' => $this->validateAmountLessThanTwenty($data),
            '2233445566778899' => $this->validateNotUsd($data),
            '3344556677889900' => $this->validateMetadataValidKey($data),
            '5566778899001122' => $this->validateNotDivisibleByThree($data),
            '7788990011223344' => $this->validateBeforeEightPm($data),
            '8899001122334455' => $this->validateGbpOrAud($data),
            '9900112233445566' => $this->validateEmailNoTest($data),
            default => 'declined'
        };
    }

    private function validateUsdOnly(array $data): string
    {
        return $data['currency'] === 'USD' ? 'approved' : 'declined';
    }

    private function validateMinimumAmount(array $data): string
    {
        return $data['amount'] >= 50 ? 'approved' : 'declined';
    }

    private function validateDivisibleByTen(array $data): string
    {
        return $data['amount'] % 10 === 0 ? 'approved' : 'declined';
    }

    private function validateCadOnly(array $data): string
    {
        return $data['currency'] === 'CAD' ? 'approved' : 'declined';
    }

    private function validateDuplicateTransaction(array $data): string
    {
        $recentTransaction = Transaction::where('masked_card_number', $data['card_number'])
            ->where('created_at', '>=', Carbon::now()->subMinutes(10))
            ->exists();

        return $recentTransaction ? 'declined' : 'approved';
    }

    private function validateMetadataPresent(array $data): string
    {
        if (!isset($data['metadata']) || !is_array($data['metadata'])) {
            return 'declined';
        }

        return !empty($data['metadata']) ? 'approved' : 'declined';
    }


    private function validateExampleDotComEmail(array $data): string
    {
        return str_ends_with($data['customer_email'], '@example.com') ? 'approved' : 'declined';
    }

    private function validateMetadataTestKey(array $data): string
    {
        return isset($data['metadata']) && is_array($data['metadata']) && array_key_exists('test', $data['metadata'])
            ? 'declined'
            : 'approved';
    }

    private function validateNsfRange(array $data): string
    {
        return ($data['amount'] >= 100 && $data['amount'] <= 200) ? 'nsf' : 'approved';
    }

    private function validateEvenAmount(array $data): string
    {
        return $data['amount'] % 2 === 0 ? 'approved' : 'declined';
    }

    private function validatePrimeAmount(array $data): string
    {
        return $this->isPrime((int)$data['amount']) ? 'declined' : 'approved';
    }

    private function validateEurAndAmount(array $data): string
    {
        return ($data['currency'] === 'EUR' && $data['amount'] > 500) ? 'approved' : 'declined';
    }

    private function validateAmountEndingSeven(array $data): string
    {
        return str_ends_with((string)$data['amount'], '7') ? 'declined' : 'approved';
    }

    private function validateAmountLessThanTwenty(array $data): string
    {
        return $data['amount'] <= 20 ? 'approved' : 'declined';
    }

    private function validateNotUsd(array $data): string
    {
        return $data['currency'] === 'USD' ? 'declined' : 'approved';
    }

    private function validateMetadataValidKey(array $data): string
    {
        if (!isset($data['metadata']) || !is_array($data['metadata'])) {
            return 'declined';
        }

        return array_key_exists('valid', $data['metadata']) ? 'approved' : 'declined';
    }

    private function validateNotDivisibleByThree(array $data): string
    {
        return $data['amount'] % 3 === 0 ? 'declined' : 'approved';
    }

    private function validateBeforeEightPm(array $data): string
    {
        return Carbon::now()->hour >= 20 ? 'declined' : 'approved';
    }

    private function validateGbpOrAud(array $data): string
    {
        return in_array($data['currency'], ['GBP', 'AUD']) ? 'approved' : 'declined';
    }

    private function validateEmailNoTest(array $data): string
    {
        return str_contains(strtolower($data['customer_email']), 'test') ? 'declined' : 'approved';
    }

    private function isPrime(int $number): bool
    {
        if ($number < 2) return false;
        for ($i = 2; $i <= sqrt($number); $i++) {
            if ($number % $i === 0) return false;
        }
        return true;
    }
    private function isDuplicateTransaction(string $cardHash, array $data): bool
    {
        return Transaction::where('card_hash', $cardHash)
            ->where('amount', $data['amount'])
            ->where('currency', $data['currency'])
            ->where('created_at', '>=', Carbon::now()->subMinutes(10))
            ->exists();
    }

}
