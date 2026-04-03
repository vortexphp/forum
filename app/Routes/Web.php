<?php

declare(strict_types=1);

use App\Handlers\AccountHandler;
use App\Handlers\Auth\LoginHandler;
use App\Handlers\Auth\LogoutHandler;
use App\Handlers\Auth\RegisterHandler;
use App\Handlers\Forum\CategoryHandler;
use App\Handlers\Forum\BookmarkHandler;
use App\Handlers\Forum\FlagHandler;
use App\Handlers\Forum\LikeHandler;
use App\Handlers\Forum\ModerationHandler;
use App\Handlers\Forum\PostHandler;
use App\Handlers\Forum\ThreadHandler;
use App\Handlers\NotificationHandler;
use App\Handlers\PrivateMessageHandler;
use App\Handlers\ProfileHandler;
use App\Handlers\SearchHandler;
use App\Middleware\GuestOnly;
use App\Middleware\RequireAuth;
use App\Middleware\RequireModerator;
use App\Middleware\ThrottleReplyCreate;
use App\Middleware\ThrottleLogin;
use App\Middleware\ThrottleRegister;
use App\Middleware\ThrottleThreadCreate;
use Vortex\Routing\Route;

/**
 * HTTP route registration. Loaded automatically from `app/Routes/` (see {@see \Vortex\Routing\RouteDiscovery}).
 */

Route::get('/', [CategoryHandler::class, 'index'])->name('home');
Route::get('/register', [RegisterHandler::class, 'show'], [GuestOnly::class])
    ->name('register.show')
    ->post('/register', [RegisterHandler::class, 'store'], [GuestOnly::class, ThrottleRegister::class])
    ->name('register.store');

Route::get('/login', [LoginHandler::class, 'show'], [GuestOnly::class])
    ->name('login.show')
    ->post('/login', [LoginHandler::class, 'store'], [GuestOnly::class, ThrottleLogin::class])
    ->name('login.store');

Route::post('/logout', [LogoutHandler::class, 'store'])->name('logout.store');

Route::get('/account', [AccountHandler::class, 'index'], [RequireAuth::class])->name('account.index');
Route::get('/account/edit', [AccountHandler::class, 'edit'], [RequireAuth::class])
    ->name('account.edit')
    ->post('/account/edit', [AccountHandler::class, 'update'], [RequireAuth::class])
    ->name('account.update');
Route::get('/users/{user}', [ProfileHandler::class, 'show'])->name('profile.show');
Route::get('/messages', [PrivateMessageHandler::class, 'inbox'], [RequireAuth::class])->name('messages.inbox');
Route::get('/messages/{user}', [PrivateMessageHandler::class, 'conversation'], [RequireAuth::class])->name('messages.show');
Route::post('/messages/{user}', [PrivateMessageHandler::class, 'send'], [RequireAuth::class])->name('messages.send');
Route::get('/messages/{user}/feed', [PrivateMessageHandler::class, 'feed'], [RequireAuth::class])->name('messages.feed');
Route::post('/messages/{user}/send-json', [PrivateMessageHandler::class, 'sendJson'], [RequireAuth::class])->name('messages.send_json');
Route::get('/notifications', [NotificationHandler::class, 'index'], [RequireAuth::class])->name('notifications.index');
Route::get('/search/suggest', [SearchHandler::class, 'suggest'])->name('search.suggest');

Route::get('/forum', [CategoryHandler::class, 'index'])->name('forum.index');
Route::get('/forum/bookmarks', [BookmarkHandler::class, 'index'], [RequireAuth::class])->name('forum.bookmarks');
Route::get('/forum/{category}', [CategoryHandler::class, 'show'])->name('forum.category');
Route::get('/forum/{category}/new', [ThreadHandler::class, 'create'], [RequireAuth::class])->name('forum.thread.create');
Route::post('/forum/{category}/new', [ThreadHandler::class, 'store'], [RequireAuth::class, ThrottleThreadCreate::class])
    ->name('forum.thread.store');
Route::get('/forum/{category}/{thread}', [ThreadHandler::class, 'show'])->name('forum.thread.show');
Route::post('/forum/{category}/{thread}/reply', [PostHandler::class, 'store'], [RequireAuth::class, ThrottleReplyCreate::class])
    ->name('forum.post.store');
Route::post('/forum/{category}/{thread}/bookmark', [BookmarkHandler::class, 'toggle'], [RequireAuth::class])
    ->name('forum.thread.bookmark');
Route::post('/forum/{category}/{thread}/posts/{post}/like', [LikeHandler::class, 'toggle'], [RequireAuth::class])
    ->name('forum.post.like');
Route::post('/forum/{category}/{thread}/flag', [FlagHandler::class, 'flagThread'], [RequireAuth::class])
    ->name('forum.flag.thread');
Route::post('/forum/{category}/{thread}/posts/{post}/flag', [FlagHandler::class, 'flagPost'], [RequireAuth::class])
    ->name('forum.flag.post');
Route::get('/forum/{category}/{thread}/posts/{post}/edit', [PostHandler::class, 'edit'], [RequireAuth::class])
    ->name('forum.post.edit');
Route::post('/forum/{category}/{thread}/posts/{post}/edit', [PostHandler::class, 'update'], [RequireAuth::class])
    ->name('forum.post.update');
Route::post('/forum/{category}/{thread}/moderate/lock', [ModerationHandler::class, 'toggleLock'], [RequireModerator::class])
    ->name('forum.moderation.lock');
Route::post('/forum/{category}/{thread}/moderate/pin', [ModerationHandler::class, 'togglePin'], [RequireModerator::class])
    ->name('forum.moderation.pin');
Route::post('/forum/{category}/{thread}/moderate/sticky', [ModerationHandler::class, 'togglePin'], [RequireModerator::class])
    ->name('forum.moderation.sticky');
Route::post('/forum/{category}/{thread}/moderate/delete', [ModerationHandler::class, 'deleteThread'], [RequireModerator::class])
    ->name('forum.moderation.delete_thread');
Route::post('/forum/{category}/{thread}/posts/{post}/moderate/delete', [ModerationHandler::class, 'deletePost'], [RequireModerator::class])
    ->name('forum.moderation.delete_post');
