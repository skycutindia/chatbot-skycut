<?php

namespace App\Console\Commands;

use App\Services\ChatAutomationService;
use Illuminate\Console\Command;

class CloseInactiveChatsCommand extends Command
{
    protected $signature = 'chat:close-inactive';

    protected $description = 'Run inactive-chat automation rules';

    public function handle(ChatAutomationService $automation): int
    {
        $closed = $automation->closeInactiveConversations();
        $this->info("Closed {$closed} inactive conversation(s).");

        return self::SUCCESS;
    }
}
