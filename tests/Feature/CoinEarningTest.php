<?php

namespace Tests\Feature;

use App\Http\Controllers\apiController;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CoinEarningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'coins.earn_rate' => 0.5,
            'coins.per_bill_limit' => 30,
            'coins.user_monthly_limit' => 100,
            'coins.shared_monthly_limit' => 500,
            'coins.large_bill_gst_threshold' => 1000,
            'coins.redemption_coins_per_rupee' => 10,
        ]);

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->integer('coins')->default(0);
            $table->decimal('wallet', 12, 2)->default(0);
            $table->integer('daily_coin_count')->default(0);
            $table->date('daily_coin_reset_date')->nullable();
            $table->integer('monthly_coin_count')->default(0);
            $table->date('monthly_coin_reset_date')->nullable();
            $table->string('tier')->nullable();
            $table->integer('scan_bill')->default(0);
            $table->decimal('tax_identified', 12, 2)->default(0);
        });

        Schema::create('bills', function (Blueprint $table): void {
            $table->id();
            $table->integer('userid');
            $table->string('gstnumber')->nullable();
            $table->decimal('cgst', 10, 2)->nullable();
            $table->decimal('sgst', 10, 2)->nullable();
            $table->decimal('igst', 10, 2)->nullable();
            $table->date('bill_date')->nullable();
            $table->integer('is_process')->default(0);
            $table->integer('coins_paid')->default(0);
        });

        Schema::create('coins', function (Blueprint $table): void {
            $table->id();
            $table->string('userid', 25);
            $table->string('bill_id', 25);
            $table->string('coin', 25)->default('0');
            $table->integer('amount')->nullable();
            $table->unsignedInteger('balance_after')->nullable();
            $table->string('type', 20)->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();
            $table->index(['bill_id', 'type']);
            $table->index(['userid', 'type', 'created_at']);
        });

        Schema::create('coin_monthly_budgets', function (Blueprint $table): void {
            $table->date('month_start')->primary();
            $table->unsignedInteger('earned_coins')->default(0);
            $table->timestamps();
        });
    }

    public function test_it_earns_a_per_bill_capped_reward_once(): void
    {
        $this->createUser(1);
        $billId = $this->createBill(1, 90, 90);

        $earning = app(apiController::class)->coins_count((object) [
            'userid' => 1,
            'billid' => $billId,
        ]);

        $this->assertSame('EARNED', $earning['status']);
        $this->assertSame(90, $earning['coins_computed']);
        $this->assertSame(30, $earning['coins_earned']);
        $this->assertSame(3.0, $earning['rupees_earned']);
        $this->assertSame(3.0, $earning['total_wallet']);
        $this->assertSame(30, (int) DB::table('users')->where('id', 1)->value('coins'));
        $this->assertSame(3.0, (float) DB::table('users')->where('id', 1)->value('wallet'));
        $this->assertSame(30, (int) DB::table('coins')->where('bill_id', $billId)->value('amount'));
        $this->assertSame(1, (int) DB::table('bills')->where('id', $billId)->value('coins_paid'));

        $secondAttempt = app(apiController::class)->coins_count((object) [
            'userid' => 1,
            'billid' => $billId,
        ]);

        $this->assertSame('ALREADY_PROCESSED', $secondAttempt['status']);
        $this->assertSame(1, DB::table('coins')->where('bill_id', $billId)->count());
        $this->assertSame(30, (int) DB::table('users')->where('id', 1)->value('coins'));
        $this->assertSame(3.0, (float) DB::table('users')->where('id', 1)->value('wallet'));
    }

    public function test_it_converts_partial_coin_earnings_to_rupees(): void
    {
        $this->createUser(1);
        $billId = $this->createBill(1, 15, 15);

        $earning = app(apiController::class)->coins_count((object) [
            'userid' => 1,
            'billid' => $billId,
        ]);

        $this->assertSame(15, $earning['coins_earned']);
        $this->assertSame(1.5, $earning['rupees_earned']);
        $this->assertSame(1.5, (float) DB::table('users')->where('id', 1)->value('wallet'));
    }

    public function test_it_enforces_the_user_monthly_limit(): void
    {
        $this->createUser(1, 90);
        $this->createPriorEarning(1, 90, 90);
        $billId = $this->createBill(1, 30, 30);

        $earning = app(apiController::class)->coins_count((object) [
            'userid' => 1,
            'billid' => $billId,
        ]);

        $this->assertSame(10, $earning['coins_earned']);
        $this->assertSame('USER_MONTHLY', $earning['cap_hit']);
        $this->assertSame(100, (int) DB::table('users')->where('id', 1)->value('coins'));
    }

    public function test_it_preserves_monthly_coins_earned_before_the_ledger_was_added(): void
    {
        $monthStart = Carbon::now('Asia/Kolkata')->startOfMonth()->toDateString();
        $this->createUser(1, 90);
        DB::table('users')->where('id', 1)->update([
            'monthly_coin_count' => 90,
            'monthly_coin_reset_date' => $monthStart,
        ]);
        $billId = $this->createBill(1, 30, 30);

        $earning = app(apiController::class)->coins_count((object) [
            'userid' => 1,
            'billid' => $billId,
        ]);

        $this->assertSame(10, $earning['coins_earned']);
        $this->assertSame(100, (int) DB::table('users')->where('id', 1)->value('monthly_coin_count'));
        $this->assertSame(100, (int) DB::table('coin_monthly_budgets')->value('earned_coins'));
    }

    public function test_it_enforces_the_shared_monthly_limit_across_users(): void
    {
        $this->createUser(1);
        $this->createUser(2);
        $this->createPriorEarning(1, 495, 495);
        $billId = $this->createBill(2, 30, 30);

        $earning = app(apiController::class)->coins_count((object) [
            'userid' => 2,
            'billid' => $billId,
        ]);

        $this->assertSame(5, $earning['coins_earned']);
        $this->assertSame('SHARED_MONTHLY', $earning['cap_hit']);
        $this->assertSame(500, (int) DB::table('coin_monthly_budgets')->value('earned_coins'));
    }

    public function test_it_does_not_award_coins_to_a_bill_held_for_review(): void
    {
        $this->createUser(1);
        $billId = $this->createBill(1, 90, 90, 1);

        $earning = app(apiController::class)->coins_count((object) [
            'userid' => 1,
            'billid' => $billId,
        ]);

        $this->assertSame('PENDING_REVIEW', $earning['status']);
        $this->assertSame(0, DB::table('coins')->count());
        $this->assertSame(0, (int) DB::table('users')->where('id', 1)->value('coins'));
    }

    public function test_invalid_seller_gstin_does_not_earn_coins(): void
    {
        $this->createUser(1);
        $billId = $this->createBill(1, 90, 90);
        DB::table('bills')->where('id', $billId)->update(['gstnumber' => 'invalid']);

        $earning = app(apiController::class)->coins_count((object) [
            'userid' => 1,
            'billid' => $billId,
        ]);

        $this->assertSame('PENDING_REVIEW', $earning['status']);
        $this->assertSame(0, DB::table('coins')->count());
    }

    public function test_bill_file_upload_calls_coin_count_for_the_extracted_bill(): void
    {
        Storage::fake('public');

        $this->partialMock(apiController::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getInvoiceInfoAi')
                ->once()
                ->andReturn(42);
            $mock->shouldReceive('coins_count')
                ->once()
                ->with(Mockery::on(function ($info): bool {
                    return $info->userid === 7 && $info->billid === 42;
                }))
                ->andReturn([
                    'status' => 'EARNED',
                    'coins_earned' => 30,
                ]);
        });

        $this->postJson('/api/bill-file-upload', [
            'filestr' => base64_encode('%PDF-1.4'),
            'userid' => 7,
        ])
            ->assertOk()
            ->assertJsonPath('data.bill_id', 42)
            ->assertJsonPath('data.coin_earning.coins_earned', 30);
    }

    public function test_get_coins_only_returns_saved_coin_information(): void
    {
        $this->createUser(1, 30);
        DB::table('users')->where('id', 1)->update([
            'daily_coin_count' => 7,
            'daily_coin_reset_date' => now()->toDateString(),
            'monthly_coin_reset_date' => now()->startOfMonth()->toDateString(),
            'tier' => 'Silver',
            'scan_bill' => 12,
            'tax_identified' => 450.25,
        ]);
        $this->createBill(1, 15, 15);
        $this->createBill(1, 15, 15);

        $this->postJson('/api/get-coins', ['userid' => 1])
            ->assertOk()
            ->assertJsonPath('message', 'Coins retrieved successfully')
            ->assertJsonPath('data.total_coins', 30)
            ->assertJsonPath('data.monthly_coin_count', 30)
            ->assertJsonPath('data.daily_coin_count', 7)
            ->assertJsonPath('data.verified_scans', 12)
            ->assertJsonPath('data.total_verified_gst', 450.25)
            ->assertJsonPath('data.tier', 'Silver');

        $this->postJson('/api/get-coins', ['userid' => 1])
            ->assertOk()
            ->assertJsonPath('data.total_coins', 30)
            ->assertJsonPath('data.monthly_coin_count', 30);

        $this->assertSame(0, DB::table('coins')->where('type', 'EARN')->count());
        $this->assertSame(0, (int) DB::table('bills')->sum('coins_paid'));
        $this->assertSame(30, (int) DB::table('users')->where('id', 1)->value('monthly_coin_count'));
    }

    public function test_wallet_returns_coins_and_wallet_including_zero_values(): void
    {
        $this->createUser(1);

        $this->postJson('/api/wallet', ['userid' => 1])
            ->assertOk()
            ->assertJsonPath('data.coins', 0)
            ->assertJsonPath('data.wallet', 0);

        DB::table('users')->where('id', 1)->update([
            'coins' => 25,
            'wallet' => 12.50,
        ]);

        $this->postJson('/api/wallet', ['userid' => 1])
            ->assertOk()
            ->assertJsonPath('data.coins', 25)
            ->assertJsonPath('data.wallet', 12.5);
    }

    private function createUser(int $id, int $coins = 0): void
    {
        DB::table('users')->insert([
            'id' => $id,
            'coins' => $coins,
            'daily_coin_count' => 0,
            'monthly_coin_count' => $coins,
            'tier' => 'Bronze',
            'scan_bill' => 0,
            'tax_identified' => 0,
        ]);
    }

    private function createBill(int $userId, float $cgst, float $sgst, int $isProcess = 0): int
    {
        return (int) DB::table('bills')->insertGetId([
            'userid' => $userId,
            'gstnumber' => '29ABCDE1234F1Z5',
            'cgst' => $cgst,
            'sgst' => $sgst,
            'bill_date' => now()->toDateString(),
            'is_process' => $isProcess,
        ]);
    }

    private function createPriorEarning(int $userId, int $amount, int $balanceAfter): void
    {
        $monthStart = Carbon::now('Asia/Kolkata')->startOfMonth();
        $createdAt = $monthStart->copy()->addDay()->toDateTimeString();

        DB::table('coins')->insert([
            'userid' => (string) $userId,
            'bill_id' => 'legacy-'.$userId,
            'coin' => (string) $amount,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'type' => 'EARN',
            'created_at' => $createdAt,
        ]);

        DB::table('coin_monthly_budgets')->insert([
            'month_start' => $monthStart->toDateString(),
            'earned_coins' => $amount,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
