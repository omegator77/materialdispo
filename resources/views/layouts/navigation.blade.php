<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    <x-nav-link :href="route('items.index')" :active="request()->routeIs('items.*') && ! request()->routeIs('items.rented')">
                        {{ __('Geräte') }}
                    </x-nav-link>

                    <x-nav-link :href="route('productions.index')" :active="request()->routeIs('productions.*')">
                        {{ __('Produktionen') }}
                    </x-nav-link>

                    <x-nav-link :href="route('itemprods')" :active="request()->routeIs('itemprods')">
                        {{ __('Packen') }}
                    </x-nav-link>

                    <x-nav-link :href="route('timeline.items')" :active="request()->routeIs('timeline.items')">
                        {{ __('Timeline') }}
                    </x-nav-link>

                    @php
                        $mieteActive = request()->routeIs('items.rented', 'suppliers.*', 'mietvorgaenge.*', 'mieter.*', 'vermietvorgaenge.*');
                    @endphp
                    <div class="flex items-center">
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button type="button"
                                        class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out {{ $mieteActive ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                    {{ __('Miete & Verleih') }}
                                    <svg class="ms-1 h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('Eingang (wir mieten)') }}</div>
                                <x-dropdown-link :href="route('items.rented')">
                                    {{ __('Mietgeräte') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('suppliers.index')">
                                    {{ __('Vermieter') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('mietvorgaenge.index')">
                                    {{ __('Mietvorgänge') }}
                                </x-dropdown-link>

                                <div class="border-t border-gray-100 my-1"></div>
                                <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('Ausgang (wir verleihen)') }}</div>
                                <x-dropdown-link :href="route('mieter.index')">
                                    {{ __('Mieter') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('vermietvorgaenge.index')">
                                    {{ __('Vermietvorgänge') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    </div>

                    @php
                        $verwaltungRoutes = ['units.*', 'geraetetypen.*', 'mailing-lists.*'];
                        if (Auth::user()->isUser()) {
                            $verwaltungRoutes = array_merge($verwaltungRoutes, ['activity-log.*']);
                        }
                        if (Auth::user()->isAdmin()) {
                            $verwaltungRoutes = array_merge($verwaltungRoutes, ['users.*', 'settings.*']);
                        }
                        $verwaltungActive = request()->routeIs(...$verwaltungRoutes);
                    @endphp
                    <div class="flex items-center">
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button type="button"
                                        class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out {{ $verwaltungActive ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                    {{ __('Verwaltung') }}
                                    <svg class="ms-1 h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('Stammdaten') }}</div>
                                <x-dropdown-link :href="route('units.index')">
                                    {{ __('Gruppen') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('geraetetypen.index')">
                                    {{ __('Gerätetypen') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('mailing-lists.index')">
                                    {{ __('Mailinglisten') }}
                                </x-dropdown-link>

                                @if(Auth::user()->isUser())
                                    <div class="border-t border-gray-100 my-1"></div>
                                    <x-dropdown-link :href="route('activity-log.index')">
                                        {{ __('Protokoll') }}
                                    </x-dropdown-link>
                                @endif

                                @if(Auth::user()->isAdmin())
                                    <div class="border-t border-gray-100 my-1"></div>
                                    <x-dropdown-link :href="route('users.index')">
                                        {{ __('Benutzer') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="route('settings.edit')">
                                        {{ __('Einstellungen') }}
                                    </x-dropdown-link>
                                @endif
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>
            </div>

            <!-- Suche / Command-Palette -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-palette'))"
                        class="inline-flex items-center gap-2 px-3 py-1.5 text-sm text-gray-500 bg-gray-50 border border-gray-200 rounded-md hover:bg-gray-100 hover:text-gray-700"
                        aria-label="Suche öffnen">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
                    <span>Suchen…</span>
                    <kbd class="text-[10px] text-gray-400 border rounded px-1">Strg K</kbd>
                </button>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-2 text-xs text-gray-400 border-b">
                            @php
                                $roleLabel = match(Auth::user()->role) {
                                    'admin'  => 'Admin',
                                    'user'   => 'Benutzer',
                                    'viewer' => 'Betrachter',
                                    default  => Auth::user()->role,
                                };
                            @endphp
                            {{ $roleLabel }}
                        </div>

                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profil') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Abmelden') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-palette'))"
                    class="block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50">
                {{ __('Suchen …') }}
            </button>
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('items.index')" :active="request()->routeIs('items.*') && ! request()->routeIs('items.rented')">
                {{ __('Geräte') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('productions.index')" :active="request()->routeIs('productions.*')">
                {{ __('Produktionen') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('itemprods')" :active="request()->routeIs('itemprods')">
                {{ __('Packen') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('timeline.items')" :active="request()->routeIs('timeline.items')">
                {{ __('Timeline') }}
            </x-responsive-nav-link>

            <div class="pt-2 mt-2 border-t border-gray-200">
                <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('Eingang (wir mieten)') }}</div>

                <x-responsive-nav-link :href="route('items.rented')" :active="request()->routeIs('items.rented')">
                    {{ __('Mietgeräte') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('suppliers.index')" :active="request()->routeIs('suppliers.*')">
                    {{ __('Vermieter') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('mietvorgaenge.index')" :active="request()->routeIs('mietvorgaenge.*')">
                    {{ __('Mietvorgänge') }}
                </x-responsive-nav-link>

                <div class="px-4 py-1 mt-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('Ausgang (wir verleihen)') }}</div>

                <x-responsive-nav-link :href="route('mieter.index')" :active="request()->routeIs('mieter.*')">
                    {{ __('Mieter') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('vermietvorgaenge.index')" :active="request()->routeIs('vermietvorgaenge.*')">
                    {{ __('Vermietvorgänge') }}
                </x-responsive-nav-link>
            </div>

            <div class="pt-2 mt-2 border-t border-gray-200">
                <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('Stammdaten') }}</div>

                <x-responsive-nav-link :href="route('units.index')" :active="request()->routeIs('units.*')">
                    {{ __('Gruppen') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('geraetetypen.index')" :active="request()->routeIs('geraetetypen.*')">
                    {{ __('Gerätetypen') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('mailing-lists.index')" :active="request()->routeIs('mailing-lists.*')">
                    {{ __('Mailinglisten') }}
                </x-responsive-nav-link>

                @if(Auth::user()->isUser())
                    <x-responsive-nav-link :href="route('activity-log.index')" :active="request()->routeIs('activity-log.*')">
                        {{ __('Protokoll') }}
                    </x-responsive-nav-link>
                @endif

                @if(Auth::user()->isAdmin())
                    <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                        {{ __('Benutzer') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('settings.edit')" :active="request()->routeIs('settings.*')">
                        {{ __('Einstellungen') }}
                    </x-responsive-nav-link>
                @endif
            </div>
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profil') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Abmelden') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
