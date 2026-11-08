<?php

namespace Modules\Marketing\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Marketing\Models\Subscriber;
use Modules\Marketing\Newsletter\Newsletter;

class NewsletterController extends Controller
{
    public function __construct(private readonly Newsletter $newsletter) {}

    public function subscribe(Request $request): RedirectResponse
    {
        abort_unless(Newsletter::enabled(), 404);

        $data = $request->validate(['email' => ['required', 'email', 'max:255'], 'consent' => ['accepted']]);

        // Roboty (vyplnené skryté pole) dostanú rovnakú odpoveď, nič sa neuloží.
        if (blank($request->input('website'))) {
            $this->newsletter->subscribe($data['email'], null, 'form', $request->ip());
        }

        return back()->with('success', __('marketing::shop.newsletter_check_email'));
    }

    public function confirm(string $token): View
    {
        $subscriber = Subscriber::query()->where('token', $token)->firstOrFail();
        $this->newsletter->confirm($subscriber);

        return view('marketing::message', ['title' => __('marketing::shop.newsletter_confirmed_title'), 'message' => __('marketing::shop.newsletter_confirmed')]);
    }

    public function unsubscribe(string $token): View
    {
        $subscriber = Subscriber::query()->where('token', $token)->firstOrFail();
        $this->newsletter->unsubscribe($subscriber);

        return view('marketing::message', ['title' => __('marketing::shop.newsletter_unsubscribed_title'), 'message' => __('marketing::shop.newsletter_unsubscribed')]);
    }
}
