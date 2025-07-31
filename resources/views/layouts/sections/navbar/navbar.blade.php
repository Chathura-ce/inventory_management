@php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Route;
    $containerNav = $containerNav ?? 'container-fluid';
    $navbarDetached = ($navbarDetached ?? '');
@endphp

        <!-- Navbar -->
@if(isset($navbarDetached) && $navbarDetached == 'navbar-detached')
    <nav class="layout-navbar {{$containerNav}} navbar navbar-expand-xl {{$navbarDetached}} align-items-center bg-navbar-theme"
         id="layout-navbar">
        @endif
        @if(isset($navbarDetached) && $navbarDetached == '')
            <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
                <div class="{{$containerNav}}">
                    @endif

                    <!--  Brand demo (display only for navbar-full and hide on below xl) -->
                    @if(isset($navbarFull))
                        <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4">
                            <a href="{{url('/')}}" class="app-brand-link gap-2">
                                <span class="app-brand-logo demo">@include('_partials.macros',["width"=>25,"withbg"=>'var(--bs-primary)'])</span>
                                <span class="app-brand-text demo menu-text fw-bold text-heading">{{config('variables.templateName')}}</span>
                            </a>
                        </div>
                    @endif

                    <!-- ! Not required for layout-without-menu -->
                    @if(!isset($navbarHideToggle))
                        <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0{{ isset($menuHorizontal) ? ' d-xl-none ' : '' }} {{ isset($contentNavbar) ?' d-xl-none ' : '' }}">
                            <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
                                <i class="bx bx-menu bx-md"></i>
                            </a>
                        </div>
                    @endif

                    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">

                        <ul class="navbar-nav flex-row align-items-center ms-auto">

                            <!-- Place this tag where you want the button to render. -->
                            <li class="nav-item dropdown me-4">
                                <a class="nav-link position-relative" href="#" id="notificationDropdown"
                                   data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-bell bx-md"></i>
                                    <span id="notif-badge" class="badge bg-danger rounded-circle
          position-absolute top-0 start-100 translate-middle p-1"
                                          style="font-size:0.6rem; display:none;"></span>
                                </a>

                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown"
                                    style="min-width:300px">
                                    <li class="dropdown-header">Notifications</li>
                                    <div id="notif-list">
                                        <li class="dropdown-item text-center text-muted py-3">Loading…</li>
                                    </div>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-center" href="{{ route('notifications.index') }}">
                                            View All Notifications
                                        </a>
                                    </li>
                                </ul>
                            </li>


                            <!-- User -->
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);"
                                   data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <img src="{{ asset('assets/img/avatars/1.png') }}" alt
                                             class="w-px-40 h-auto rounded-circle">
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0);">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar avatar-online">
                                                        <img src="{{ asset('assets/img/avatars/1.png') }}" alt
                                                             class="w-px-40 h-auto rounded-circle">
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0">{{ Auth::user()->name }}</h6>
                                                    <small class="text-muted">{{ Auth::user()->role ?? 'User' }}</small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>

                                    <li>
                                        <div class="dropdown-divider my-1"></div>
                                    </li>
                                    {{--              <li>--}}
                                    {{--                <a class="dropdown-item" href="javascript:void(0);">--}}
                                    {{--                  <i class="bx bx-user bx-md me-3"></i><span>My Profile</span>--}}
                                    {{--                </a>--}}
                                    {{--              </li>--}}
                                    {{--              <li>--}}
                                    {{--                <a class="dropdown-item" href="javascript:void(0);">--}}
                                    {{--                  <i class="bx bx-cog bx-md me-3"></i><span>Settings</span>--}}
                                    {{--                </a>--}}
                                    {{--              </li>--}}
                                    <li>
                                        <div class="dropdown-divider my-1"></div>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item d-flex align-items-center">
                                                <i class="bx bx-power-off bx-md me-3"></i>
                                                <span>Log Out</span>
                                            </button>
                                        </form>

                                    </li>
                                </ul>
                            </li>
                            <!--/ User -->
                        </ul>
                    </div>

                    @if(!isset($navbarDetached))
                </div>
                @endif
            </nav>
            <!-- / Navbar -->
            @push('scripts')
                <script>
                  document.addEventListener('DOMContentLoaded', () => {
                    const badgeEl = document.getElementById('notif-badge');
                    const listEl  = document.getElementById('notif-list');

                    const bell = document.getElementById('notificationDropdown');
                    bell.addEventListener('shown.bs.dropdown', async () => {
                      // 1) Tell the server to mark all as read
                      await fetch('{{ route("notifications.readAll") }}', {
                        method: 'POST',
                        headers: {
                          'X-CSRF-TOKEN': '{{ csrf_token() }}',
                          'Accept':       'application/json'
                        }
                      });
                      // 2) Hide the badge
                      document.getElementById('notif-badge').style.display = 'none';
                    });


                    async function fetchNotifications() {
                      try {
                        const res  = await fetch('{{ route('notifications.unread') }}', {
                          headers: { 'Accept': 'application/json' }
                        });
                        const json = await res.json();

                        // Badge
                        if (json.count > 0) {
                          badgeEl.textContent = json.count;
                          badgeEl.style.display = 'inline-block';
                        } else {
                          badgeEl.style.display = 'none';
                        }

                        // Dropdown list
                        if (json.recent.length) {
                          listEl.innerHTML = json.recent.map(n => `
          <li>
            <a class="dropdown-item py-2" href="/notifications/${n.id}"
               onclick="event.preventDefault(); ">
              <div class="small text-truncate">${n.message}</div>
              <div class="small text-muted">${n.time}</div>
            </a>
          </li>
        `).join('');
                        } else {
                          listEl.innerHTML = `
          <li class="dropdown-item text-center text-muted py-3">
            No new notifications
          </li>
        `;
                        }
                      } catch (err) {
                        console.error('Notif fetch error', err);
                      }
                    }

                    // Poll on load + every 30s
                    fetchNotifications();
                    setInterval(fetchNotifications, 30000);
                  });
                </script>
    @endpush

