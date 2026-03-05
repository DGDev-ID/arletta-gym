<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmergencyContactToUserDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->string('emergency_name')->nullable()->after('phone_number');
            $table->string('emergency_phone')->nullable()->after('emergency_name');
            $table->string('emergency_relation')->nullable()->after('emergency_phone');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_details', 'emergency_name')) {
                $table->dropColumn(['emergency_name', 'emergency_phone', 'emergency_relation']);
            }
        });
    }
}
