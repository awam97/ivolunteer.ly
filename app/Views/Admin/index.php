<!DOCTYPE html>
<html lang="<?= $language ;?>">
<head>        
    <meta charset="UTF-8">
    <meta name="description" content="The small framework with powerful features">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="<?= base_url(); ?>uploads/logo.ico">
    <title><?= $page_title; ?></title>        
    <script type="text/javascript">var baseurl = '<?= base_url(); ?>';</script>                
    <?php include(APPPATH . 'Views/topcss.php'); ?>  
    <?php include(APPPATH . 'Views/scripts.php'); ?> 
</head>
<body class="normal-body" dir="<?php if($language == 'en'){echo 'ltr';}else{echo 'rtl';}?>">
    <div class="preloader">
        <div class="cssload-speeding-wheel"></div>
    </div>                                                               
    <div id="wrapper" class="app-wrapper">
        <aside class="sidebar-wrapper" id="sidebar">
            <?php include(APPPATH . 'Views/Admin/navigation.php'); ?>
        </aside>
        <div class="main-content-wrapper">
            <?php include(APPPATH . 'Views/Admin/header.php'); ?>
            <main class="content-area">
                <div class="page-title-box">
                    <nav class="breadcrumb-trail">
                        <a href="<?= base_url('Admin/dashboard') ?>" class="breadcrumb-link">الرئيسية</a>
                        <span class="breadcrumb-sep"><i class="fa-solid fa-chevron-left"></i></span>
                        <span class="breadcrumb-current"><?= $translate->translate_phrase($page_title, $language); ?></span>
                    </nav>
                </div>
                <div class="content-container" id="contentContainer">
                    <?php include(APPPATH . 'Views/Admin/' . $page_name . '.php'); ?>
                </div>
                <?php include(APPPATH . 'Views/generalfooter.php'); ?>
            </main>
        </div>
    </div>                
    
    <!-- Global Management Modal -->
    <div id="managementModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2 id="modalTitle">إضافة جديد</h2>
                <button class="modal-close" onclick="closeManagementModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- AJAX Content Loads Here -->
            </div>
        </div>
    </div>

    <script>
        function closeManagementModal() {
            // Remove all active editors when closing modal
            if (typeof tinymce !== "undefined") tinymce.remove();
            
            $('#managementModal').fadeOut();
            $('body').removeClass('modal-open');
        }

        
        // Ensure clicking overlay closes modal
        $('#managementModal').on('click', function(e) {
            if (e.target === this) closeManagementModal();
        });
    </script>
</body>
</html>
<script>
    // Sidebar Toggle Logic
    $(document).ready(function() {
        const sidebar = $('#sidebar');
        const wrapper = $('#wrapper');
        const storageKey = 'sidebar-state';

        // Load saved state
        const savedState = localStorage.getItem(storageKey);
        if (savedState === 'collapsed') {
            sidebar.addClass('collapsed');
            wrapper.addClass('sidebar-collapsed');
        }

        // Desktop Toggle
        $('#sidebarToggle').on('click', function() {
            sidebar.toggleClass('collapsed');
            wrapper.toggleClass('sidebar-collapsed');
            
            // Toggle icon
            const icon = $(this).find('i');
            if (sidebar.hasClass('collapsed')) {
                icon.removeClass('fa-angle-left').addClass('fa-angle-right');
                localStorage.setItem(storageKey, 'collapsed');
            } else {
                icon.removeClass('fa-angle-right').addClass('fa-angle-left');
                localStorage.setItem(storageKey, 'expanded');
            }
        });

        // Mobile Hamburger Toggle
        $('#mobileMenuBtn').on('click', function() {
            sidebar.addClass('mobile-expanded');
        });

        $('#sidebarClose').on('click', function() {
            sidebar.removeClass('mobile-expanded');
        });

        // Close mobile sidebar on overlay click
        $(document).on('click', function(e) {
            if ($(window).width() < 992) {
                if (!sidebar.is(e.target) && sidebar.has(e.target).length === 0 && !$('#mobileMenuBtn').is(e.target) && $('#mobileMenuBtn').has(e.target).length === 0) {
                    sidebar.removeClass('mobile-expanded');
                }
            }
        });
    });

    window.addEventListener('load', () => {
      const preloader = document.querySelector('.preloader');
      setTimeout(() => {
        preloader.style.transition = 'opacity 0.5s ease';
        preloader.style.opacity = '0';
        setTimeout(() => {
          preloader.style.display = 'none';
        }, 500);
      }, 500);
    });
</script>
