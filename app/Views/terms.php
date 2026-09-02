<div class="login-panel">
    <div class="login-card" style="max-width: 800px;">
        <center>
            <?php include(APPPATH . 'Views/generalheader.php'); ?>                                                                       
            <div class="box-title">اللائحة التنظيمية لمنصة "أنا متطوع"</div>                        
        </center>
        
        <div class="terms-content" style="margin-top: 30px; text-align: right; direction: rtl; line-height: 1.8; color: var(--text-color);">
            <?php include(APPPATH . 'Views/terms_content.php'); ?>
        </div>

        <div class="footer-links" style="margin-top: 40px; border-top: 1px solid #eee; padding-top: 25px;">
            <a href="<?= base_url('register') ?>" class="login-button btn btn-info btn-lg" style="width: auto; padding: 0 40px;">
                العودة للتسجيل
            </a>
        </div>
    </div>
</div>
