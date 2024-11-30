<?php

namespace App\Services;

class CardService
{
    public function maskCardNumber(string $cardNumber): string
    {
        return substr($cardNumber, 0, 6) . str_repeat('*', 6) . substr($cardNumber, -4);
    }

    public function hashCardNumber(string $cardNumber): string
    {
        return hash('sha256', $cardNumber);
    }
}
