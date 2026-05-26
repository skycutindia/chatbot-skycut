<?php

namespace App\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case ProposalSent = 'proposal_sent';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::Qualified => 'Qualified',
            self::ProposalSent => 'Proposal Sent',
            self::Won => 'Won',
            self::Lost => 'Lost',
        };
    }

    /** @return list<string> */
    public static function pipeline(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
