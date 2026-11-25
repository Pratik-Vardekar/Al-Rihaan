@php
    $active = $active ?? '';
    $userName = $user->name ?? 'User';
@endphp

{{-- Mobile account navigation --}}
<div class="col-12 d-lg-none mb-4">
    <div class="myAccMobileNav">
        <div class="myAccMobileScroll">
            <a href="{{ route('profile.profile') }}" class="myAccMobileLink {{ $active === 'profile' ? 'active' : '' }}">
                <i data-lucide="user"></i>
                <span>Profile</span>
            </a>
            <a href="{{ route('profile.orders') }}" class="myAccMobileLink {{ $active === 'orders' ? 'active' : '' }}">
                <i data-lucide="package"></i>
                <span>Orders</span>
            </a>
            <a href="{{ route('wishlist') }}" class="myAccMobileLink {{ $active === 'wishlist' ? 'active' : '' }}">
                <i data-lucide="heart"></i>
                <span>Wishlist</span>
            </a>
            <a href="{{ route('profile.address') }}"
                class="myAccMobileLink {{ $active === 'address' ? 'active' : '' }}">
                <i data-lucide="map-pin"></i>
                <span>Addresses</span>
            </a>
            <a href="{{ route('profile.changepassword') }}"
                class="myAccMobileLink {{ $active === 'password' ? 'active' : '' }}">
                <i data-lucide="key"></i>
                <span>Password</span>
            </a>
        </div>
    </div>
</div>

{{-- Desktop account sidebar --}}
<div class="col-lg-3 mb-lg-4 d-none d-lg-block my-account-sidebar">
    <div class="myAccLinkWrap">
        <div class="profileGreeting mb-4">
            <div class="greetingAvatar">
                <i data-lucide="user-check"></i>
            </div>
            <div class="greetingInfo">
                <small class="greetingSub">Hello,</small>
                <strong class="greetingName">{{ $userName }}</strong>
            </div>
        </div>
        <ul class="myAccLinks"
            style="list-style: none !important; list-style-type: none !important; padding: 0 !important; margin: 0 !important;">
            <li style="list-style: none !important; list-style-type: none !important;">
                <a href="{{ route('profile.profile') }}" class="{{ $active === 'profile' ? 'active' : '' }}">
                    <i data-lucide="user"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li style="list-style: none !important; list-style-type: none !important;">
                <a href="{{ route('profile.orders') }}" class="{{ $active === 'orders' ? 'active' : '' }}">
                    <i data-lucide="package"></i>
                    <span>Orders</span>
                </a>
            </li>
            <li style="list-style: none !important; list-style-type: none !important;">
                <a href="{{ route('wishlist') }}" class="{{ $active === 'wishlist' ? 'active' : '' }}">
                    <i data-lucide="heart"></i>
                    <span>Wishlist</span>
                </a>
            </li>
            <li style="list-style: none !important; list-style-type: none !important;">
                <a href="{{ route('profile.address') }}" class="{{ $active === 'address' ? 'active' : '' }}">
                    <i data-lucide="map-pin"></i>
                    <span>Addresses</span>
                </a>
            </li>
            <li style="list-style: none !important; list-style-type: none !important;">
                <a href="{{ route('profile.changepassword') }}" class="{{ $active === 'password' ? 'active' : '' }}">
                    <i data-lucide="key"></i>
                    <span>Change Password</span>
                </a>
            </li>
        </ul>
    </div>
</div>
