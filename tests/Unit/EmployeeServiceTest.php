<?php

namespace Tests\Unit;

use App\Services\HikCentral\EmployeeService;
use App\Services\HikCentral\HikCentralClient;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class EmployeeServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('hikcentral_person_id')->unique();
            $table->string('employee_code')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('organization_code')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');

        parent::tearDown();
    }

    public function test_it_fetches_and_saves_all_employee_pages(): void
    {
        $firstPage = array_map(function ($number) {
            return ['personId' => 'person-'.$number, 'personName' => 'Employee '.$number];
        }, range(1, 100));
        $secondPage = [[
            'personId' => 'person-101',
            'personName' => 'Employee 101',
        ]];

        $client = Mockery::mock(HikCentralClient::class);
        $client->shouldReceive('post')
            ->once()
            ->ordered()
            ->with('/artemis/api/resource/v1/person/personList', ['pageNo' => 1, 'pageSize' => 100])
            ->andReturn(['data' => ['list' => $firstPage, 'total' => 101]]);
        $client->shouldReceive('post')
            ->once()
            ->ordered()
            ->with('/artemis/api/resource/v1/person/personList', ['pageNo' => 2, 'pageSize' => 100])
            ->andReturn(['data' => ['list' => $secondPage, 'total' => 101]]);

        $processed = (new EmployeeService($client))->sync();

        $this->assertSame(101, $processed);
        $this->assertSame(101, DB::table('employees')->count());
        $this->assertDatabaseHas('employees', [
            'hikcentral_person_id' => 'person-101',
            'name' => 'Employee 101',
        ]);
    }

    public function test_it_stops_after_one_page_when_all_employees_fit(): void
    {
        $employees = array_map(function ($number) {
            return ['personId' => 'person-'.$number];
        }, range(1, 100));

        $client = Mockery::mock(HikCentralClient::class);
        $client->shouldReceive('post')
            ->once()
            ->with('/artemis/api/resource/v1/person/personList', ['pageNo' => 1, 'pageSize' => 100])
            ->andReturn(['data' => ['list' => $employees, 'total' => 100]]);

        $processed = (new EmployeeService($client))->sync();

        $this->assertSame(100, $processed);
        $this->assertSame(100, DB::table('employees')->count());
    }
}
