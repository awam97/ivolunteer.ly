<?php
    $fileModel = new \App\Models\FileModel();
    $image_url = $fileModel->get_image_url('admin', $admin_id);
?>
<header class="top-header">
    <div class="header-left">
        <button id="mobileMenuBtn" class="mobile-toggle" title="Toggle Menu"><i class="fa-solid fa-bars"></i></button>
    </div>

    <?php if (!in_array($page_name, ['dashboard', 'profile', 'calendar', 'activity', 'news_page', 'settings', 'table'])): ?>
    <div class="header-search">
        <div id="headerAddBtn" class="header-add-btn" title="<?= $translate->translate_phrase('new',$language);?>">
            <i class="fa-solid fa-plus"></i>
        </div>
        <div class="search-input-wrapper">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" id="globalSearch" placeholder="<?= $translate->translate_phrase('type-to-search',$language);?>">
        </div>
    </div>
    <?php endif; ?>

    <div class="header-right">
        <?php if (!in_array($page_name, ['dashboard', 'profile', 'settings', 'table'])): ?>
        <div class="header-toggle-group">
            <div id="headerSelectAll" class="view-btn" title="<?= $translate->translate_phrase('select-all',$language);?>"><i class="fa-regular fa-square-check"></i></div>
            <div class="header-divider"></div>
            <?php if (!in_array($page_name, ['library', 'activities', 'news', 'table'])): ?>
                <div id="viewBtnGrid" class="view-btn active" title="Grid View"><i class="fa-solid fa-grip"></i></div>
                <div id="viewBtnList" class="view-btn" title="List View"><i class="fa-solid fa-list"></i></div>
                <div class="header-divider"></div>
            <?php endif; ?>
            <div class="sort-dropdown-container">
                <div id="headerSortTrigger" class="view-btn" title="الترتيب"><i class="fa-solid fa-arrow-down-wide-short"></i></div>
                <div class="custom-sort-menu" id="headerSortMenu">
                    <div class="sort-option" data-sort="az">من أ إلى ي</div>
                    <div class="sort-option" data-sort="za">من ي إلى أ</div>
                    <div class="sort-option" data-sort="newest">الأحدث أولاً</div>
                    <div class="sort-option" data-sort="oldest">الأقدم أولاً</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="user-profile-dropdown">
            <div class="user-profile-widget" id="userProfileTrigger">
                <span class="user-name"><?php echo $adminData->name; ?></span>
                <img src="<?= $image_url ?>" class="header-user-logo" alt="User Profile" />
                <i class="fa-solid fa-chevron-down profile-chevron"></i>
            </div>
            <div class="dropdown-menu-custom" id="userMenu">
                <a href="<?= base_url('Admin/profile') ?>" class="dropdown-item">
                    <i class="fa-solid fa-user-circle"></i> <?= $translate->translate_phrase('profile',$language);?>
                </a>
                <a href="<?= base_url('Admin/settings') ?>" class="dropdown-item">
                    <i class="fa-solid fa-cog"></i> <?= $translate->translate_phrase('settings',$language);?>
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?= base_url('Admin/logout') ?>" class="dropdown-item logout-item">
                    <i class="fa-solid fa-sign-out-alt"></i> <?= $translate->translate_phrase('logout',$language);?>
                </a>
            </div>
        </div>
    </div>
</header>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Header Scroll Effect ---
        const header = document.querySelector('.top-header');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // --- Profile Dropdown Logic ---
        const profileTrigger = document.getElementById('userProfileTrigger');
        const userMenu = document.getElementById('userMenu');
        
        if (profileTrigger && userMenu) {
            profileTrigger.addEventListener('click', function(e) {
                e.stopPropagation();
                userMenu.classList.toggle('show');
            });
            
            document.addEventListener('click', function(e) {
                if (!userMenu.contains(e.target) && !profileTrigger.contains(e.target)) {
                    userMenu.classList.remove('show');
                }
            });
        }

        // Force light mode only for the admin portal.
        document.documentElement.removeAttribute('data-theme');
        localStorage.removeItem('theme');

        // --- Mobile Menu Toggle ---
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.querySelector('.sidebar-wrapper');
        const sidebarClose = document.getElementById('sidebarClose'); // Will add this in navigation.php
        
        if (mobileBtn && sidebar) {
            mobileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                sidebar.classList.toggle('show');
            });
            
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 992 && !sidebar.contains(e.target) && !mobileBtn.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            });
        }

        // --- Global View Toggle ---
        const viewBtnGrid = document.getElementById('viewBtnGrid');
        const viewBtnList = document.getElementById('viewBtnList');

        function applyViewMode(mode) {
            const containers = document.querySelectorAll('.view-mode-container');
            containers.forEach(container => {
                if (mode === 'grid') {
                    container.classList.remove('view-list');
                    container.classList.add('view-grid');
                } else {
                    container.classList.remove('view-grid');
                    container.classList.add('view-list');
                }
            });
            
            if (mode === 'grid') {
                viewBtnGrid?.classList.add('active');
                viewBtnList?.classList.remove('active');
            } else {
                viewBtnList?.classList.add('active');
                viewBtnGrid?.classList.remove('active');
            }
            
            localStorage.setItem('pref-view', mode);
            window.dispatchEvent(new Event('resize'));
        }

        if (viewBtnGrid && viewBtnList) {
            viewBtnGrid.addEventListener('click', () => applyViewMode('grid'));
            viewBtnList.addEventListener('click', () => applyViewMode('list'));
            
            // Load preference
            const prefView = localStorage.getItem('pref-view') || 'grid';
            applyViewMode(prefView);
        }

        // --- Search bar, Select All, and Sort logic ---
        const globalSearch = document.getElementById('globalSearch');
        const headerSelectAll = document.getElementById('headerSelectAll');
        const headerSortTrigger = document.getElementById('headerSortTrigger');
        const headerSortMenu = document.getElementById('headerSortMenu');

        // 1. Global Search (Instant AJAX)
        if (globalSearch) {
            let searchTimeout;
            
            // Set initial value if search query exists in URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('search')) {
                globalSearch.value = urlParams.get('search');
            }

            globalSearch.addEventListener('input', function() {
                const query = this.value.trim();
                
                // --- Part A: Instant Local Filter (Immediate feedback) ---
                const localQuery = query.toLowerCase();
                $('.entity-card-wrapper').each(function() {
                    const cardText = $(this).text().toLowerCase();
                    $(this).toggle(cardText.includes(localQuery));
                });

                // --- Part B: Server-side Search (AJAX for full DB search) ---
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const url = new URL(window.location.href);
                    if (query) url.searchParams.set('search', query);
                    else url.searchParams.delete('search');
                    
                    // Reset pagination for new search
                    const keysToDelete = [];
                    url.searchParams.forEach((val, key) => {
                        if (key.startsWith('page')) keysToDelete.push(key);
                    });
                    keysToDelete.forEach(key => url.searchParams.delete(key));

                    // Update URL silently
                    window.history.pushState({}, '', url);

                    // Fetch and update content
                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(response => response.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            
                            // 1. Update Grid Content
                            const newGrid = doc.getElementById('gridContainerOne');
                            const currentGrid = document.getElementById('gridContainerOne');
                            if (newGrid && currentGrid) {
                                currentGrid.innerHTML = newGrid.innerHTML;
                                // Re-apply current view mode if necessary
                                const currentMode = localStorage.getItem('pref-view-mode') || 'grid';
                                applyViewMode(currentMode);
                            }

                            // 2. Update Pagination
                            const newPager = doc.querySelector('.pagination-container');
                            const currentPager = document.querySelector('.pagination-container');
                            if (newPager && currentPager) {
                                currentPager.innerHTML = newPager.innerHTML;
                            }

                            // 3. Update DataTables if present
                            if (window.jQuery && $.fn.DataTable && $.fn.DataTable.isDataTable('#example')) {
                                $('#example').DataTable().search(query).draw();
                            }
                        })
                        .catch(err => console.warn('AJAX Search error:', err));
                }, 300);
            });

            // Prevent Enter from reloading the page
            globalSearch.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') e.preventDefault();
            });
        }

        // 1.1 Modal Trigger
        const headerAddBtn = document.getElementById('headerAddBtn');
        if (headerAddBtn) {
            headerAddBtn.addEventListener('click', function() {
                if (typeof window.openAddModal === 'function') {
                    window.openAddModal();
                }
            });
        }

        // 2. Select All
        if (headerSelectAll) {
            headerSelectAll.addEventListener('click', function() {
                this.classList.toggle('active');
                const isChecked = this.classList.contains('active');
                
                // Mirror to page's select_all checkbox if it exists (DataTables)
                const pageMainCheckbox = document.getElementById('select_all');
                if (pageMainCheckbox) {
                    pageMainCheckbox.checked = isChecked;
                }

                // Trigger page-specific select_all function (DataTables or Grid)
                if (typeof window.select_all === 'function') {
                    window.select_all(isChecked);
                } else if (typeof select_all === 'function') {
                    select_all(isChecked);
                }
            });
        }

        // 3. Sorting
        if (headerSortTrigger && headerSortMenu) {
            headerSortTrigger.addEventListener('click', function(e) {
                e.stopPropagation();
                headerSortMenu.classList.toggle('show');
            });

            document.addEventListener('click', function(e) {
                if (!headerSortMenu.contains(e.target)) {
                    headerSortMenu.classList.remove('show');
                }
            });

            const sortOptions = headerSortMenu.querySelectorAll('.sort-option');
            sortOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const sortMode = this.dataset.sort;
                    
                    // Update active state
                    sortOptions.forEach(opt => opt.classList.remove('active'));
                    this.classList.add('active');
                    headerSortMenu.classList.remove('show');

                    // DataTables Sorting
                    if (window.jQuery && $.fn.DataTable && $.fn.DataTable.isDataTable('#example')) {
                        const table = $('#example').DataTable();
                        switch(sortMode) {
                            case 'az': table.order([2, 'asc']).draw(); break;
                            case 'za': table.order([2, 'desc']).draw(); break;
                            case 'newest': table.order([1, 'desc']).draw(); break;
                            case 'oldest': table.order([1, 'asc']).draw(); break;
                        }
                    }
                    
                    // Server-side Sorting via Page Reload
                    const url = new URL(window.location.href);
                    url.searchParams.set('sort', sortMode);
                    window.location.href = url.toString();
                });
            });
        }
    });
</script>
