<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
        });

        Schema::create('bills', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('userid');
            $table->date('bill_date')->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->decimal('gst', 10, 2)->nullable();
            $table->decimal('cgst', 10, 2)->nullable();
            $table->decimal('sgst', 10, 2)->nullable();
            $table->decimal('igst', 10, 2)->nullable();
            $table->decimal('gross_amount', 10, 2)->nullable();
            $table->string('bill_number')->nullable();
            $table->string('merchant_name')->nullable();
            $table->unsignedInteger('coins')->default(0);
        });

        Schema::create('monthly_bills_pdf', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('userid');
            $table->unsignedInteger('month');
            $table->unsignedInteger('year');
            $table->string('bills_pdf');
            $table->timestamps();
        });

        DB::table('users')->insert([['id' => 1], ['id' => 2]]);
    }

    public function test_it_returns_monthly_spending_gst_transaction_and_coin_totals(): void
    {
        DB::table('bills')->insert([
            [
                'userid' => 1,
                'bill_date' => '2026-11-02',
                'total_amount' => 118,
                'gst' => 18,
                'cgst' => 9,
                'sgst' => 9,
                'igst' => 0,
                'gross_amount' => 100,
                'bill_number' => 'INV-1',
                'merchant_name' => 'Shop One',
                'coins' => 30,
            ],
            [
                'userid' => 1,
                'bill_date' => '2026-11-20',
                'total_amount' => 59,
                'gst' => null,
                'cgst' => 4.5,
                'sgst' => 4.5,
                'igst' => 0,
                'gross_amount' => 50,
                'bill_number' => 'INV-2',
                'merchant_name' => 'Shop Two',
                'coins' => 10,
            ],
            [
                'userid' => 1,
                'bill_date' => '2026-10-31',
                'total_amount' => 100,
                'gst' => 10,
                'cgst' => null,
                'sgst' => null,
                'igst' => null,
                'gross_amount' => null,
                'bill_number' => null,
                'merchant_name' => null,
                'coins' => 20,
            ],
            [
                'userid' => 2,
                'bill_date' => '2026-11-10',
                'total_amount' => 200,
                'gst' => 20,
                'cgst' => null,
                'sgst' => null,
                'igst' => null,
                'gross_amount' => null,
                'bill_number' => null,
                'merchant_name' => null,
                'coins' => 40,
            ],
        ]);

        $response = $this->postJson('/api/reports', [
            'userid' => 1,
            'month' => 11,
            'year' => 2026,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Report retrieved successfully')
            ->assertJsonPath('data.month', 11)
            ->assertJsonPath('data.total_spent', 177)
            ->assertJsonPath('data.total_gst', 27)
            ->assertJsonPath('data.total_transactions', 2)
            ->assertJsonPath('data.total_coins_earned', 40);

        $pdfUrl = $response->json('data.pdf');
        $csvUrl = $response->json('data.csv');
        $this->assertIsString($pdfUrl);
        $this->assertIsString($csvUrl);
        $this->assertStringContainsString('/monthly_bills_pdf/', $pdfUrl);
        $this->assertStringContainsString('/monthly_bills_csv/', $csvUrl);
        $pdfPath = ltrim(str_replace('/storage/', '', parse_url($pdfUrl, PHP_URL_PATH)), '/');
        $csvPath = ltrim(str_replace('/storage/', '', parse_url($csvUrl, PHP_URL_PATH)), '/');
        $this->assertTrue(Storage::disk('public')->exists($pdfPath));
        $this->assertTrue(Storage::disk('public')->exists($csvPath));

        $csvRows = array_map('str_getcsv', explode("\n", trim(Storage::disk('public')->get($csvPath))));
        $this->assertSame(['Bill Date', 'Bill Number', 'Merchant', 'Amount', 'CGST', 'IGST', 'Total'], $csvRows[0]);
        $this->assertSame(['2026-11-20', 'INV-2', 'Shop Two', '59.00', '4.50', '0.00', '50.00'], $csvRows[1]);
        $this->assertSame('TOTAL', $csvRows[3][0]);
    }

    public function test_it_requires_a_valid_month_and_year(): void
    {
        $this->postJson('/api/reports', ['userid' => 1, 'month' => 13, 'year' => 2026])
            ->assertOk()
            ->assertJsonPath('status', 400);
    }

    public function test_it_returns_yearly_totals_when_month_is_omitted_blank_or_null(): void
    {
        DB::table('bills')->insert([
            [
                'userid' => 1,
                'bill_date' => '2026-03-10',
                'total_amount' => 118,
                'gst' => 18,
                'coins' => 30,
            ],
            [
                'userid' => 1,
                'bill_date' => '2026-11-20',
                'total_amount' => 59,
                'gst' => 9,
                'coins' => 10,
            ],
            [
                'userid' => 1,
                'bill_date' => '2025-12-31',
                'total_amount' => 100,
                'gst' => 10,
                'coins' => 20,
            ],
        ]);

        foreach ([[], ['month' => ''], ['month' => null]] as $monthInput) {
            $response = $this->postJson('/api/reports', array_merge([
                'userid' => 1,
                'year' => 2026,
            ], $monthInput))
                ->assertOk()
                ->assertJsonMissingPath('data.month')
                ->assertJsonPath('data.total_spent', 177)
                ->assertJsonPath('data.total_gst', 27)
                ->assertJsonPath('data.total_transactions', 2)
                ->assertJsonPath('data.total_coins_earned', 40);

            $pdfUrl = $response->json('data.pdf');
            $csvUrl = $response->json('data.csv');
            $this->assertIsString($pdfUrl);
            $this->assertIsString($csvUrl);
            $pdfPath = ltrim(str_replace('/storage/', '', parse_url($pdfUrl, PHP_URL_PATH)), '/');
            $csvPath = ltrim(str_replace('/storage/', '', parse_url($csvUrl, PHP_URL_PATH)), '/');
            $this->assertStringContainsString('yearly_bills_pdf/', $pdfPath);
            $this->assertStringContainsString('yearly_bills_csv/', $csvPath);
            $csvRows = array_map('str_getcsv', explode("\n", trim(Storage::disk('public')->get($csvPath))));
            $this->assertCount(4, $csvRows);
            $this->assertSame('TOTAL', $csvRows[3][0]);
        }
    }
}
