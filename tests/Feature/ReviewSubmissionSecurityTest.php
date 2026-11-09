<?php

namespace Tests\Feature;

use App\Models\Back\Marketing\Review;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReviewSubmissionSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Schema::create('pages', function (Blueprint $table) { $table->id(); });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->boolean('status')->default(true);
        });
        DB::table('products')->insert(['id' => 1, 'status' => true]);
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            foreach (['product_id', 'order_id', 'user_id', 'stars', 'sort_order'] as $field) {
                $table->integer($field)->default(0);
            }
            foreach (['lang', 'fname', 'lname', 'email', 'avatar', 'message'] as $field) {
                $table->text($field)->nullable();
            }
            $table->boolean('featured')->default(false);
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }

    public function test_guest_review_cannot_choose_moderation_or_identity_fields(): void
    {
        $this->post(route('komentar.proizvoda'), array_merge($this->payload(), [
            'status' => 'on', 'featured' => 'on', 'sort_order' => -100, 'user_id' => 50,
            'order_id' => 900, 'avatar' => 'https://attacker.example.test/tracker',
        ]))->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'product_id' => 1, 'status' => false, 'featured' => false, 'sort_order' => 0,
            'user_id' => 0, 'order_id' => 0, 'avatar' => 'media/avatar.jpg',
        ]);
    }

    public function test_stored_review_cannot_break_out_of_the_administrative_textarea(): void
    {
        $message = '</textarea><script id="review-xss">window.audit=1</script><textarea>';
        $this->post(route('komentar.proizvoda'), array_merge($this->payload(), ['message' => $message]))
            ->assertRedirect();
        $review = Review::firstOrFail();
        $this->assertSame($message, $review->message);

        $template = file_get_contents(resource_path('views/back/marketing/review/edit.blade.php'));
        preg_match('/<textarea id="message-editor".*?<\/textarea>/s', $template, $matches);
        $html = Blade::render($matches[0], compact('review'));
        $document = new \DOMDocument();
        @$document->loadHTML($html);
        $this->assertNull($document->getElementById('review-xss'));
        $this->assertStringContainsString($message, $document->getElementsByTagName('textarea')->item(0)->textContent);
    }

    public function test_staff_can_still_approve_a_pending_review(): void
    {
        $this->post(route('komentar.proizvoda'), $this->payload())->assertRedirect();
        $review = Review::firstOrFail();
        $review->validateRequest(new Request(array_merge($this->payload(), [
            'status' => 'on', 'featured' => 'on', 'sort_order' => 2,
        ])))->edit();
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'status' => true, 'featured' => true, 'sort_order' => 2]);
    }

    #[DataProvider('invalidReviews')]
    public function test_guest_review_validates_product_rating_and_language(array $changes): void
    {
        $this->postJson(route('komentar.proizvoda'), array_merge($this->payload(), $changes))
            ->assertUnprocessable();
        $this->assertDatabaseCount('reviews', 0);
    }

    public static function invalidReviews(): array
    {
        return [[['product_id' => '999']], [['stars' => '6']], [['stars' => '-1']], [['lang' => 'zz']]];
    }

    public function test_repeated_review_submissions_are_rate_limited(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post(route('komentar.proizvoda'), $this->payload())->assertRedirect();
        }
        $this->post(route('komentar.proizvoda'), $this->payload())->assertStatus(429);
        $this->assertDatabaseCount('reviews', 5);
    }

    public function test_repeated_reviews_do_not_award_points_twice_for_one_user_and_product(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->boolean('status')->default(true);
        });
        Schema::create('loyalty', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('reference_id');
            $table->string('target');
            $table->integer('earned');
            $table->integer('spend');
            $table->timestamps();
        });
        $user = User::create(['name' => 'Customer', 'email' => 'customer@example.test', 'password' => bcrypt('test-password')]);
        DB::table('user_details')->insert(['user_id' => $user->id, 'status' => true]);
        $this->actingAs($user);
        $this->post(route('komentar.proizvoda'), $this->payload())->assertRedirect();
        $this->post(route('komentar.proizvoda'), $this->payload())->assertRedirect();
        $this->assertDatabaseCount('reviews', 2);
        $this->assertDatabaseCount('loyalty', 1);
        $this->assertDatabaseHas('loyalty', ['user_id' => $user->id, 'reference_id' => 1, 'target' => 'product_review']);
    }

    private function payload(): array
    {
        return ['name' => 'Test Customer', 'email' => 'customer@example.test', 'stars' => '5',
            'product_id' => '1', 'lang' => 'hr', 'message' => 'A normal review.'];
    }
}
