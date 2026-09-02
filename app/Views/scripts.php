<script src="<?= base_url('style/bower_components/jquery/dist/jquery.min.js') ?>"></script>
<script src="<?= base_url('style/bootstrap/dist/js/bootstrap.min.js') ?>"></script>
<script src="<?= base_url('style/bower_components/sidebar-nav/dist/sidebar-nav.min.js') ?>"></script>
<script src="<?= base_url('style/js/jquery.slimscroll.js') ?>"></script>
<script src="<?= base_url('style/js/waves.js') ?>"></script>
<script src="<?= base_url('style/bower_components/raphael/raphael-min.js') ?>"></script>
<script src="<?= base_url('style/bower_components/morrisjs/morris.js') ?>"></script>
<script src="<?= base_url('style/bower_components/jquery-sparkline/jquery.sparkline.min.js') ?>"></script>
<script src="<?= base_url('style/bower_components/jquery-sparkline/jquery.charts-sparkline.js') ?>"></script>
<script src="<?= base_url('style/js/custom.min.js') ?>"></script>
<script src="<?= base_url('style/bower_components/gallery/js/animated-masonry-gallery.js') ?>"></script>
<script src="<?= base_url('style/bower_components/gallery/js/jquery.isotope.min.js') ?>"></script>
<script src="<?= base_url('style/bower_components/fancybox/ekko-lightbox.min.js') ?>"></script>
<script src="<?= base_url('style/js/jPlayer/jquery.jplayer.min.js') ?>"></script>
<script src="<?= base_url('style/js/jPlayer/add-on/jplayer.playlist.min.js') ?>"></script>
<script src="<?= base_url('style/js/dashboard1.js') ?>"></script>
<script src="<?= base_url('style/bower_components/switchery/dist/switchery.min.js') ?>"></script>
<script src="<?= base_url('style/bower_components/styleswitcher/jQuery.style.switcher.js') ?>"></script>
<script src="<?= base_url('style/bower_components/owl.carousel/owl.carousel.min.js') ?>"></script>
<script src="<?= base_url('style/bower_components/owl.carousel/owl.custom.js') ?>"></script>
<script src="<?= base_url('style/bower_components/bootstrap-datepicker/bootstrap-datepicker.min.js') ?>"></script>
<script src="<?= base_url('style/js/jasny-bootstrap.js') ?>"></script>
<script src="<?= base_url('style/bower_components/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('style/bower_components/html5-editor/wysihtml5-0.3.0.js') ?>"></script>
<script src="<?= base_url('style/bower_components/html5-editor/bootstrap-wysihtml5.js') ?>"></script>
<script src="<?= base_url('style/bower_components/toast-master/js/jquery.toast.js') ?>"></script>
<script src="<?= base_url('style/js/toastr.js') ?>"></script>


<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.flash.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>
<script type="text/javascript">
    jQuery('.mydatepicker').datepicker();

    // Global TinyMCE Helper for Dynamic AJAX Modals
    window.initTinyMCE = function(selector = 'textarea') {
        // Destroy existing instance to avoid conflicts on re-init
        tinymce.remove(selector);
        
        tinymce.init({
            selector: selector,
            height: 320,
            menubar: false,
            directionality: 'rtl', // Ensure Right-to-Left for Arabic
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | ' +
                     'bold italic underline | alignleft aligncenter ' +
                     'alignright alignjustify | bullist numlist outdent indent | ' +
                     'removeformat | code help',
            content_style: 'body { font-family: "Outfit", sans-serif; font-size: 14px; color: #444; direction: rtl; text-align: right; }',
            skin: 'oxide', // Oxide skin looks premium
            setup: function(editor) {
                editor.on('change', function() {
                    editor.save(); // Automatically sync to textarea on change
                });
            }
        });

    };


    // Global Notification Helper (Premium Admin Style)
    window.showNotification = function(type, message, heading = '') {
        const icons = {
            'success': 'success',
            'error': 'error',
            'warning': 'warning',
            'info': 'info'
        };

        const colors = {
            'success': '#304300', // Deep Executive Green
            'error': '#d33333',   // Alert Red
            'warning': '#f39c12', // Warning Orange
            'info': '#3498db'     // Info Blue
        };

        if (heading === '') {
            heading = type.charAt(0).toUpperCase() + type.slice(1);
            if (type === 'success') heading = 'نجاح العملية';
            if (type === 'error') heading = 'خطأ';
        }

        $.toast({
            heading: heading,
            text: message,
            position: 'top-right',
            loaderBg: colors[type] || '#304300',
            icon: icons[type] || 'info',
            hideAfter: 3500,
            stack: 6,
            showHideTransition: 'fade',
            loader: true
        });
    };

    // Auto-trigger from Flashdata
    <?php 
        $session = session();
        $notif = $session->getFlashdata('notification');
        if ($notif): 
    ?>
        $(document).ready(function() {
            window.showNotification('<?= $notif['type'] ?>', '<?= addslashes($notif['message']) ?>');
        });
    <?php endif; ?>

    /**
     * Iframe Height Communication
     * Sends the current page height to parent window for auto-resizing
     */
    window.sendHeightToParent = function() {
        // Calculate the actual content height
        const height = document.body.scrollHeight || document.documentElement.scrollHeight;
        
        // Only send if we are inside an iframe
        if (window.parent && window.parent !== window) {
            window.parent.postMessage({
                type: 'iframe_resize',
                height: height,
                id: 'volunteer_portal' // Identifier for the parent to target specific iframe
            }, '*');
        }
    };

    // Trigger on load and window resize
    $(window).on('load resize', function() {
        window.sendHeightToParent();
        // Delay a bit for any late-rendering components
        setTimeout(window.sendHeightToParent, 200);
    });

    // Watch for DOM mutations (AJAX content, collapses, etc.)
    if (window.MutationObserver) {
        const observer = new MutationObserver(window.sendHeightToParent);
        observer.observe(document.body, { 
            attributes: true, 
            childList: true, 
            subtree: true 
        });
    }
</script>