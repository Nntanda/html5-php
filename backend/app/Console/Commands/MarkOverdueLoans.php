<?php

namespace App\Console\Commands;

use App\Services\LoanService;
use Illuminate\Console\Command;

class MarkOverdueLoans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loans:mark-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark loans as overdue based on payment schedule';

    protected LoanService $loanService;

    public function __construct(LoanService $loanService)
    {
        parent::__construct();
        $this->loanService = $loanService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for overdue loans...');

        $result = $this->loanService->markOverdueLoans();

        $this->info("Checked {$result['total_checked']} loans");
        $this->info("Marked {$result['marked_count']} loans as overdue");

        if ($result['marked_count'] > 0) {
            $this->warn("⚠️  {$result['marked_count']} loans have been marked as overdue!");
        } else {
            $this->info("✅ No overdue loans found");
        }

        return Command::SUCCESS;
    }
}