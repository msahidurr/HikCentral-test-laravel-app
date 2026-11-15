<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_events', function (Blueprint $table) {
            $table->id();
            $table->string('hikcentral_event_id', 100)->unique();
            $table->string('person_id', 64)->nullable()->index();
            $table->string('employee_code', 64)->nullable()->index();
            $table->string('person_name')->nullable();
            $table->string('event_type', 32)->nullable();
            $table->dateTime('event_time')->nullable()->index();
            $table->string('door_index_code', 64)->nullable();
            $table->string('door_name')->nullable();
            $table->string('direction', 10)->nullable()->after('event_type')->index();
            $table->json('raw_data');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_events');
    }
};
