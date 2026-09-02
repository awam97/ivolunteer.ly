<html lang="ar">
<head>        
    <meta charset="UTF-8">
    <meta name="description" content="I-Volunteer Administrative Portal">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="<?= base_url(); ?>uploads/logo.ico">
    <title><?= $page_title; ?></title>        
    <script type="text/javascript">var baseurl = '<?= base_url(); ?>';</script>
    <?php include(APPPATH . 'Views/topcss.php'); ?>
    <?php include(APPPATH . 'Views/scripts.php'); ?>
</head>
<body dir="rtl" class="auth-body">                                               
    <main id="auth-container">
        <?php include(APPPATH . 'Views/' . $page_name . '.php'); ?>
    </main>                
</body>
</html>