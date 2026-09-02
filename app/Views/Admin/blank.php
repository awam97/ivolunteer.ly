<html style="background-color:transparent">
    <head>        
        <meta charset="UTF-8">
        <meta name="description" content="The small framework with powerful features">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="shortcut icon" type="image/png" href="uploads/logo.ico">
        <title><?= $page_title;?></title>        
        <script type="text/javascript">var baseurl = '<?php echo base_url();?>';</script>
    </head>
    <body dir="rtl" style="background-color:transparent">                                               
        <?php include(APPPATH . 'Views/topcss.php'); ?>
        <div id="wrapper" style="background-color:transparent">
            <div class="container-fluid" style="background-color:transparent">
                <div class="col-md-12">                     
                    <div class="col-md-12" style="background-color:transparent">                                                                          
                        <?php if($page_name=='login')
                        {
                            include(APPPATH . 'Views/login.php'); 
                        }
                        else
                        {
                             
                            include(APPPATH . 'Views/Admin/'.$page_name.'.php'); 
                        ;}?>
                    </div>    
                      
                </div>                   
            </div>
        </div>                
    </body>
</html>