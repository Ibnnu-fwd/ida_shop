@props(['icon' => '', 'title' => '', 'active' => false, 'href' => '#', 'id' => ''])
<li class="nav-item {{ $active ? '' : 'collapsed' }}">
    <a class="nav-link" data-bs-toggle="{{ $active ? 'collapse' : '' }}"
        aria-expanded="{{ $active ? 'true' : 'false' }}" aria-controls="{{ $id }}">
        <i class="{{ $icon }}"></i>
        <span class="menu-title">
            {{ $title }}
        </span>
        <i class="menu-arrow"></i>
    </a>
    <div class="{{ $active ? 'collapse show' : 'collapse' }}" id="{{ $id }}">
        <ul class="nav flex-column sub-menu">
            {{ $slot }}
        </ul>
    </div>
</li>
