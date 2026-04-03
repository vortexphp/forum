<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Models\ForumSetting;
use Closure;
use Vortex\Config\Repository;
use Vortex\Contracts\Middleware;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;
use Vortex\I18n\Translator;
use Vortex\View\View;
use Throwable;

/**
 * Resolves locale from ?lang=, session, Accept-Language, then config; syncs {@see Translator} and view shares.
 */
final class SetLocale implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = Repository::get('app.locales', ['en']);
        if (! is_array($supported)) {
            $supported = ['en'];
        }
        /** @var list<string> $supported */
        $supported = array_values(array_filter(array_map('strval', $supported)));

        $default = (string) Repository::get('app.locale', 'en');

        Session::start();

        $lang = Request::query()['lang'] ?? null;
        if (is_string($lang) && in_array($lang, $supported, true)) {
            Session::put('locale', $lang);
        }

        $fromSession = Session::get('locale');
        if (is_string($fromSession) && in_array($fromSession, $supported, true)) {
            $locale = $fromSession;
        } else {
            if (is_string($fromSession)) {
                Session::forget('locale');
            }
            $locale = $this->negotiate($supported, $default);
        }

        Translator::setLocale($locale);
        View::share('locale', $locale);
        View::share('supportedLocales', $supported);
        View::share('htmlLang', $locale);
        $appName = (string) Repository::get('app.name', 'App');
        $appDescription = null;
        try {
            $dbTitle = ForumSetting::value('forum_title');
            $dbDescription = ForumSetting::value('forum_description');
            if ($dbTitle !== null && trim($dbTitle) !== '') {
                $appName = trim($dbTitle);
            }
            if ($dbDescription !== null && trim($dbDescription) !== '') {
                $appDescription = trim($dbDescription);
            }
        } catch (Throwable) {
            // Fresh installs may not have forum_settings yet.
        }
        View::share('appName', $appName);
        View::share('appDescription', $appDescription);

        return $next($request);
    }

    /**
     * @param list<string> $supported
     */
    private function negotiate(array $supported, string $default): string
    {
        $header = Request::header('Accept-Language');
        if ($header === null || $header === '') {
            return $default;
        }

        foreach (explode(',', $header) as $part) {
            $tag = strtolower(trim(explode(';', $part, 2)[0]));
            if ($tag === '') {
                continue;
            }
            $base = explode('-', $tag)[0];
            foreach ($supported as $code) {
                $c = strtolower($code);
                if ($c === $tag || $c === $base) {
                    return $code;
                }
            }
        }

        return $default;
    }
}
