<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * App\Models\OtherApi declares aychindodata_key and aychindodata_secret in
     * its $fillable array, but the columns were never added to other_api.
     * Any code writing those attributes would fail with an unknown-column
     * error. Nullable longtext to match the other API credential columns on
     * this table, so this is safe to roll forward and back.
     */
    public function up(): void
    {
        Schema::table('other_api', function (Blueprint $table) {
            if (!Schema::hasColumn('other_api', 'aychindodata_key')) {
                $table->longText('aychindodata_key')->nullable()->after('resopot_apikey');
            }

            if (!Schema::hasColumn('other_api', 'aychindodata_secret')) {
                $table->longText('aychindodata_secret')->nullable()->after('aychindodata_key');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('other_api', function (Blueprint $table) {
            if (Schema::hasColumn('other_api', 'aychindodata_secret')) {
                $table->dropColumn('aychindodata_secret');
            }

            if (Schema::hasColumn('other_api', 'aychindodata_key')) {
                $table->dropColumn('aychindodata_key');
            }
        });
    }
};
