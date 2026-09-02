<?php 
$admin_row = $db->table('admin')->where('id', $admin_id)->get()->getRow();
$is_owner = $admin_row ? $admin_row->owner : 0;
?>
<div class="sidebar-nav">
    <div class="sidebar-header-actions">
        <i class="fa-solid fa-times sidebar-close" id="sidebarClose"></i>
        <button id="sidebarToggle" class="sidebar-toggle-btn" title="Toggle Sidebar">
            <i class="fa-solid fa-angle-left"></i>
        </button>
    </div>

    <div class="sidebar-brand">
        <a href="<?= base_url() ?>" class="brand-link">
            <img src="<?= base_url() ?>uploads/logo-color-1.png" alt="I-Volunteer Logo" class="sidebar-main-logo" onerror="this.src='<?= base_url() ?>uploads/user.jpg'"/>
        </a>
    </div>
    
    <div id="navMenu" class="sidebar-menu">
        <a href="<?= base_url('Admin/dashboard') ?>" class="nav-item <?= ($page_name == 'dashboard') ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-pie"></i> <span><?= $translate->translate_phrase('dashboard',$language);?></span>
        </a>
        
        <?php if($is_owner == 1): ?>
        <a href="<?= base_url('Admin/admins') ?>" class="nav-item <?= ($page_name == 'admins') ? 'active' : '' ?>">
            <i class="fa-solid fa-user-shield"></i> <span><?= $translate->translate_phrase('admins',$language);?></span>
        </a>
        <?php endif; ?>
        
        <a href="<?= base_url('Admin/cities') ?>" class="nav-item <?= ($page_name == 'cities') ? 'active' : '' ?>">
            <i class="fa-solid fa-map-location-dot"></i> <span><?= $translate->translate_phrase('cities',$language);?></span>
        </a>
        
        <a href="<?= base_url('Admin/activities') ?>" class="nav-item <?= ($page_name == 'activities') ? 'active' : '' ?>">
            <i class="fa-solid fa-calendar-check"></i> <span><?= $translate->translate_phrase('activities',$language);?></span>
        </a>
        
        <a href="<?= base_url('Admin/volunteers') ?>" class="nav-item <?= ($page_name == 'volunteers') ? 'active' : '' ?>">
            <i class="fa-solid fa-users"></i> <span><?= $translate->translate_phrase('volunteers',$language);?></span>
        </a>
        
        <a href="<?= base_url('Admin/volunteer_activities') ?>" class="nav-item <?= ($page_name == 'volunteer_activities') ? 'active' : '' ?>">
            <i class="fa-solid fa-handshake-angle"></i> <span><?= $translate->translate_phrase('volunteer_activities',$language);?></span>
        </a>
        
        <a href="<?= base_url('Admin/news') ?>" class="nav-item <?= ($page_name == 'news') ? 'active' : '' ?>">
            <i class="fa-solid fa-newspaper"></i> <span><?= $translate->translate_phrase('news',$language);?></span>
        </a>
        
        <a href="<?= base_url('Admin/library') ?>" class="nav-item <?= ($page_name == 'library') ? 'active' : '' ?>">
            <i class="fa-solid fa-photo-film"></i> <span><?= $translate->translate_phrase('library',$language);?></span>
        </a>
        
        <?php if($is_owner == 1): ?>
        <a href="<?= base_url('Admin/settings') ?>" class="nav-item <?= ($page_name == 'settings') ? 'active' : '' ?>">
            <i class="fa-solid fa-gears"></i> <span>إعدادات النظام</span>
        </a>
        <?php endif; ?>

        <a href="<?= base_url('Admin/profile') ?>" class="nav-item <?= ($page_name == 'profile') ? 'active' : '' ?>">
            <i class="fa-solid fa-user-cog"></i> <span><?= $translate->translate_phrase('profile',$language);?></span>
        </a>
    </div>
</div>