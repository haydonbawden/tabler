<div class="nav-item dropdown global-search" data-global-search data-search-url="<?= e($searchUrl ?? '/admin/search') ?>">
    <div class="input-icon">
        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
        <input type="search" class="form-control" placeholder="Search clients, audits, certificates..." autocomplete="off" data-global-search-input>
    </div>
    <div class="dropdown-menu dropdown-menu-end dropdown-menu-card global-search-menu" data-global-search-results>
        <div class="dropdown-item text-secondary">Start typing to search.</div>
    </div>
</div>
