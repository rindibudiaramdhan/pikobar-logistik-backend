<?php

namespace Tests\Feature;

use App\Agency;
use App\Applicant;
use App\MasterFaskes;
use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;

class LogisticRequestTest extends TestCase
{
    use WithFaker;
    // use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->masterFaskes = factory(MasterFaskes::class)->create();
        $this->agency = factory(Agency::class)->create([
            'master_faskes_id' => $this->masterFaskes->id,
            'agency_type' => $this->masterFaskes->id_tipe_faskes,
        ]);
        $this->applicant = factory(Applicant::class)->create(['agency_id' => $this->agency->id]);
    }

    public function test_get_logistic_request_no_auth()
    {
        $response = $this->get('/api/v1/logistic-request');
        $response->assertUnauthorized();
    }

    public function test_get_logistic_request_by_agency_id_no_auth()
    {
        $agencyId = $this->agency->id;
        $response = $this->get('/api/v1/logistic-request/' . $agencyId);
        $response->assertUnauthorized();
    }

    public function test_get_logistic_request()
    {
        $admin = factory(User::class)->create();
        $response = $this->actingAs($admin, 'api')->get('/api/v1/logistic-request');
        $response->assertSuccessful();
    }

    public function test_get_logistic_request_by_agency_id()
    {
        $admin = factory(User::class)->create();
        $agency = Agency::first();
        $agencyId = $agency->id;
        $response = $this->actingAs($admin, 'api')->get('/api/v1/logistic-request/' . $agencyId);
        $response->assertSuccessful();
    }

    public function test_get_logistic_request_by_agency_id_not_admin()
    {
        $notAdmin = factory(User::class)->create(['roles' => 'dinkeskota']);

        $agency = Agency::first();
        $agencyId = $agency->id;
        $response = $this->actingAs($notAdmin, 'api')->get('/api/v1/logistic-request/' . $agencyId);
        $response->assertUnauthorized();
    }
}
