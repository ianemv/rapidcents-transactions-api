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
            'card_number' => '1234567890123456'
        ]);

        $this->postJson('/api/transactions', $firstPayload)
            ->assertStatus(201);

        $duplicatePayload = array_merge($this->getBasePayload(), [
            'card_number' => '6789012345678901',
            'amount' => rand(1000, 9999)
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

    /** @test */
    public function it_validates_metadata_test_key()
    {
        // Test with 'test' key in metadata - should be declined
        $payloadWithTest = array_merge($this->getBasePayload(), [
            'card_number' => '2222222222222222',
            'metadata' => ['test' => 'value']
        ]);

        $this->postJson('/api/transactions', $payloadWithTest)
            ->assertStatus(201)
            ->assertJson(['status' => 'declined']);

        // Test without 'test' key in metadata - should be approved
        $payloadWithoutTest = array_merge($this->getBasePayload(), [
            'card_number' => '2222222222222222',
            'metadata' => ['other_key' => 'value']
        ]);

        $this->postJson('/api/transactions', $payloadWithoutTest)
            ->assertStatus(201)
            ->assertJson(['status' => 'approved']);
    }

    /** @test */
    public function it_validates_divisible_by_ten()
    {
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => '5432167890123456'
        ]);

        // Test divisible by 10
        $payload['amount'] = 70.00;
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'approved']);

        // Test not divisible by 10
        $payload['amount'] = 75.00;
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'declined']);
    }

    /** @test */
    public function it_validates_cad_only()
    {
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => '1234432112344321'
        ]);

        // Test CAD approval
        $payload['currency'] = 'CAD';
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'approved']);

        // Test non-CAD decline
        $payload['currency'] = 'USD';
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'declined']);
    }

    /** @test */
    public function it_validates_metadata_missing()
    {
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => '8888888888888888'
        ]);

        // Test with metadata
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'approved']);

        // Test without metadata
        unset($payload['metadata']);
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'declined']);
    }

    /** @test */
    public function it_validates_always_pending()
    {
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => '3333333333333333'
        ]);

        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'pending']);
    }

    /** @test */
    public function it_validates_example_dot_com_email()
    {
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => '1212121212121212'
        ]);

        // Test example.com email
        $payload['customer_email'] = 'user@example.com';
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'approved']);

        // Test other domain
        $payload['customer_email'] = 'user@other.com';
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'declined']);
    }

    /** @test */
    public function it_validates_even_amount()
    {
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => '1357913579135791'
        ]);

        // Test even amount
        $payload['amount'] = 100.00;
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'approved']);

        // Test odd amount
        $payload['amount'] = 101.00;
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'declined']);
    }

    /** @test */
    public function it_validates_prime_amount()
    {
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => '2468024680246802'
        ]);

        // Test prime amount
        $payload['amount'] = 13.00;
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'declined']);

        // Test non-prime amount
        $payload['amount'] = 14.00;
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'approved']);
    }

    /** @test */
    public function it_validates_eur_and_amount()
    {
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => '7777777777777777'
        ]);

        // Test EUR with amount > 500
        $payload['currency'] = 'EUR';
        $payload['amount'] = 600.00;
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'approved']);

        // Test EUR with amount <= 500
        $payload['amount'] = 400.00;
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'declined']);

        // Test non-EUR with amount > 500
        $payload['currency'] = 'USD';
        $payload['amount'] = 600.00;
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'declined']);
    }

    /** @test */
    public function it_validates_amount_ending_seven()
    {
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => '6666666666666666'
        ]);

        // Test amount ending in 7
        $payload['amount'] = 127.00;
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'declined']);

        // Test amount not ending in 7
        $payload['amount'] = 128.00;
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'approved']);
    }

    /** @test */
    public function it_validates_amount_less_than_twenty()
    {
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => '9988776655443322'
        ]);

        // Test amount <= 20
        $payload['amount'] = 20.00;
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'approved']);

        // Test amount > 20
        $payload['amount'] = 25.00;
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'declined']);
    }

    /** @test */
    public function it_validates_not_usd()
    {
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => '2233445566778899'
        ]);

        // Test USD decline
        $payload['currency'] = 'USD';
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'declined']);

        // Test non-USD approval
        $payload['currency'] = 'CAD';
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'approved']);
    }

    /** @test */
    public function it_validates_metadata_valid_key()
    {
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => '3344556677889900'
        ]);

        // Test with valid key
        $payload['metadata'] = ['valid' => 'value'];
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'approved']);

        // Test without valid key
        $payload['metadata'] = ['other' => 'value'];
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'declined']);
    }

    /** @test */
    public function it_validates_not_divisible_by_three()
    {
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => '5566778899001122'
        ]);

        // Test divisible by 3
        $payload['amount'] = 99.00;
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'declined']);

        // Test not divisible by 3
        $payload['amount'] = 100.00;
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'approved']);
    }

    /** @test */
    public function it_validates_gbp_or_aud()
    {
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => '8899001122334455'
        ]);

        // Test GBP approval
        $payload['currency'] = 'GBP';
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'approved']);

        // Test AUD approval
        $payload['currency'] = 'AUD';
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'approved']);

        // Test other currency decline
        $payload['currency'] = 'USD';
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'declined']);
    }

    /** @test */
    public function it_validates_email_no_test()
    {
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => '9900112233445566'
        ]);

        // Test email with test
        $payload['customer_email'] = 'test@domain.com';
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'declined']);

        // Test email without test
        $payload['customer_email'] = 'user@domain.com';
        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'approved']);
    }

    /** @test */
    public function it_declines_unknown_cards()
    {
        $payload = array_merge($this->getBasePayload(), [
            'card_number' => '0000000000000000'
        ]);

        $this->postJson('/api/transactions', $payload)
            ->assertStatus(201)
            ->assertJson(['status' => 'declined']);
    }


}
