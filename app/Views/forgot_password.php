<div class="login-panel">
    <!-- Decorative Background Orbs -->
    <div class="login-orb orb-1"></div>
    <div class="login-orb orb-2"></div>
    <div class="login-orb orb-3"></div>

    <div class="login-card">
        <div class="text-center">
            <div class="header-logo">
                <?php include(APPPATH . 'Views/generalheader.php'); ?>
            </div>
            <h1 class="box-title"><?= $translate->translate_phrase($page_title, $language); ?></h1>
        </div>

        <form class="login-form" method="POST" id="form_login" action="<?= base_url('send_otp') ?>">
            
            <div class="auth-input-group">
                <input type="text" class="form-control" name="phone" id="phone" 
                       placeholder="رقم الهاتف المرتبط بالحساب" 
                       autocomplete="off" required>
                <i class="fa-solid fa-phone"></i>
            </div>

            <button type="submit" class="login-button btn btn-info btn-lg">
                <i class="fa-solid fa-paper-plane"></i> طلب رمز التحقق
            </button>

            <div class="text-center">
                <a href="<?= base_url('login') ?>" class="forgot-password-link">
                    العودة لتسجيل الدخول
                </a>
            </div>
        </form>
    </div>
</div>
