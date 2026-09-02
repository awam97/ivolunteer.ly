<html>
    <head>        
        <meta charset="UTF-8">
        <meta name="description" content="The small framework with powerful features">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="shortcut icon" type="image/png" href="uploads/logo.ico">
        <title><?= $page_title;?></title>        
        <script type="text/javascript">var baseurl = '<?php echo base_url();?>';</script>                
        <?php include(APPPATH . 'Views/topcss.php'); ?>        
        <?php include(APPPATH . 'Views/scripts.php'); ?> 
    </head>
    <body class="normal-body" dir="rtl">                                                               
        <div id="wrapper" class="app-wrapper">
            <aside class="sidebar-wrapper" id="sidebar">
                <?php include(APPPATH . 'Views/Volunteer/navigation.php'); ?>  
            </aside>
            <div class="main-content-wrapper">
                <?php include(APPPATH . 'Views/Volunteer/header.php'); ?>
                <main class="content-area">
                    <div class="page-title-box">
                        <nav class="breadcrumb-trail">
                            <a href="<?= base_url('Volunteer/dashboard') ?>" class="breadcrumb-link">الرئيسية</a>
                            <span class="breadcrumb-sep"><i class="fa-solid fa-chevron-left"></i></span>
                            <span class="breadcrumb-current"><?= $translate->translate_phrase($page_title, $language); ?></span>
                        </nav>
                    </div>
                    <div class="content-container">
                        <?php include(APPPATH . 'Views/Volunteer/'.$page_name.'.php'); ?>                        
                    </div>
                    <?php include(APPPATH . 'Views/generalfooter.php'); ?>  
                </main>
            </div>
        </div>
        <script>
        function closeManagementModal() {
            $('#managementModal').fadeOut();
            $('body').removeClass('modal-open');
        }
        
        // Ensure clicking overlay closes modal
        $('#managementModal').on('click', function(e) {
            if (e.target === this) closeManagementModal();
        });

        // Sidebar Toggle & Collapse Logic (Premium Admin Style)
        $(document).ready(function() {
            const sidebar = $('#sidebar');
            const wrapper = $('#wrapper');
            const storageKey = 'sidebar-state-volunteer'; // Separate key for volunteer

            // Load saved state (Expanded by default)
            const savedState = localStorage.getItem(storageKey);
            if (savedState === 'collapsed') {
                sidebar.addClass('collapsed');
                wrapper.addClass('sidebar-collapsed');
            }

            // Desktop Collapse Toggle
            $('#sidebarToggle').on('click', function() {
                sidebar.toggleClass('collapsed');
                wrapper.toggleClass('sidebar-collapsed');
                
                // Toggle Icon Direction
                const icon = $(this).find('i');
                if (sidebar.hasClass('collapsed')) {
                    icon.removeClass('fa-angle-left').addClass('fa-angle-right');
                    localStorage.setItem(storageKey, 'collapsed');
                } else {
                    icon.removeClass('fa-angle-right').addClass('fa-angle-left');
                    localStorage.setItem(storageKey, 'expanded');
                }
            });

            // Mobile Hamburger Toggle (Triggers from header.php)
            $(document).on('click', '#mobileMenuBtn', function() {
                sidebar.addClass('mobile-expanded');
            });

            $(document).on('click', '#sidebarClose', function() {
                sidebar.removeClass('mobile-expanded');
            });

            // Close mobile sidebar on outside click
            $(document).on('click', function(e) {
                if ($(window).width() < 992) {
                    if (!sidebar.is(e.target) && sidebar.has(e.target).length === 0 && !$('#mobileMenuBtn').is(e.target) && $('#mobileMenuBtn').has(e.target).length === 0) {
                        sidebar.removeClass('mobile-expanded');
                    }
                }
            });
        });
    </script>
    </body>
</html>