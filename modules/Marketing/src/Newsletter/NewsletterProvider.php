<?php

namespace Modules\Marketing\Newsletter;

use Modules\Marketing\Models\Subscriber;

/**
 * Externá služba, kam sa odberatelia posielajú (Mailchimp, Ecomail).
 */
interface NewsletterProvider
{
    /** Pridá alebo aktualizuje odberateľa podľa jeho stavu (prihlásený / odhlásený). */
    public function sync(Subscriber $subscriber): void;
}
