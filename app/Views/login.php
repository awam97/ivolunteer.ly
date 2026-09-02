<div class="auth-page-wrapper">
    <!-- Decorative Elements -->
    <div class="auth-orb orb-blue"></div>
    <div class="auth-orb orb-emerald"></div>

    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <a href="https://i-volunteer.ly">
                    <img src="<?= base_url('uploads/logo-color-1.png') ?>" class="auth-logo-img" alt="Logo">
                </a>
                <h1 class="auth-title">تسجيل الدخول</h1>
                <p class="auth-subtitle">مرحباً بك مجدداً في منصة "أنا متطوع"</p>
            </div>

            <form class="auth-form" method="POST" id="form_login" action="<?= base_url('verify_login') ?>">
                
                <div class="auth-group">
                    <label class="auth-label">اسم المستخدم / البريد</label>
                    <div class="auth-input-wrapper">
                        <input type="text" class="auth-control" name="user" id="user" 
                               placeholder="أدخل اسم المستخدم أو البريد" required>
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>

                <div class="auth-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label class="auth-label" style="margin-bottom: 0;">كلمة المرور</label>
                        <a href="<?= base_url('forgot_password') ?>" class="auth-link" style="font-size: 13px;">نسيت كلمة المرور؟</a>
                    </div>
                    <div class="auth-input-wrapper">
                        <input type="password" class="auth-control" name="password" id="password" 
                               placeholder="أدخل كلمة المرور الخاصة بك" required>
                        <i class="fa-solid fa-lock"></i>
                    </div>
                </div>

                <button type="submit" class="auth-btn-primary">
                    <i class="fa-solid fa-right-to-bracket"></i> دخول للمنصة
                </button>

                <div class="auth-divider"></div>

                <div class="auth-footer">
                    <p style="color: #64748b; font-size: 14px; margin-bottom: 15px;">ليس لديك حساب حتى الآن؟</p>
                    <a href="<?= base_url('register') ?>" class="auth-link" style="font-size: 17px; display: block; padding: 12px; border: 2px solid var(--auth-secondary); border-radius: 16px;">
                        <i class="fa-solid fa-user-plus"></i> إنشاء حساب متطوع جديد
                    </a>
                    
                    <a href="https://i-volunteer.ly" class="auth-secondary-btn" style="margin-top: 25px;">
                        <i class="fa-solid fa-house"></i> العودة للرئيسية
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
