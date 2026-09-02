<?php 
    $volunteer_name = $db->table('volunteers')->where('id', $volunteer_id)->get()->getRow()->name;
    $folderPath = "uploads/volunteers_files/";
    $filePath = glob($folderPath . $volunteer_id . ".*");
    $default_image = 'uploads/user.jpg';
    $image_url = ($filePath && file_exists($filePath[0])) 
        ? base_url($filePath[0]) 
        : base_url($default_image);
?>
<header class="top-header">
    <div class="header-left">
        <button id="mobileMenuBtn" class="mobile-toggle" title="Toggle Menu"><i class="fa-solid fa-bars"></i></button>
    </div>

    <?php if (!in_array($page_name, ['dashboard', 'profile'])): ?>
    <div class="header-search">
        <div class="search-input-wrapper">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" id="globalSearch" placeholder="بحث عن نشاطات...">
        </div>
    </div>
    <?php endif; ?>

    <div class="header-right">
        <div class="user-profile-dropdown">
            <div class="user-profile-widget" id="userProfileTrigger">
                <span class="user-name"><?php echo $volunteer_name; ?></span>
                <img src="<?= $image_url ?>" class="header-user-logo" alt="User Profile" />
                <i class="fa-solid fa-chevron-down profile-chevron"></i>
            </div>
            <div class="dropdown-menu-custom" id="userMenu">
                <a href="<?= base_url('Volunteer/profile') ?>" class="dropdown-item">
                    <i class="fa-solid fa-user-circle"></i> ملفي الشخصي
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?= base_url('Volunteer/logout') ?>" class="dropdown-item logout-item">
                    <i class="fa-solid fa-sign-out-alt"></i> تسجيل الخروج
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

        // Force light mode only for the volunteer portal.
        document.documentElement.removeAttribute('data-theme');
        localStorage.removeItem('theme');
    });
</script>
