<?php $volunteer_name = $db->table('volunteers')->where('id', $volunteer_id)->get()->getRow()->name;?>
<div class="sidebar-nav">
    <div class="sidebar-header-actions">
        <i class="fa-solid fa-times sidebar-close" id="sidebarClose"></i>
        <button id="sidebarToggle" class="sidebar-toggle-btn" title="Toggle Sidebar">
            <i class="fa-solid fa-angle-left"></i>
        </button>
    </div>

    <div class="sidebar-brand">
        <a href="<?= base_url() ?>" class="brand-link">
            <img src="<?= base_url() ?>uploads/logo-white-1.png" alt="I-Volunteer Logo" class="sidebar-main-logo"/>
        </a>
    </div>
    
    <div id="navMenu" class="sidebar-menu">
        <a href="<?= base_url('Volunteer/dashboard') ?>" class="nav-item <?= ($page_name == 'dashboard') ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-pie"></i> <span>لوحة التحكم</span>
        </a>            
        <a href="<?= base_url('Volunteer/activities') ?>" class="nav-item <?= ($page_name == 'activities') ? 'active' : '' ?>">
            <i class="fa-solid fa-calendar-check"></i> <span>النشاطات المتاحة</span>
        </a>
        <a href="<?= base_url('Volunteer/my_activities') ?>" class="nav-item <?= ($page_name == 'my_activities') ? 'active' : '' ?>">
            <i class="fa-solid fa-list-check"></i> <span>نشاطاتي الخاصة</span>
        </a>
        <a href="<?= base_url('Volunteer/certificates') ?>" class="nav-item <?= ($page_name == 'certificates') ? 'active' : '' ?>">
            <i class="fa-solid fa-certificate"></i> <span>الشهادات</span>
        </a>
        <a href="<?= base_url('Volunteer/terms') ?>" class="nav-item <?= ($page_name == 'terms') ? 'active' : '' ?>">
            <i class="fa-solid fa-file-contract"></i> <span>الشروط و الأحكام</span>
        </a>
        <a href="<?= base_url('Volunteer/profile') ?>" class="nav-item <?= ($page_name == 'profile') ? 'active' : '' ?>">
            <i class="fa-solid fa-user"></i> <span>ملفي الشخصي</span>
        </a>
    </div>
</div>