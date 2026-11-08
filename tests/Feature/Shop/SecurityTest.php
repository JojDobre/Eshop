<?php

namespace Tests\Feature\Shop;

class SecurityTest extends ShopTestCase
{
    public function test_security_headers_are_sent(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('Strict-Transport-Security');

        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=15552000');
        config(['eshop.security.hsts' => false]);
        $this->get('https://localhost/')->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_rich_text_is_sanitized_in_the_shop(): void
    {
        $product = $this->product('Káva', attributes: ['description' => '<p onclick="steal()">Lahodná<script>alert(1)</script> <a href="javascript:alert(1)">káva</a></p><iframe src="https://www.youtube.com/embed/x"></iframe><iframe src="https://zly.example"></iframe>']);

        $this->get($product->url())
            ->assertOk()
            ->assertSee('Lahodná')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('onclick="steal()"', false)
            ->assertDontSee('javascript:alert', false)
            ->assertSee('https://www.youtube.com/embed/x', false)
            ->assertDontSee('zly.example', false);
    }
}
