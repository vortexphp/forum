<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use PHPUnit\Framework\TestCase;
use Vortex\Application;
use Vortex\Crypto\Password;
use Vortex\Database\Connection;
use Vortex\Database\Schema\SchemaMigrator;
use Vortex\Http\Csrf;
use Vortex\Http\Kernel;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;

final class ForumFeatureTest extends TestCase
{
    private string $basePath;
    private string $dbPath;
    private Kernel $kernel;
    private User $member;
    private User $moderator;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = dirname(__DIR__, 2);
        $this->dbPath = $this->basePath . '/storage/testing.sqlite';

        if (is_file($this->dbPath)) {
            unlink($this->dbPath);
        }

        putenv('DB_DRIVER=sqlite');
        putenv('DB_DATABASE=' . $this->dbPath);
        putenv('APP_ENV=testing');
        putenv('APP_DEBUG=1');

        $app = Application::boot($this->basePath);
        $container = $app->container();
        $this->kernel = new Kernel($container);

        $migrator = new SchemaMigrator($this->basePath, $container->make(Connection::class));
        $migrator->up();

        $this->category = Category::create([
            'name' => 'General',
            'slug' => 'general',
            'description' => 'General discussion',
            'sort_order' => 1,
            'is_locked' => 0,
        ]);

        $this->member = User::create([
            'name' => 'Member',
            'email' => 'member@example.test',
            'password' => Password::hash('password123'),
            'role' => 'member',
        ]);

        $this->moderator = User::create([
            'name' => 'Moderator',
            'email' => 'moderator@example.test',
            'password' => Password::hash('password123'),
            'role' => 'moderator',
        ]);

        Session::start();
    }

    public function test_guest_cannot_open_new_thread_form(): void
    {
        $response = $this->get('/forum/general/new');

        self::assertSame(302, $response->httpStatus());
        self::assertSame('/login?next=%2Fforum%2Fgeneral%2Fnew', $response->headers()['Location']);
    }

    public function test_authenticated_user_can_create_thread_and_first_post(): void
    {
        $this->actingAs($this->member);

        $response = $this->post('/forum/general/new', [
            'title' => 'First thread',
            'body' => 'Hello from thread body.',
        ]);

        self::assertSame(302, $response->httpStatus());
        $location = (string) ($response->headers()['Location'] ?? '');
        self::assertStringStartsWith('/forum/general/first-thread', $location);

        $thread = Thread::findByCategoryAndSlug((int) $this->category->id, 'first-thread');
        self::assertNotNull($thread);

        $posts = Post::query()->where('thread_id', (int) $thread->id)->get();
        self::assertCount(1, $posts);
    }

    public function test_user_can_reply_and_edit_own_post(): void
    {
        $thread = $this->createThreadBy($this->member, 'Support topic', 'Initial body');
        $this->actingAs($this->member);

        $reply = $this->post('/forum/general/support-topic/reply', ['body' => 'My reply']);
        self::assertSame(302, $reply->httpStatus());

        $postRows = Post::query()->where('thread_id', (int) $thread->id)->orderByDesc('id')->get();
        $last = $postRows[0] ?? null;
        self::assertNotNull($last);

        $editView = $this->get('/forum/general/support-topic/posts/' . (int) $last->id . '/edit');
        self::assertSame(200, $editView->httpStatus());

        $update = $this->post('/forum/general/support-topic/posts/' . (int) $last->id . '/edit', [
            'body' => 'Edited reply body',
        ]);
        self::assertSame(302, $update->httpStatus());

        $updated = Post::find((int) $last->id);
        self::assertNotNull($updated);
        self::assertSame('Edited reply body', (string) $updated->body);
    }

    public function test_non_moderator_cannot_access_moderation_route(): void
    {
        $thread = $this->createThreadBy($this->member, 'Rules', 'Initial');
        $this->actingAs($this->member);

        $response = $this->post('/forum/general/rules/moderate/lock', []);
        self::assertSame(403, $response->httpStatus());

        $fresh = Thread::find((int) $thread->id);
        self::assertNotNull($fresh);
        self::assertSame(0, (int) $fresh->is_locked);
    }

    public function test_moderator_can_lock_thread(): void
    {
        $thread = $this->createThreadBy($this->member, 'Announcements', 'Initial');
        $this->actingAs($this->moderator);

        $response = $this->post('/forum/general/announcements/moderate/lock', []);
        self::assertSame(302, $response->httpStatus());

        $fresh = Thread::find((int) $thread->id);
        self::assertNotNull($fresh);
        self::assertSame(1, (int) $fresh->is_locked);
    }

    private function actingAs(User $user): void
    {
        Session::start();
        Session::put('auth_user_id', (int) $user->id);
    }

    private function get(string $path): Response
    {
        return $this->kernel->handle(Request::make('GET', $path));
    }

    /**
     * @param array<string, mixed> $body
     */
    private function post(string $path, array $body): Response
    {
        Session::start();
        $body['_csrf'] = Csrf::token();

        return $this->kernel->handle(Request::make('POST', $path, [], $body, [], ['REMOTE_ADDR' => '127.0.0.1']));
    }

    private function createThreadBy(User $author, string $title, string $body): Thread
    {
        $slug = strtolower(str_replace(' ', '-', $title));
        $thread = Thread::create([
            'category_id' => (int) $this->category->id,
            'user_id' => (int) $author->id,
            'title' => $title,
            'slug' => $slug,
            'body' => $body,
            'is_locked' => 0,
            'is_pinned' => 0,
            'reply_count' => 0,
            'last_post_at' => gmdate('Y-m-d H:i:s'),
        ]);

        Post::create([
            'thread_id' => (int) $thread->id,
            'user_id' => (int) $author->id,
            'body' => $body,
            'is_edited' => 0,
            'edited_at' => null,
        ]);

        return $thread;
    }
}
