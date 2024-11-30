<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Transaction;
use Carbon\Carbon;use Illuminate\Foundation\Testing\DatabaseMigrations;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    private function getBasePayload(): array
    {
        return [
            'amount' => 100.00,
            'currency' => 'USD',
            'customer_email' => 'test@example.com',
            'metadata' => ['order_id' => '12345']
        ];
    }

    /** @test */
    public function it_approves_transactions_for_always_approved_card()
    {
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => '1234567890123456'
        ]);

        $response = $this->postJson('/api/transactions', $payload);

        $response->assertStatus(201)
            ->assertJson(['status' => 'approved']);
    }

    /** @test */
    public function it_declines_transactions_for_always_declined_card()
    {
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => '1111222233334444'
        ]);

        $response = $this->postJson('/api/transactions', $payload);

        $response->assertStatus(201)
            ->assertJson(['status' => 'declined']);
    }

    /** @test */
    public function it_handles_usd_only_card()
    {
        $card = '9876543210987654';

        // Test USD approval
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => $card,
            'currency' => 'USD'
        ]);

        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'approved']);

        // Test non-USD decline
        $payload['currency'] = 'EUR';

        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'declined']);
    }

    /** @test */
    public function it_validates_minimum_amount()
    {
        $card = '5678901234567890';

        // Test below minimum
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => $card,
            'amount' => 49.99
        ]);

        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'declined']);

        // Test at minimum
        $payload['amount'] = 50.00;

        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'approved']);
    }

   /** @test */
    public function it_detects_duplicate_transactions()
    {
        $this->artisan('migrate:fresh');

        // First transaction with a different card number that's always approved
        $firstPayload = array_merge($this->getBasePayload(), [
            'card_number' => '1234567890123456'  // This card is always approved
        ]);

        $this->postJson('/api/transactions', $firstPayload)
            ->assertStatus(201);

        // Now test duplicate detection with the special card
        $duplicatePayload = array_merge($this->getBasePayload(), [
            'card_number' => '6789012345678901'
        ]);

        $this->postJson('/api/transactions', $duplicatePayload)
            ->assertStatus(201);

        // Try the exact same transaction again
        $this->postJson('/api/transactions', $duplicatePayload)
            ->assertStatus(409);
    }


    /** @test */
    public function it_validates_time_based_restrictions()
    {
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => '7788990011223344'
        ]);

        // Test before 8 PM
        Carbon::setTestNow('2024-01-01 19:59:59');

        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'approved']);

        // Test after 8 PM
        Carbon::setTestNow('2024-01-01 20:00:00');

        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'declined']);
    }

    /** @test */
    public function it_validates_nsf_range()
    {
        $card = '9999999999999999';

        // Test below range
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => $card,
            'amount' => 99.99
        ]);

        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'approved']);

        // Test in NSF range
        $payload['amount'] = 150.00;

        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'nsf']);

        // Test above range
        $payload['amount'] = 200.01;

        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'approved']);
    }

    /** @test */
    public function it_validates_request_format()
    {
        $response = $this->postJson('/api/transactions', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['card_number', 'amount', 'currency', 'customer_email']);
    }
}
