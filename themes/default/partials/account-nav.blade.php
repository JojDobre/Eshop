<nav class="flex flex-wrap items-center gap-2 border-b border-zinc-100 pb-4 text-sm" aria-label="{{ __('theme::account.title') }}">
    @foreach ([['customer.account', __('theme::account.orders')], ['customer.profile', __('theme::account.profile')]] as [$routeName, $label])
        <a href="{{ route($routeName) }}" @class(['rounded-full px-4 py-2', 'bg-zinc-900 text-white' => request()->routeIs($routeName), 'text-zinc-600 hover:bg-zinc-100' => ! request()->routeIs($routeName)])
           @if (request()->routeIs($routeName)) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
    <form method="POST" action="{{ route('customer.logout') }}" class="ml-auto">
        @csrf
        <button type="submit" class="rounded-full px-4 py-2 text-zinc-600 hover:bg-zinc-100">{{ __('theme::account.logout') }}</button>
    </form>
</nav>
