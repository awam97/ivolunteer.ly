<div class="auth-page-wrapper">
    <!-- Decorative Elements -->
    <div class="auth-orb orb-blue"></div>
    <div class="auth-orb orb-emerald"></div>

    <div class="auth-container wide">
        <div class="auth-card">
            <div class="auth-header">
                <a href="https://i-volunteer.ly">
                    <img src="<?= base_url('uploads/logo-color-1.png') ?>" class="auth-logo-img" alt="Logo">
                </a>
                <h1 class="auth-title">إنشاء حساب متطوع</h1>
                <p class="auth-subtitle">انضم إلينا وكن جزءاً من التغيير الإيجابي</p>
            </div>

            <div class="wizard-steps">
                <div class="step-item active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-label">الحساب</div>
                </div>
                <div class="step-item" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-label">الشخصية</div>
                </div>
                <div class="step-item" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-label">التفاصيل</div>
                </div>
            </div>

            <form class="auth-form" method="POST" id="volunteersAdd">
                <!-- Step 1: Account Details -->
                <div class="auth-step active" data-step="1">
                    <div class="auth-row">
                        <div class="auth-col-6">
                            <div class="auth-group">
                                <label class="auth-label">اسم المستخدم</label>
                                <div class="auth-input-wrapper">
                                    <input type="text" class="auth-control" name="username" id="username" placeholder="مثال: m.ali88" required>
                                    <i class="fa-solid fa-at"></i>
                                </div>
                            </div>
                        </div>
                        <div class="auth-col-6">
                            <div class="auth-group">
                                <label class="auth-label">رقم الواتساب</label>
                                <div class="auth-input-wrapper">
                                    <input type="phone" class="auth-control" name="phone" id="phone" placeholder="9xxxxxxxx" required>
                                    <i class="fa-brands fa-whatsapp"></i>
                                </div>
                            </div>
                        </div>
                        <div class="auth-col-6">
                            <div class="auth-group">
                                <label class="auth-label">البريد الإلكتروني (اختياري)</label>
                                <div class="auth-input-wrapper">
                                    <input type="email" class="auth-control" name="email" id="email" placeholder="example@mail.com">
                                    <i class="fa-solid fa-envelope"></i>
                                </div>
                            </div>
                        </div>
                        <div class="auth-col-6">
                            <div class="auth-group">
                                <label class="auth-label">كلمة المرور</label>
                                <div class="auth-input-wrapper">
                                    <input type="password" class="auth-control" name="password" id="password" placeholder="••••••••" required>
                                    <i class="fa-solid fa-lock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="wizard-actions">
                        <button type="button" class="btn-wizard-next auth-btn-primary">التالي <i class="fa-solid fa-arrow-left"></i></button>
                    </div>
                </div>

                <!-- Step 2: Personal Information -->
                <div class="auth-step" data-step="2">
                    <div class="auth-row">
                        <div class="auth-col-6">
                            <div class="auth-group">
                                <label class="auth-label">الإسم الثلاثي</label>
                                <div class="auth-input-wrapper">
                                    <input type="text" class="auth-control" name="name" id="name" placeholder="مثال: محمد علي أحمد" required>
                                    <i class="fa-solid fa-user"></i>
                                </div>
                            </div>
                        </div>
                        <div class="auth-col-6">
                            <div class="auth-group">
                                <label class="auth-label">التعريف الشخصي / الرقم الوطني</label>
                                <div class="auth-input-wrapper">
                                    <input type="text" class="auth-control" name="identity" id="identity" placeholder="1xxxxxxxxxxxx">
                                    <i class="fa-solid fa-id-card"></i>
                                </div>
                            </div>
                        </div>
                        <div class="auth-col-6">
                            <div class="auth-group">
                                <label class="auth-label">تاريخ الميلاد</label>
                                <div class="auth-input-wrapper">
                                    <input type="date" class="auth-control" name="birthdate" id="birthdate" required>
                                    <i class="fa-solid fa-calendar-days"></i>
                                </div>
                            </div>
                        </div>
                        <div class="auth-col-6">
                            <div class="auth-group">
                                <label class="auth-label">الجنس</label>
                                <div class="auth-input-wrapper">
                                    <select class="auth-control" name="gender" id="gender" required>
                                        <option value="" disabled selected>الجنس</option>
                                        <?php foreach ($genders as $row): ?>
                                            <option value="<?= $row->id; ?>"><?= $row->name; ?></option>
                                        <?php endforeach; ?>                 
                                    </select>
                                    <i class="fa-solid fa-venus-mars"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="wizard-actions">
                        <button type="button" class="btn-wizard-prev"><i class="fa-solid fa-arrow-right"></i> السابق</button>
                        <button type="button" class="btn-wizard-next auth-btn-primary">التالي <i class="fa-solid fa-arrow-left"></i></button>
                    </div>
                </div>

                <!-- Step 3: Professional Details -->
                <div class="auth-step" data-step="3">
                    <div class="auth-row">
                        <div class="auth-col-6">
                            <div class="auth-group">
                                <label class="auth-label">المدينة</label>
                                <div class="auth-input-wrapper">
                                    <select class="auth-control" name="city_id" id="city_id" required>
                                        <option value="" disabled selected>اختر المدينة</option>
                                        <?php foreach ($cities as $row): ?>
                                            <option value="<?= $row->id; ?>"><?= $row->name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class="fa-solid fa-city"></i>
                                </div>
                            </div>
                        </div>
                        <div class="auth-col-6">
                            <div class="auth-group">
                                <label class="auth-label">عنوان السكن بالتفصيل</label>
                                <div class="auth-input-wrapper">
                                    <input type="text" class="auth-control" name="address" id="address" placeholder="مثال: حي الأندلس، طرابلس" required>
                                    <i class="fa-solid fa-location-dot"></i>
                                </div>
                            </div>
                        </div>
                        <div class="auth-col-6">
                            <div class="auth-group">
                                <label class="auth-label">المؤهل العلمي / التخصص</label>
                                <div class="auth-input-wrapper">
                                    <input type="text" class="auth-control" name="academic_value" id="academic_value" placeholder="مثال: هندسة برمجيات">
                                    <i class="fa-solid fa-graduation-cap"></i>
                                </div>
                            </div>
                        </div>
                        <div class="auth-col-6">
                            <div class="auth-group">
                                <label class="auth-label">الهوايات أو المهارات</label>
                                <div class="auth-input-wrapper">
                                    <input type="text" class="auth-control" name="hobbies" id="hobbies" placeholder="مثال: التصوير الفوتوغرافي، البرمجة">
                                    <i class="fa-solid fa-heart"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="wizard-actions">
                        <button type="button" class="btn-wizard-prev"><i class="fa-solid fa-arrow-right"></i> السابق</button>
                        <button type="submit" class="auth-btn-primary"><i class="fa-solid fa-user-plus"></i> إتمام التسجيل</button>
                    </div>
                </div>

                <div class="auth-divider"></div>

                <div class="auth-footer">
                    <p style="color: #64748b; font-size: 14px; margin-bottom: 10px;">لديك حساب بالفعل؟</p>
                    <a href="<?= base_url('login') ?>" class="auth-link" style="font-size: 16px;">
                        <i class="fa-solid fa-right-to-bracket"></i> تسجيل الدخول للمنظومة
                    </a>
                    
                    <div style="margin-top: 25px;">
                        <a href="https://i-volunteer.ly" class="auth-secondary-btn">
                            <i class="fa-solid fa-house"></i> العودة للرئيسية
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Conditions Popup -->
<div id="conditionsPopup" class="popup-overlay">
    <div class="popup-box">
        <h3>🔖 اللائحة التنظيمية لمنصة "أنا متطوع"</h3>
        <div class="popup-scroll-text">
            <?php include(APPPATH . 'Views/terms_content.php'); ?>
            <hr>
            <p style="text-align: center;"><a href="<?= base_url('terms') ?>" target="_blank" style="color: var(--auth-secondary); font-weight: 700; text-decoration: underline;">فتح اللائحة في صفحة منفصلة</a></p>
        </div>

        <div class="popup-buttons">
            <a id="acceptConditions" class="btn btn-info" style="background: var(--auth-secondary); border: none; padding: 12px 40px; border-radius: 12px; font-weight: 800;">موافق</a>
            <a href="https://i-volunteer.ly" id="declineConditions" class="btn btn-danger" style="padding: 12px 40px; border-radius: 12px; font-weight: 800;">رفض</a>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () 
    {
        $('#conditionsPopup').css('display', 'flex').hide().fadeIn();
        $('#acceptConditions').on('click', function () {
            $('#conditionsPopup').fadeOut();
        });

        // Wizard Navigation
        let currentStep = 1;
        const totalSteps = 3;

        function updateWizard() {
            $('.auth-step').removeClass('active');
            $(`.auth-step[data-step="${currentStep}"]`).addClass('active');
            
            $('.step-item').removeClass('active completed');
            $('.step-item').each(function() {
                const step = $(this).data('step');
                if (step === currentStep) $(this).addClass('active');
                if (step < currentStep) $(this).addClass('completed');
            });
        }

        $('.btn-wizard-next').on('click', function() {
            const $currentStepEl = $(`.auth-step[data-step="${currentStep}"]`);
            let isValid = true;
            
            // Basic validation for current step
            $currentStepEl.find('input[required], select[required]').each(function() {
                if (!$(this).val()) {
                    $(this).addClass('is-invalid');
                    isValid = false;
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (isValid && currentStep < totalSteps) {
                currentStep++;
                updateWizard();
            }
        });

        $('.btn-wizard-prev').on('click', function() {
            if (currentStep > 1) {
                currentStep--;
                updateWizard();
            }
        });

        const entityName = 'volunteers';                                                
        const Registerform = `#${entityName}Add`;

        $(Registerform).on('submit', function (e) {
            e.preventDefault();
            const formData = {};
            $(`${Registerform} input, ${Registerform} select, ${Registerform} textarea`).each(function () {
                const fieldName = $(this).attr('name');
                const fieldValue = $(this).val();                                                                                                        
                if (fieldName) formData[fieldName] = fieldValue;                
            });
            
            $.ajax({
                url: '<?= base_url("Home/verify_register") ?>',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    table: entityName,
                    fields_entity: formData
                }),
                success: function (response) {
                    if (response.status === 'success') {
                        window.location.href = '<?= base_url("Home/success_register") ?>';
                    } else {
                        alert(response.message);
                    }
                },
                error: function (xhr) {
                    alert('An error occurred. Please try again.');
                }
            });
        });                          
    });    
</script>
