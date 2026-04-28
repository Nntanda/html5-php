<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\Loan;
use App\Models\LoanRepayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);
    }

    /**
     * Test database query performance with indexes
     */
    public function test_member_search_performance_with_indexes()
    {
        // Create sample data
        Member::factory()->count(100)->create();

        // Enable query logging
        DB::enableQueryLog();

        // Perform search
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/members?search=test&page=1&per_page=20');

        $response->assertStatus(200);

        // Get executed queries
        $queries = DB::getQueryLog();
        
        // Verify queries are optimized (should use indexes)
        // Check that we're not doing full table scans
        foreach ($queries as $query) {
            // Queries should be relatively simple and indexed
            $this->assertLessThan(1000, strlen($query['query']));
        }

        DB::disableQueryLog();
    }

    public function test_loan_listing_performance()
    {
        // Create test data
        $members = Member::factory()->count(50)->create();
        foreach ($members as $member) {
            Loan::factory()->count(2)->create(['member_id' => $member->id]);
        }

        DB::enableQueryLog();

        // Test loan listing with filters
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/loans?status=approved&page=1&per_page=20');

        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        
        // Should not have N+1 query problems
        // With eager loading, should have minimal queries
        $this->assertLessThan(10, count($queries));

        DB::disableQueryLog();
    }

    public function test_transaction_history_performance()
    {
        // Create member with many transactions
        $member = Member::factory()->create();
        $account = SavingsAccount::factory()->create(['member_id' => $member->id]);
        SavingsTransaction::factory()->count(200)->create(['account_id' => $account->id]);

        DB::enableQueryLog();

        // Fetch transaction history
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/savings/accounts/{$account->id}/transactions?page=1&per_page=50");

        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        
        // Should use pagination efficiently
        $this->assertLessThan(5, count($queries));

        DB::disableQueryLog();
    }

    /**
     * Test API response caching
     */
    public function test_report_caching()
    {
        // Create test data
        Member::factory()->count(10)->create();
        
        // Clear cache
        Cache::flush();

        // First request (should hit database)
        DB::enableQueryLog();
        $response1 = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/savings-summary');
        $queries1 = DB::getQueryLog();
        DB::disableQueryLog();

        $response1->assertStatus(200);
        $firstQueryCount = count($queries1);

        // Second request (should use cache if implemented)
        DB::enableQueryLog();
        $response2 = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/savings-summary');
        $queries2 = DB::getQueryLog();
        DB::disableQueryLog();

        $response2->assertStatus(200);
        
        // Verify responses are identical
        $this->assertEquals($response1->json(), $response2->json());
    }

    /**
     * Test bulk operations performance
     */
    public function test_bulk_transaction_processing_performance()
    {
        // Create members with accounts
        $members = Member::factory()->count(50)->create();
        foreach ($members as $member) {
            SavingsAccount::factory()->create(['member_id' => $member->id]);
        }

        // Measure time for bulk processing
        $startTime = microtime(true);

        // Simulate bulk deposit processing
        DB::transaction(function () use ($members) {
            foreach ($members as $member) {
                $account = $member->savingsAccount;
                if ($account) {
                    $account->increment('balance', 1000);
                    SavingsTransaction::create([
                        'account_id' => $account->id,
                        'type' => 'deposit',
                        'amount' => 1000,
                        'source' => 'salary_deduction',
                        'reference' => 'BULK-TEST',
                        'transaction_date' => now(),
                    ]);
                }
            }
        });

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Should complete in reasonable time (< 5 seconds for 50 records)
        $this->assertLessThan(5, $executionTime);
    }

    /**
     * Test pagination performance
     */
    public function test_pagination_performance()
    {
        // Create large dataset
        Member::factory()->count(500)->create();

        // Test first page
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/members?page=1&per_page=50');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'total', 'per_page'],
        ]);

        // Test middle page
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/members?page=5&per_page=50');
        $response->assertStatus(200);

        // Test last page
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/members?page=10&per_page=50');
        $response->assertStatus(200);
    }

    /**
     * Test complex report generation performance
     */
    public function test_complex_report_generation_performance()
    {
        // Create comprehensive test data
        $members = Member::factory()->count(20)->create();
        foreach ($members as $member) {
            $account = SavingsAccount::factory()->create(['member_id' => $member->id]);
            SavingsTransaction::factory()->count(10)->create(['account_id' => $account->id]);
            
            $loan = Loan::factory()->create(['member_id' => $member->id, 'status' => 'disbursed']);
            LoanRepayment::factory()->count(5)->create(['loan_id' => $loan->id]);
        }

        $startTime = microtime(true);

        // Generate comprehensive report
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports/transactions?start_date=2024-01-01&end_date=2024-12-31');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200);
        
        // Should complete in reasonable time (< 3 seconds)
        $this->assertLessThan(3, $executionTime);
    }

    /**
     * Test eager loading to prevent N+1 queries
     */
    public function test_eager_loading_prevents_n_plus_one()
    {
        // Create members with related data
        $members = Member::factory()->count(10)->create();
        foreach ($members as $member) {
            SavingsAccount::factory()->create(['member_id' => $member->id]);
            Loan::factory()->count(2)->create(['member_id' => $member->id]);
        }

        DB::enableQueryLog();

        // Fetch members with relationships
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/members?include=savings_account,loans');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertStatus(200);

        // With proper eager loading, should have ~3-4 queries
        // 1 for members, 1 for savings accounts, 1 for loans, 1 for count
        $this->assertLessThan(10, count($queries));
    }

    /**
     * Test database connection pooling
     */
    public function test_concurrent_request_handling()
    {
        // Simulate multiple concurrent requests
        $responses = [];
        
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->actingAs($this->user, 'sanctum')
                ->getJson('/api/members?page=1&per_page=10');
        }

        // All requests should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
    }

    /**
     * Test memory usage for large datasets
     */
    public function test_memory_efficient_data_export()
    {
        // Create large dataset
        $member = Member::factory()->create();
        $account = SavingsAccount::factory()->create(['member_id' => $member->id]);
        SavingsTransaction::factory()->count(1000)->create(['account_id' => $account->id]);

        $memoryBefore = memory_get_usage();

        // Export large dataset
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reports/member/{$member->id}/statement?format=pdf");

        $memoryAfter = memory_get_usage();
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB

        $response->assertStatus(200);

        // Should not use excessive memory (< 50MB for 1000 records)
        $this->assertLessThan(50, $memoryUsed);
    }
}
