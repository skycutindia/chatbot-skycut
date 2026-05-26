<?php

use App\Models\OperatingHour;
use App\Models\Website;
use App\Services\WidgetConfigService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        OperatingHour::query()->update([
            'opens_at' => '00:00:00',
            'closes_at' => '23:59:59',
            'is_closed' => false,
        ]);

        $service = app(WidgetConfigService::class);
        Website::query()->each(fn (Website $website) => $service->invalidate($website));
    }

    public function down(): void
    {
        //
    }
};
