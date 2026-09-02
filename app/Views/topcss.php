<link rel="stylesheet" href="<?= base_url('style/bower_components/bootstrap-rtl-master/dist/css/bootstrap-rtl.min.css') ?>" type="text/css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="<?= base_url('style/bower_components/sidebar-nav/dist/sidebar-nav.min.css') ?>" type="text/css">
<link rel="stylesheet" href="<?= base_url('style/bower_components/morrisjs/morris.css') ?>" type="text/css">
<link rel="stylesheet" href="<?= base_url('style/css/animate.css') ?>" type="text/css">
<link rel="stylesheet" href="<?= base_url('style/css/core.css') ?>?v=<?= filemtime(FCPATH . 'style/css/core.css') ?>" type="text/css">
<link rel="stylesheet" href="<?= base_url('style/css/typography.css') ?>?v=<?= filemtime(FCPATH . 'style/css/typography.css') ?>" type="text/css">

<link rel="stylesheet" href="<?= base_url('style/bower_components/owl.carousel/owl.carousel.min.css') ?>" type="text/css"/>
<link rel="stylesheet" href="<?= base_url('style/bower_components/toast-master/css/jquery.toast.css') ?>" type="text/css">
<link rel="stylesheet" href="<?= base_url('style/js/jPlayer/jplayer.flat.css') ?>" type="text/css"/>
<link rel="stylesheet" href="<?= base_url('style/bower_components/gallery/css/animated-masonry-gallery.css') ?>" type="text/css"/>
<link rel="stylesheet" href="<?= base_url('style/bower_components/owl.carousel/owl.theme.default.css') ?>" type="text/css"/>
<link rel="stylesheet" href="<?= base_url('style/bower_components/datatables/jquery.dataTables.min.css') ?>" type="text/css"/>
<link rel="stylesheet" href="<?= base_url('style/bower_components/fancybox/ekko-lightbox.min.css') ?>" type="text/css"/>
<link rel="stylesheet" href="<?= base_url('style/bower_components/bootstrap-datepicker/bootstrap-datepicker.min.css') ?>" type="text/css"/>
<link rel="stylesheet" href="<?= base_url('style/bower_components/html5-editor/bootstrap-wysihtml5.css') ?>" type="text/css"/>
<link rel="stylesheet" href="<?= base_url('style/bower_components/switchery/dist/switchery.min.css') ?>" type="text/css"/>
<link rel="stylesheet" href="<?= base_url('assets/js/dropzone/dropzone.css') ?>">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.2.2/css/buttons.dataTables.min.css" type="text/css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/searchpanes/1.2.1/css/searchPanes.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/select/1.3.3/css/select.dataTables.min.css">
<?php 
if (isset($page_name)) {
    $css_file = 'style/css/views/' . $page_name . '.css';
    if (is_file(FCPATH . $css_file)) {
        echo '<link rel="stylesheet" href="' . base_url($css_file) . '?v=' . filemtime(FCPATH . $css_file) . '" type="text/css">';
    }
}
if (isset($entityName)) {
    $entity_css = 'style/css/views/' . $entityName . '.css';
    if (is_file(FCPATH . $entity_css)) {
        echo '<link rel="stylesheet" href="' . base_url($entity_css) . '?v=' . filemtime(FCPATH . $entity_css) . '" type="text/css">';
    }
}
?>
