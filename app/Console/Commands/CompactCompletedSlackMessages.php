<?php

namespace App\Console\Commands;

use App\Models\Mietvorgang;
use App\Models\Vermietvorgang;
use App\Services\SlackVorgangSync;
use Illuminate\Console\Command;

class CompactCompletedSlackMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slack:compact-completed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ersetzt Slack-Nachrichten abgeschlossener Miet-/Vermietvorgänge 48h nach Abschluss durch eine einzeilige Kurzfassung.';

    public function __construct(private SlackVorgangSync $slack)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        Mietvorgang::whereNotNull('slack_message_ts')
            ->whereNull('slack_compacted_at')
            ->with('supplier')
            ->get()
            ->each(fn (Mietvorgang $mietvorgang) => $this->slack->compactIfDue($mietvorgang));

        Vermietvorgang::whereNotNull('slack_message_ts')
            ->whereNull('slack_compacted_at')
            ->with('mieter')
            ->get()
            ->each(fn (Vermietvorgang $vermietvorgang) => $this->slack->compactIfDue($vermietvorgang));

        return self::SUCCESS;
    }
}
