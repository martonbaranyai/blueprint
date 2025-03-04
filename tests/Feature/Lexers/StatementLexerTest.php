<?php

namespace Tests\Feature\Lexers;

use Blueprint\Lexers\StatementLexer;
use Blueprint\Models\Statements\DispatchStatement;
use Blueprint\Models\Statements\EloquentStatement;
use Blueprint\Models\Statements\FireStatement;
use Blueprint\Models\Statements\InertiaStatement;
use Blueprint\Models\Statements\QueryStatement;
use Blueprint\Models\Statements\RedirectStatement;
use Blueprint\Models\Statements\RenderStatement;
use Blueprint\Models\Statements\ResourceStatement;
use Blueprint\Models\Statements\RespondStatement;
use Blueprint\Models\Statements\SendStatement;
use Blueprint\Models\Statements\SessionStatement;
use Blueprint\Models\Statements\ValidateStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see StatementLexer
 */
final class StatementLexerTest extends TestCase
{
    /**
     * @var StatementLexer
     */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new StatementLexer;
    }

    #[Test]
    public function it_returns_nothing_without_statements_token(): void
    {
        $this->assertEquals([], $this->subject->analyze([]));
    }

    #[Test]
    public function it_returns_a_render_statement(): void
    {
        $tokens = [
            'render' => 'post.index',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(RenderStatement::class, $actual[0]);

        $this->assertEquals('post.index', $actual[0]->view());
        $this->assertSame([], $actual[0]->data());
    }

    #[Test]
    public function it_returns_a_render_statement_with_data(): void
    {
        $tokens = [
            'render' => 'post.index with:foo,bar,baz',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(RenderStatement::class, $actual[0]);

        $this->assertEquals('post.index', $actual[0]->view());
        $this->assertEquals(['foo', 'bar', 'baz'], $actual[0]->data());
    }

    #[Test]
    public function it_returns_an_inertia_statement_with_data(): void
    {
        $tokens = [
            'inertia' => 'Post/Index with:foo,bar,baz',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(InertiaStatement::class, $actual[0]);

        $this->assertEquals('Post/Index', $actual[0]->view());
        $this->assertEquals(['foo', 'bar', 'baz'], $actual[0]->data());
    }

    #[Test]
    public function it_returns_an_event_statement(): void
    {
        $tokens = [
            'fire' => 'SomeEvent',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(FireStatement::class, $actual[0]);

        $this->assertEquals('SomeEvent', $actual[0]->event());
        $this->assertSame([], $actual[0]->data());
    }

    #[Test]
    public function it_returns_an_event_statement_with_data(): void
    {
        $tokens = [
            'fire' => 'some.event with:foo, bar,  baz',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(FireStatement::class, $actual[0]);

        $this->assertEquals('some.event', $actual[0]->event());
        $this->assertEquals(['foo', 'bar', 'baz'], $actual[0]->data());
    }

    #[Test]
    public function it_returns_a_dispatch_statement(): void
    {
        $tokens = [
            'dispatch' => 'ProcessPodcast',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(DispatchStatement::class, $actual[0]);

        $this->assertEquals('ProcessPodcast', $actual[0]->job());
        $this->assertSame([], $actual[0]->data());
    }

    #[Test]
    public function it_returns_a_dispatch_statement_with_data(): void
    {
        $tokens = [
            'dispatch' => 'ProcessPodcast with:foo, bar,        baz',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(DispatchStatement::class, $actual[0]);

        $this->assertEquals('ProcessPodcast', $actual[0]->job());
        $this->assertEquals(['foo', 'bar', 'baz'], $actual[0]->data());
    }

    #[Test]
    public function it_returns_a_send_statement(): void
    {
        $tokens = [
            'send' => 'ReviewPost',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(SendStatement::class, $actual[0]);

        $this->assertEquals('ReviewPost', $actual[0]->mail());
        $this->assertNull($actual[0]->to());
        $this->assertSame([], $actual[0]->data());
        $this->assertEquals('emails.review-post', $actual[0]->view());
        $this->assertEquals(SendStatement::TYPE_MAIL, $actual[0]->type());
    }

    #[Test]
    public function it_returns_a_send_statement_to_only(): void
    {
        $tokens = [
            'send' => 'ReviewPost to:post.author',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(SendStatement::class, $actual[0]);

        $this->assertEquals('ReviewPost', $actual[0]->mail());
        $this->assertEquals('post.author', $actual[0]->to());
        $this->assertSame([], $actual[0]->data());
        $this->assertEquals('emails.review-post', $actual[0]->view());
        $this->assertEquals(SendStatement::TYPE_MAIL, $actual[0]->type());
    }

    #[Test]
    public function it_returns_a_send_statement_with_only(): void
    {
        $tokens = [
            'send' => 'ReviewPost with:foo, bar, baz',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(SendStatement::class, $actual[0]);

        $this->assertEquals('ReviewPost', $actual[0]->mail());
        $this->assertNull($actual[0]->to());
        $this->assertEquals(['foo', 'bar', 'baz'], $actual[0]->data());
        $this->assertEquals('emails.review-post', $actual[0]->view());
        $this->assertEquals(SendStatement::TYPE_MAIL, $actual[0]->type());
    }

    #[Test]
    public function it_returns_a_send_statement_to_and_with(): void
    {
        $tokens = [
            'send' => 'ReviewPost to:post.author with:foo, bar, baz',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(SendStatement::class, $actual[0]);

        $this->assertEquals('ReviewPost', $actual[0]->mail());
        $this->assertEquals('post.author', $actual[0]->to());
        $this->assertEquals(['foo', 'bar', 'baz'], $actual[0]->data());
        $this->assertEquals('emails.review-post', $actual[0]->view());
        $this->assertEquals(SendStatement::TYPE_MAIL, $actual[0]->type());
    }

    #[Test]
    public function it_returns_a_send_statement_to_with_and_view(): void
    {
        $tokens = [
            'send' => 'ReviewPost to:post.author with:foo, bar, baz view:email.review-post',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(SendStatement::class, $actual[0]);

        $this->assertEquals('ReviewPost', $actual[0]->mail());
        $this->assertEquals('post.author', $actual[0]->to());
        $this->assertEquals(['foo', 'bar', 'baz'], $actual[0]->data());
        $this->assertEquals('email.review-post', $actual[0]->view());
        $this->assertEquals(SendStatement::TYPE_MAIL, $actual[0]->type());
    }

    #[Test]
    public function it_returns_a_send_statement_type_notification_facade(): void
    {
        $tokens = [
            'send' => 'ReviewNotification',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(SendStatement::class, $actual[0]);

        $this->assertEquals('ReviewNotification', $actual[0]->mail());
        $this->assertNull($actual[0]->to());
        $this->assertSame([], $actual[0]->data());
        $this->assertEquals(SendStatement::TYPE_NOTIFICATION_WITH_FACADE, $actual[0]->type());
    }

    #[Test]
    public function it_returns_a_send_statement_to_only_type_notification_facade(): void
    {
        $tokens = [
            'send' => 'ReviewNotification to:post.author',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(SendStatement::class, $actual[0]);

        $this->assertEquals('ReviewNotification', $actual[0]->mail());
        $this->assertEquals('post.author', $actual[0]->to());
        $this->assertSame([], $actual[0]->data());
        $this->assertEquals(SendStatement::TYPE_NOTIFICATION_WITH_FACADE, $actual[0]->type());
    }

    #[Test]
    public function it_returns_a_send_statement_with_only_type_notification_facade(): void
    {
        $tokens = [
            'send' => 'ReviewNotification with:foo, bar, baz',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(SendStatement::class, $actual[0]);

        $this->assertEquals('ReviewNotification', $actual[0]->mail());
        $this->assertNull($actual[0]->to());
        $this->assertEquals(['foo', 'bar', 'baz'], $actual[0]->data());
        $this->assertEquals(SendStatement::TYPE_NOTIFICATION_WITH_FACADE, $actual[0]->type());
    }

    #[Test]
    public function it_returns_a_send_statement_to_and_with_type_notification_facade(): void
    {
        $tokens = [
            'send' => 'ReviewNotification to:post.author with:foo, bar, baz',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(SendStatement::class, $actual[0]);

        $this->assertEquals('ReviewNotification', $actual[0]->mail());
        $this->assertEquals('post.author', $actual[0]->to());
        $this->assertEquals(['foo', 'bar', 'baz'], $actual[0]->data());
        $this->assertEquals(SendStatement::TYPE_NOTIFICATION_WITH_FACADE, $actual[0]->type());
    }

    #[Test]
    public function it_returns_a_send_statement_with_type_notification_model(): void
    {
        $tokens = [
            'notify' => 'user ReviewNotification',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(SendStatement::class, $actual[0]);

        $this->assertEquals('ReviewNotification', $actual[0]->mail());
        $this->assertEquals('user', $actual[0]->to());
        $this->assertSame([], $actual[0]->data());
        $this->assertEquals(SendStatement::TYPE_NOTIFICATION_WITH_MODEL, $actual[0]->type());
    }

    #[Test]
    public function it_returns_a_send_statement_to_only_type_notification_model(): void
    {
        $tokens = [
            'notify' => 'post.author ReviewNotification',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(SendStatement::class, $actual[0]);

        $this->assertEquals('ReviewNotification', $actual[0]->mail());
        $this->assertEquals('post.author', $actual[0]->to());
        $this->assertSame([], $actual[0]->data());
        $this->assertEquals(SendStatement::TYPE_NOTIFICATION_WITH_MODEL, $actual[0]->type());
    }

    #[Test]
    public function it_returns_a_send_statement_with_only_type_notification_model(): void
    {
        $tokens = [
            'notify' => 'user ReviewNotification with:foo, bar, baz',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(SendStatement::class, $actual[0]);

        $this->assertEquals('ReviewNotification', $actual[0]->mail());
        $this->assertEquals('user', $actual[0]->to());
        $this->assertEquals(['foo', 'bar', 'baz'], $actual[0]->data());
        $this->assertEquals(SendStatement::TYPE_NOTIFICATION_WITH_MODEL, $actual[0]->type());
    }

    #[Test]
    public function it_returns_a_send_statement_to_and_with_type_notification_model(): void
    {
        $tokens = [
            'notify' => 'post.author ReviewNotification with:foo, bar, baz',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(SendStatement::class, $actual[0]);

        $this->assertEquals('ReviewNotification', $actual[0]->mail());
        $this->assertEquals('post.author', $actual[0]->to());
        $this->assertEquals(['foo', 'bar', 'baz'], $actual[0]->data());
        $this->assertEquals(SendStatement::TYPE_NOTIFICATION_WITH_MODEL, $actual[0]->type());
    }

    #[Test]
    public function it_returns_a_validate_statement(): void
    {
        $tokens = [
            'validate' => 'title, author_id, content',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(ValidateStatement::class, $actual[0]);

        $this->assertSame(['title', 'author_id', 'content'], $actual[0]->data());
    }

    #[Test]
    #[DataProvider('eloquentTokensProvider')]
    public function it_returns_an_eloquent_statement($operation, $reference): void
    {
        $tokens = [
            $operation => $reference,
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(EloquentStatement::class, $actual[0]);

        $this->assertSame($operation, $actual[0]->operation());
        $this->assertSame($reference, $actual[0]->reference());
    }

    #[Test]
    public function it_returns_an_update_eloquent_statement_with_columns(): void
    {
        $tokens = [
            'update' => 'name, title, age',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(EloquentStatement::class, $actual[0]);

        $this->assertSame('update', $actual[0]->operation());
        $this->assertNull($actual[0]->reference());
        $this->assertSame(['name', 'title', 'age'], $actual[0]->columns());
    }

    #[Test]
    #[DataProvider('sessionTokensProvider')]
    public function it_returns_a_session_statement($operation, $reference): void
    {
        $tokens = [
            $operation => $reference,
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(SessionStatement::class, $actual[0]);

        $this->assertSame($operation, $actual[0]->operation());
        $this->assertSame($reference, $actual[0]->reference());
    }

    #[Test]
    public function it_returns_a_redirect_statement(): void
    {
        $tokens = [
            'redirect' => 'route.index',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(RedirectStatement::class, $actual[0]);

        $this->assertEquals('route.index', $actual[0]->route());
        $this->assertSame([], $actual[0]->data());
    }

    #[Test]
    public function it_returns_a_redirect_statement_with_data(): void
    {
        $tokens = [
            'redirect' => 'route.show with:foo, bar,        baz',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(RedirectStatement::class, $actual[0]);

        $this->assertEquals('route.show', $actual[0]->route());
        $this->assertEquals(['foo', 'bar', 'baz'], $actual[0]->data());
    }

    #[Test]
    public function it_returns_a_response_statement_with_status_code(): void
    {
        $tokens = [
            'respond' => '204',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(RespondStatement::class, $actual[0]);

        $this->assertEquals(204, $actual[0]->status());
        $this->assertNull($actual[0]->content());
    }

    #[Test]
    public function it_returns_a_response_statement_with_content(): void
    {
        $tokens = [
            'respond' => 'post',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(RespondStatement::class, $actual[0]);

        $this->assertEquals(200, $actual[0]->status());
        $this->assertEquals('post', $actual[0]->content());
    }

    #[Test]
    public function it_returns_a_query_all_statement(): void
    {
        $tokens = [
            'query' => 'all:posts',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(QueryStatement::class, $actual[0]);

        $this->assertEquals('all', $actual[0]->operation());
        $this->assertSame(['posts'], $actual[0]->clauses());
        $this->assertSame('Post', $actual[0]->model());
    }

    #[Test]
    public function it_returns_a_query_all_statement_without_clause(): void
    {
        $tokens = [
            'query' => 'all',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(QueryStatement::class, $actual[0]);

        $this->assertEquals('all', $actual[0]->operation());
        $this->assertSame([], $actual[0]->clauses());
        $this->assertNull($actual[0]->model());
    }

    #[Test]
    public function it_returns_a_query_get_statement(): void
    {
        $tokens = [
            'query' => 'where:post.title order:post.created_at',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(QueryStatement::class, $actual[0]);

        $this->assertEquals('get', $actual[0]->operation());
        $this->assertSame(['where:post.title', 'order:post.created_at'], $actual[0]->clauses());
        $this->assertSame('Post', $actual[0]->model());
    }

    #[Test]
    public function it_returns_a_query_pluck_statement(): void
    {
        $tokens = [
            'query' => 'order:post.created_at pluck:id',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(QueryStatement::class, $actual[0]);

        $this->assertEquals('pluck', $actual[0]->operation());
        $this->assertSame(['order:post.created_at', 'pluck:id'], $actual[0]->clauses());
        $this->assertSame('Post', $actual[0]->model());
    }

    #[Test]
    public function it_returns_a_query_count_statement(): void
    {
        $tokens = [
            'query' => 'where:title count',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(QueryStatement::class, $actual[0]);

        $this->assertEquals('count', $actual[0]->operation());
        $this->assertSame(['where:title'], $actual[0]->clauses());
        $this->assertNull($actual[0]->model());
    }

    #[Test]
    public function it_returns_a_query_exists_statement(): void
    {
        $tokens = [
            'query' => 'where:title exists',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(QueryStatement::class, $actual[0]);

        $this->assertEquals('exists', $actual[0]->operation());
        $this->assertSame(['where:title'], $actual[0]->clauses());
        $this->assertNull($actual[0]->model());
    }

    #[Test]
    public function it_returns_a_resource_statement(): void
    {
        $tokens = [
            'resource' => 'user',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(ResourceStatement::class, $actual[0]);

        $this->assertEquals('UserResource', $actual[0]->name());
        $this->assertEquals('user', $actual[0]->reference());
        $this->assertFalse($actual[0]->collection());
        $this->assertFalse($actual[0]->paginate());
    }

    #[Test]
    public function it_returns_a_resource_collection_statement(): void
    {
        $tokens = [
            'resource' => 'collection:users',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(ResourceStatement::class, $actual[0]);

        $this->assertEquals('UserCollection', $actual[0]->name());
        $this->assertEquals('users', $actual[0]->reference());
        $this->assertTrue($actual[0]->collection());
        $this->assertFalse($actual[0]->paginate());
    }

    #[Test]
    public function it_returns_a_resource_collection_statement_with_pagination(): void
    {
        $tokens = [
            'resource' => 'paginate:users',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(ResourceStatement::class, $actual[0]);

        $this->assertEquals('UserCollection', $actual[0]->name());
        $this->assertEquals('users', $actual[0]->reference());
        $this->assertTrue($actual[0]->collection());
        $this->assertTrue($actual[0]->paginate());
    }

    #[Test]
    public function it_returns_a_resource_collection_statement_without_generating_a_resource_collection_class(): void
    {
        config(['blueprint.generate_resource_collection_classes' => false]);

        $tokens = [
            'resource' => 'collection:users',
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(ResourceStatement::class, $actual[0]);

        $this->assertEquals('UserResource', $actual[0]->name());
        $this->assertEquals('users', $actual[0]->reference());
        $this->assertTrue($actual[0]->collection());
        $this->assertFalse($actual[0]->paginate());
    }

    public static function sessionTokensProvider(): array
    {
        return [
            ['flash', 'post.title'],
            ['store', 'post.id'],
        ];
    }

    public static function eloquentTokensProvider(): array
    {
        return [
            ['save', 'post'],
            ['update', 'post'],
            ['delete', 'post.id'],
        ];
    }
}
