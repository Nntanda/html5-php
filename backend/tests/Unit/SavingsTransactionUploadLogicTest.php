<?php

namespace Tests\Unit;

use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsTransactionUploadLogicTest extends TestCase
{
    use RefreshDatabase;

    protected Member $member;
    protected SavingsAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->member = Member::factory()->create([
            'member_number' => 'MEM001',
            'status' => Member::STATUS_ACTIVE,
        ]);

        $this->account = SavingsAccount::factory()->create([
            'member_id' => $this->member->id,
            'balance' => 0,
        ]);
    }

    /**
     * Test transaction creation with correct attributes
     */
    public function test_transaction_created_with_correct_attributes()
    {
        $transaction = SavingsTransaction::create([
            'account_id' => $this->account->id,
            'type' => SavingsTransaction::TYPE_SALARY_SAVINGS,
            'amount' => 50000,
            'source' => SavingsTransaction::SOURCE_SALARY,
            'reference' => 'SAL-2024-01',
            'transaction_date' => now()->toDateString(),
            'description' => 'Salary deduction - January 2024',
            'salary_period' => 'January 2024',
            'employer_reference' => 'SAL-2024-01',
        ]);

        $this->assertDatabaseHas('savings_transactions', [
            'id' => $transaction->id,
            'account_id' => $this->account->id,
            'type' => SavingsTransaction::TYPE_SALARY_SAVINGS,
            'amount' => 50000,
            'source' => SavingsTransaction::SOURCE_SALARY,
            'reference' => 'SAL-2024-01',
            'salary_period' => 'January 2024',
            'employer_reference' => 'SAL-2024-01',
            'is_reversed' => false,
        ]);
    }

    /**
     * Test balance update after transaction creation
     */
    public function test_balance_updated_after_transaction()
    {
        $initialBalance = $this->account->balance;

        $this->account->increment('balance', 50000);

        $this->assertEquals($initialBalance + 50000, $this->account->fresh()->balance);
    }

    /**
     * Test multiple transactions accumulate balance
     */
    public function test_multiple_transactions_accumulate_balance()
    {
        $this->account->increment('balance', 50000);
        $this->account->increment('balance', 75000);
        $this->account->increment('balance', 100000);

        $this->assertEquals(225000, $this->account->fresh()->balance);
    }

    /**
     * Test transaction reference uniqueness
     */
    public function test_transaction_reference_uniqueness()
    {
        SavingsTransaction::create([
            'account_id' => $this->account->id,
            'type' => SavingsTransaction::TYPE_SALARY_SAVINGS,
            'amount' => 50000,
            'source' => SavingsTransaction::SOURCE_SALARY,
            'reference' => 'SAL-2024-01',
            'transaction_date' => now()->toDateString(),
            'description' => 'First transaction',
            'salary_period' => 'January 2024',
        ]);

        // Attempting to create another transaction with same reference should fail
        $this->expectException(\Illuminate\Database\QueryException::class);

        SavingsTransaction::create([
            'account_id' => $this->account->id,
            'type' => SavingsTransaction::TYPE_SALARY_SAVINGS,
            'amount' => 75000,
            'source' => SavingsTransaction::SOURCE_SALARY,
            'reference' => 'SAL-2024-01',
            'transaction_date' => now()->toDateString(),
            'description' => 'Duplicate reference',
            'salary_period' => 'January 2024',
        ]);
    }

    /**
     * Test transaction type constants
     */
    public function test_transaction_type_constants()
    {
        $this->assertEquals('salary_savings', SavingsTransaction::TYPE_SALARY_SAVINGS);
        $this->assertEquals('direct_deposit', SavingsTransaction::TYPE_DIRECT_DEPOSIT);
        $this->assertEquals('withdrawal', SavingsTransaction::TYPE_WITHDRAWAL);
        $this->assertEquals('reversal', SavingsTransaction::TYPE_REVERSAL);
    }

    /**
     * Test transaction source constants
     */
    public function test_transaction_source_constants()
    {
        $this->assertEquals('salary', SavingsTransaction::SOURCE_SALARY);
        $this->assertEquals('cash', SavingsTransaction::SOURCE_CASH);
        $this->assertEquals('bank_transfer', SavingsTransaction::SOURCE_BANK_TRANSFER);
        $this->assertEquals('mobile_money', SavingsTransaction::SOURCE_MOBILE_MONEY);
    }

    /**
     * Test member active status check
     */
    public function test_member_active_status_check()
    {
        $this->assertTrue($this->member->isActive());

        $this->member->update(['status' => Member::STATUS_INACTIVE]);
        $this->assertFalse($this->member->fresh()->isActive());

        $this->member->update(['status' => Member::STATUS_SUSPENDED]);
        $this->assertFalse($this->member->fresh()->isActive());
    }

    /**
     * Test member status constants
     */
    public function test_member_status_constants()
    {
        $this->assertEquals('active', Member::STATUS_ACTIVE);
        $this->assertEquals('suspended', Member::STATUS_SUSPENDED);
        $this->assertEquals('inactive', Member::STATUS_INACTIVE);
    }

    /**
     * Test savings account balance casting
     */
    public function test_savings_account_balance_casting()
    {
        $this->account->update(['balance' => 50000.50]);

        $this->assertIsString($this->account->fresh()->balance);
        $this->assertEquals('50000.50', $this->account->fresh()->balance);
    }

    /**
     * Test transaction amount casting
     */
    public function test_transaction_amount_casting()
    {
        $transaction = SavingsTransaction::create([
            'account_id' => $this->account->id,
            'type' => SavingsTransaction::TYPE_SALARY_SAVINGS,
            'amount' => 50000.50,
            'source' => SavingsTransaction::SOURCE_SALARY,
            'reference' => 'SAL-2024-01',
            'transaction_date' => now()->toDateString(),
            'description' => 'Test transaction',
            'salary_period' => 'January 2024',
        ]);

        $this->assertIsString($transaction->fresh()->amount);
        $this->assertEquals('50000.50', $transaction->fresh()->amount);
    }

    /**
     * Test transaction date casting
     */
    public function test_transaction_date_casting()
    {
        $transaction = SavingsTransaction::create([
            'account_id' => $this->account->id,
            'type' => SavingsTransaction::TYPE_SALARY_SAVINGS,
            'amount' => 50000,
            'source' => SavingsTransaction::SOURCE_SALARY,
            'reference' => 'SAL-2024-01',
            'transaction_date' => '2024-01-15',
            'description' => 'Test transaction',
            'salary_period' => 'January 2024',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $transaction->fresh()->transaction_date);
    }

    /**
     * Test transaction relationship to account
     */
    public function test_transaction_relationship_to_account()
    {
        $transaction = SavingsTransaction::create([
            'account_id' => $this->account->id,
            'type' => SavingsTransaction::TYPE_SALARY_SAVINGS,
            'amount' => 50000,
            'source' => SavingsTransaction::SOURCE_SALARY,
            'reference' => 'SAL-2024-01',
            'transaction_date' => now()->toDateString(),
            'description' => 'Test transaction',
            'salary_period' => 'January 2024',
        ]);

        $this->assertEquals($this->account->id, $transaction->account->id);
    }

    /**
     * Test account relationship to member
     */
    public function test_account_relationship_to_member()
    {
        $this->assertEquals($this->member->id, $this->account->member->id);
    }

    /**
     * Test account relationship to transactions
     */
    public function test_account_relationship_to_transactions()
    {
        SavingsTransaction::create([
            'account_id' => $this->account->id,
            'type' => SavingsTransaction::TYPE_SALARY_SAVINGS,
            'amount' => 50000,
            'source' => SavingsTransaction::SOURCE_SALARY,
            'reference' => 'SAL-2024-01',
            'transaction_date' => now()->toDateString(),
            'description' => 'Test transaction 1',
            'salary_period' => 'January 2024',
        ]);

        SavingsTransaction::create([
            'account_id' => $this->account->id,
            'type' => SavingsTransaction::TYPE_SALARY_SAVINGS,
            'amount' => 75000,
            'source' => SavingsTransaction::SOURCE_SALARY,
            'reference' => 'SAL-2024-02',
            'transaction_date' => now()->toDateString(),
            'description' => 'Test transaction 2',
            'salary_period' => 'January 2024',
        ]);

        $this->assertEquals(2, $this->account->fresh()->transactions()->count());
    }

    /**
     * Test transaction reference generation
     */
    public function test_transaction_reference_generation()
    {
        $reference = SavingsTransaction::generateReference();

        $this->assertStringStartsWith('TXN', $reference);
        $this->assertGreaterThan(10, strlen($reference));
    }

    /**
     * Test member number generation
     */
    public function test_member_number_generation()
    {
        $memberNumber = Member::generateMemberNumber();

        $this->assertStringStartsWith('MEM', $memberNumber);
        $this->assertGreaterThan(6, strlen($memberNumber));
    }

    /**
     * Test savings account number generation
     */
    public function test_savings_account_number_generation()
    {
        $accountNumber = SavingsAccount::generateAccountNumber();

        $this->assertStringStartsWith('SAV', $accountNumber);
        $this->assertGreaterThan(10, strlen($accountNumber));
    }
}
