<?php

namespace Tests\Feature;

use App\Mail\ContractTerminationConfirmation;
use App\Mail\ContractTerminationMessage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContractTerminationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'mail.contract_termination_to' => 'prodaja@example.test',
            'services.recaptcha.bypass_local' => true,
            'services.recaptcha.sitekey' => null,
            'services.recaptcha.secret' => null,
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('page');
            $table->timestamps();
        });
        Schema::create('page_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('page_id')->index();
            $table->string('lang', 2)->default('hr');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();
        });

        require_once database_path('migrations/2026_09_10_090000_create_contract_terminations_table.php');
        (new \CreateContractTerminationsTable())->up();
    }

    public function test_form_is_available(): void
    {
        $this->get(route('contract-termination'))
            ->assertOk()
            ->assertSee('Jednostrani raskid ugovora')
            ->assertSee('Top Tim d.o.o.');
    }

    public function test_valid_statement_is_stored_and_both_emails_are_sent(): void
    {
        Mail::fake();

        $response = $this->post(route('contract-termination.send'), $this->validPayload());

        $response->assertRedirect(route('contract-termination'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('contract_terminations', [
            'order_number' => 'TT-12345',
            'email' => 'ana@example.test',
            'status' => 'received',
        ]);
        Mail::assertSent(ContractTerminationMessage::class, 1);
        Mail::assertSent(ContractTerminationConfirmation::class, 1);
    }

    public function test_required_fields_have_croatian_validation_messages(): void
    {
        Mail::fake();

        $this->from(route('contract-termination'))
            ->post(route('contract-termination.send'), [])
            ->assertRedirect(route('contract-termination'))
            ->assertSessionHasErrors([
                'full_name' => 'Upišite ime i prezime.',
                'email' => 'Upišite e-mail adresu.',
                'address' => 'Upišite ulicu i kućni broj.',
                'postal_code' => 'Upišite poštanski broj.',
                'city' => 'Upišite mjesto.',
                'country' => 'Upišite državu.',
                'order_number' => 'Upišite broj narudžbe ili računa.',
                'items' => 'Navedite artikle na koje se raskid odnosi.',
                'statement' => 'Za slanje je potrebno potvrditi izjavu o raskidu ugovora.',
            ]);

        $this->assertDatabaseCount('contract_terminations', 0);
        Mail::assertNothingSent();
    }

    private function validPayload(): array
    {
        return [
            'full_name' => 'Ana Anić',
            'email' => 'ana@example.test',
            'phone' => '091 123 4567',
            'address' => 'Ilica 1',
            'postal_code' => '10000',
            'city' => 'Zagreb',
            'country' => 'HR',
            'order_number' => 'TT-12345',
            'order_date' => now()->subDays(5)->toDateString(),
            'received_date' => now()->subDays(2)->toDateString(),
            'items' => 'Dres komplet Promo, veličina L, 1 komad',
            'iban' => '',
            'statement' => '1',
            'website' => '',
            'contract_termination_form' => '1',
        ];
    }
}
