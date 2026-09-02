<link rel="stylesheet" href="<?= base_url('style/css/views/settings.css'); ?>">

<div class="settings-page-container">
    <div class="settings-grid">
        <div class="settings-section-card">
            <div class="settings-header">
                <i class="fa-solid fa-bell"></i>
                <h2>تكامل إشعارات الواتساب (WasenderAPI)</h2>
            </div>
            
            <!-- Status Toggle Row (Tri-State) -->
            <div class="settings-control-row">
                <div class="settings-info">
                    <h4>نظام الإشعارات</h4>
                    <p>حدد آلية إرسال الإشعارات للمتطوعين.</p>
                </div>
                
                <div class="tri-state-toggle-container">
                    <select id="whatsapp_enabled_select" class="form-control premium-select">
                        <option value="0" <?= (isset($settings['whatsapp_enabled']) && $settings['whatsapp_enabled'] == '0') ? 'selected' : '' ?>>إيقاف التشغيل (OFF)</option>
                        <option value="2" <?= (isset($settings['whatsapp_enabled']) && $settings['whatsapp_enabled'] == '2') ? 'selected' : '' ?>>وضع الاختبار فقط (TEST)</option>
                        <option value="1" <?= (isset($settings['whatsapp_enabled']) && $settings['whatsapp_enabled'] == '1') ? 'selected' : '' ?>>تفعيل للكل (ON)</option>
                    </select>
                </div>
            </div>

            <!-- Test Number Row (Conditionally Relevant) -->
            <div id="test_number_wrapper" class="test-number-field-row" style="<?= (isset($settings['whatsapp_enabled']) && $settings['whatsapp_enabled'] == '2') ? '' : 'display: none;' ?>">
                <div class="settings-info" style="margin-bottom: 12px;">
                    <label style="font-weight: 800; color: var(--primary); font-size: 14px;">رقم الهاتف المعتمد للاختبار</label>
                    <p style="font-size: 12px;">في وضع الاختبار، سيتم إرسال الإشعارات التلقائية <b>فقط</b> لهذا الرقم.</p>
                </div>
                <div class="api-key-input-container" style="max-width: none;">
                    <input type="text" id="whatsapp_test_number_input" class="form-control" value="<?= esc($whatsapp_test_number); ?>" placeholder="مثال: 921234567">
                    <i class="fa-solid fa-user-shield api-key-icon"></i>
                </div>
            </div>

            <!-- API Key Row -->
            <div class="api-key-wrapper">
                <div class="settings-info" style="margin-bottom: 15px;">
                    <h4>مفتاح API الخاص بالخدمة</h4>
                    <p>مفتاح الجلسة المستخدم للاتصال بخدمة WasenderAPI. يتم استخدامه بشكل تلقائي لجميع الرسائل الصادرة.</p>
                </div>
                
                <div class="api-key-input-container">
                    <input type="password" id="whatsapp_api_key_input" class="form-control" value="<?= esc($whatsapp_api_key); ?>" placeholder="أدخل مفتاح الـ API هنا...">
                    <i class="fa-solid fa-key api-key-icon"></i>
                </div>
            </div>

            <!-- Webhook Secret Row -->
            <div class="api-key-wrapper">
                <div class="settings-info" style="margin-bottom: 15px;">
                    <h4>مفتاح Webhook السري</h4>
                    <p>يُستخدم للتحقق من أن إشعارات Webhook الواردة من WasenderAPI أصلية.</p>
                </div>

                <div class="api-key-input-container">
                    <input type="password" id="whatsapp_webhook_secret_input" class="form-control" value="<?= esc($whatsapp_webhook_secret); ?>" placeholder="أدخل مفتاح Webhook السري هنا...">
                    <i class="fa-solid fa-shield-halved api-key-icon"></i>
                </div>
            </div>

            <div class="card-footer-actions" style="margin-top: 40px; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 25px;">
                <button type="button" id="saveWhatsappSettings" class="premium-save-btn" style="width: auto; min-width: 220px;">
                    <i class="fa-solid fa-floppy-disk"></i> حفظ إعدادات الواتساب
                </button>
            </div>
        </div>

        <!-- Section 2: Notification Controls -->
        <div class="settings-section-card">
            <div class="settings-header">
                <i class="fa-solid fa-toggle-on"></i>
                <h2>تفعيل / إيقاف حالات الإشعارات</h2>
            </div>
            
            <div class="alert alert-info" style="background: rgba(52, 152, 219, 0.1); border: none; border-right: 4px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                <p style="margin: 0; font-size: 13px; color: #2c3e50;">
                    <i class="fa-solid fa-circle-info"></i>
                    تنبيهات الإدارة يتم إرسالها تلقائياً إلى <b>جميع حسابات الإدارة</b> التي تمتلك رقم هاتف مسجل في المنظومة.
                </p>
            </div>

            <div class="notification-controls-grid">
                <!-- Registration -->
                <div class="control-group-box">
                    <h5><i class="fa-solid fa-user-plus"></i> عند التسجيل الجديد</h5>
                    <div class="switch-row">
                        <span>إرسال ترحيب للمتطوع</span>
                        <label class="ios-switch">
                            <input type="checkbox" class="wa-toggle" data-key="wa_reg_user" <?= (isset($settings['wa_reg_user']) && $settings['wa_reg_user'] == '1') ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="switch-row">
                        <span>إرسال تنبيه للإدارة</span>
                        <label class="ios-switch">
                            <input type="checkbox" class="wa-toggle" data-key="wa_reg_admin" <?= (isset($settings['wa_reg_admin']) && $settings['wa_reg_admin'] == '1') ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Enrollment -->
                <div class="control-group-box">
                    <h5><i class="fa-solid fa-file-signature"></i> عند طلب الانضمام لنشاط</h5>
                    <div class="switch-row">
                        <span>تأكيد استلام الطلب للمتطوع</span>
                        <label class="ios-switch">
                            <input type="checkbox" class="wa-toggle" data-key="wa_enroll_user" <?= (isset($settings['wa_enroll_user']) && $settings['wa_enroll_user'] == '1') ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="switch-row">
                        <span>إرسال تنبيه للإدارة</span>
                        <label class="ios-switch">
                            <input type="checkbox" class="wa-toggle" data-key="wa_enroll_admin" <?= (isset($settings['wa_enroll_admin']) && $settings['wa_enroll_admin'] == '1') ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Status Update -->
                <div class="control-group-box">
                    <h5><i class="fa-solid fa-sync"></i> عند تغيير حالة الطلب</h5>
                    <div class="switch-row">
                        <span>إرسال تحديث للمتطوع (قبول/رفض)</span>
                        <label class="ios-switch">
                            <input type="checkbox" class="wa-toggle" data-key="wa_status_user" <?= (isset($settings['wa_status_user']) && $settings['wa_status_user'] == '1') ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Certificate -->
                <div class="control-group-box">
                    <h5><i class="fa-solid fa-certificate"></i> عند جاهزية الشهادة</h5>
                    <div class="switch-row">
                        <span>إشعار المتطوع بجاهزية الشهادة</span>
                        <label class="ios-switch">
                            <input type="checkbox" class="wa-toggle" data-key="wa_cert_user" <?= (isset($settings['wa_cert_user']) && $settings['wa_cert_user'] == '1') ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <!-- OTP -->
                <div class="control-group-box">
                    <h5><i class="fa-solid fa-key"></i> عند طلب استعادة كلمة المرور</h5>
                    <div class="switch-row">
                        <span>إرسال رمز OTP للمتطوع</span>
                        <label class="ios-switch">
                            <input type="checkbox" class="wa-toggle" data-key="wa_otp_user" <?= (isset($settings['wa_otp_user']) && $settings['wa_otp_user'] == '1') ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="card-footer-actions" style="margin-top: 30px; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 25px;">
                <button type="button" id="saveNotificationToggles" class="premium-save-btn" style="width: auto; min-width: 220px; background: #2c3e50;">
                    <i class="fa-solid fa-check-double"></i> حفظ حالات الإرسال
                </button>
            </div>
        </div>

        <!-- Section 3: Service Testing -->
        <div class="settings-section-card">
            <div class="settings-header">
                <i class="fa-solid fa-vial"></i>
                <h2>تجرية الخدمة (Service Testing)</h2>
            </div>
            
            <div class="settings-info" style="margin-bottom: 20px;">
                <h4>إرسال رسالة تجربة</h4>
                <p>يمكنك إرسال رسالة تحقق للتأكد من ربط المفتاح بشكل صحيح. سيتم تجاوز وضع الإيقاف العام عند إجراء الاختبار.</p>
            </div>

            <div class="settings-control-row" style="background: rgba(48, 67, 0, 0.05); border-color: rgba(48, 67, 0, 0.1);">
                <div class="api-key-input-container" style="flex: 1; margin: 0; max-width: none;">
                    <input type="text" id="test_phone" class="form-control" placeholder="أدخل رقم الهاتف (مثال: 921234567)...">
                    <i class="fa-solid fa-phone api-key-icon"></i>
                </div>
                
                <button type="button" id="sendTestSms" class="premium-save-btn" style="width: auto; min-width: 180px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                    <i class="fa-solid fa-paper-plane"></i> إرسال تجربة
                </button>
            </div>

            <div id="testResultArea" style="margin-top: 20px; display: none;">
                <div id="testResultMessage" style="padding: 15px; border-radius: 12px; font-size: 14px; font-weight: 500;">
                    <!-- Result from AJAX -->
                </div>
            </div>
        </div>

        <!-- Automated Message Templates Section -->
        <div class="settings-section-card">
            <div class="settings-header">
                <i class="fa-solid fa-comment-dots"></i>
                <h2>قوالب الرسائل التلقائية</h2>
            </div>
            
            <p class="settings-description-text" style="margin-bottom: 25px; color: var(--text-muted); font-size: 14px;">تخصيص محتوى رسائل الواتساب التي يتم إرسالها تلقائياً. يمكنك استخدام <b>الوسوم</b> (الموجودة بين أقواس <code>{}</code>) ليتم استبدالها بالبيانات الفعلية عند الإرسال.</p>

            <div class="templates-container">
                <!-- Registration Template -->
                <div class="template-field-group">
                    <label>رسالة الترحيب عند التسجيل <span class="placeholder-hint">الوسوم: <code>{name}</code></span></label>
                    <textarea id="msg_registration" class="form-control template-textarea" rows="5" placeholder="اكتب رسالة الترحيب هنا..."><?= esc($settings['msg_registration'] ?? '') ?></textarea>
                </div>

                <!-- New Activity Template -->
                <div class="template-field-group">
                    <label>تنبيه بنشاط جديد <span class="placeholder-hint">الوسوم: <code>{activity_name}</code></span></label>
                    <textarea id="msg_new_activity" class="form-control template-textarea" rows="3" placeholder="اكتب رسالة التنبيه بالنشاط الجديد..."><?= esc($settings['msg_new_activity'] ?? '') ?></textarea>
                </div>

                <!-- Status 1: Approved -->
                <div class="template-field-group">
                    <label>إشعار الموافقة على الطلب <span class="placeholder-hint">الوسوم: <code>{activity_name}</code>, <code>{city_name}</code></span></label>
                    <textarea id="msg_status_1" class="form-control template-textarea" rows="4" placeholder="اكتب رسالة الموافقة..."><?= esc($settings['msg_status_1'] ?? '') ?></textarea>
                </div>

                <!-- Status 0: Pending -->
                <div class="template-field-group">
                    <label>إشعار وضع الطلب في الانتظار <span class="placeholder-hint">الوسوم: <code>{activity_name}</code></span></label>
                    <textarea id="msg_status_0" class="form-control template-textarea" rows="4" placeholder="اكتب رسالة الانتظار/المراجعة..."><?= esc($settings['msg_status_0'] ?? '') ?></textarea>
                </div>

                <!-- Status 2: Completed -->
                <div class="template-field-group">
                    <label>إشعار إتمام النشاط <span class="placeholder-hint">الوسوم: <code>{activity_name}</code></span></label>
                    <textarea id="msg_status_2" class="form-control template-textarea" rows="4" placeholder="اكتب رسالة إتمام النشاط..."><?= esc($settings['msg_status_2'] ?? '') ?></textarea>
                </div>

                <!-- Certificate Ready -->
                <div class="template-field-group">
                    <label>إشعار جاهزية الشهادة <span class="placeholder-hint">الوسوم: <code>{activity_name}</code></span></label>
                    <textarea id="msg_certificate" class="form-control template-textarea" rows="4" placeholder="اكتب رسالة جاهزية الشهادة..."><?= esc($settings['msg_certificate'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="card-footer-actions" style="margin-top: 30px; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 25px;">
                <button type="button" id="saveMessageTemplates" class="premium-save-btn" style="width: auto; min-width: 220px; background: var(--secondary);">
                    <i class="fa-solid fa-save"></i> حفظ جميع القوالب
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Executive Toggle Animations */
    .select-checkbox input:checked ~ span .toggle-dot {
        transform: translateX(30px);
    }
    
    .premium-select {
        height: 48px;
        border-radius: 12px;
        border: 1.5px solid rgba(0,0,0,0.08);
        padding: 0 15px;
        font-weight: 700;
        cursor: pointer;
        background: #f8f9fa;
        min-width: 180px;
    }

    .test-number-field-row {
        margin-top: 25px;
        padding: 20px;
        background: rgba(48, 67, 0, 0.04);
        border: 1px dashed var(--primary);
        border-radius: 16px;
    }

    .select-checkbox span::after { display: none !important; }

    .premium-save-btn {
        height: 52px;
        background: var(--primary);
        color: white;
        border-radius: var(--field-radius);
        border: none;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(48, 67, 0, 0.2);
    }

    .premium-save-btn:hover {
        transform: translateY(-2px);
        background: var(--primary-dark);
        box-shadow: 0 8px 16px rgba(48, 67, 0, 0.3);
    }

    .template-field-group {
        margin-bottom: 25px;
    }

    .template-field-group label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        font-weight: 700;
        color: var(--text-dark);
        font-size: 14px;
    }

    .placeholder-hint {
        font-size: 11px;
        font-weight: 400;
        color: var(--text-muted);
        background: rgba(0,0,0,0.03);
        padding: 4px 10px;
        border-radius: 6px;
    }

    .placeholder-hint code {
        color: var(--primary);
        font-weight: 700;
    }

    .template-textarea {
        border-radius: 12px;
        border: 1.5px solid rgba(0,0,0,0.08);
        padding: 15px;
        font-size: 14px;
        line-height: 1.6;
        transition: all 0.3s ease;
        resize: vertical;
        background: #fcfcfc;
    }

    .template-textarea:focus {
        border-color: var(--primary);
        background: white;
        box-shadow: 0 4px 12px rgba(48, 67, 0, 0.08);
    }

    /* iOS Switch Styling */
    .ios-switch {
        position: relative;
        display: inline-block;
        width: 46px;
        height: 24px;
    }
    .ios-switch input { opacity: 0; width: 0; height: 0; }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 34px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    input:checked + .slider { background-color: var(--primary); }
    input:focus + .slider { box-shadow: 0 0 1px var(--primary); }
    input:checked + .slider:before { transform: translateX(22px); }

    .notification-controls-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    .control-group-box {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 16px;
        border: 1px solid rgba(0,0,0,0.05);
    }
    .control-group-box h5 {
        margin-top: 0;
        margin-bottom: 15px;
        color: var(--primary);
        font-weight: 800;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .switch-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid rgba(0,0,0,0.03);
    }
    .switch-row:last-child { border-bottom: none; }
    .switch-row span { font-size: 13px; font-weight: 500; color: var(--text-dark); }
</style>

<script>
    $(document).ready(function() {
        // Toggle interaction feedback
        $('#whatsapp_enabled_select').on('change', function() {
            const val = $(this).val();
            if (val == '2') {
                $('#test_number_wrapper').slideDown(300);
            } else {
                $('#test_number_wrapper').slideUp(300);
            }
        });

        // Save All WhatsApp Settings
        $('#saveWhatsappSettings').on('click', function() {
            const btn = $(this);
            const originalHtml = btn.html();
            const statusMode = $('#whatsapp_enabled_select').val();
            const apiKey = $('#whatsapp_api_key_input').val().trim();
            const webhookSecret = $('#whatsapp_webhook_secret_input').val().trim();
            const testNumber = $('#whatsapp_test_number_input').val().trim();

            if (!apiKey) {
                showNotification('error', 'يرجى إدخال مفتاح API صالح');
                return;
            }

            if (statusMode == '2' && !testNumber) {
                showNotification('error', 'يرجى إدخال رقم الهاتف المعتمد لوضع الاختبار');
                return;
            }

            btn.html('<i class="fa-solid fa-spinner fa-spin"></i> جارٍ الحفظ...').prop('disabled', true);

            // Sequential Updates
            const updates = [
                { key: 'whatsapp_enabled', value: statusMode },
                { key: 'whatsapp_api_key', value: apiKey },
                { key: 'whatsapp_webhook_secret', value: webhookSecret },
                { key: 'whatsapp_test_number', value: testNumber }
            ];

            // Using Promise.all for clean parallel execution
            Promise.all(updates.map(update => {
                return $.ajax({
                    url: '<?= base_url('Admin/update_settings') ?>',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(update)
                });
            })).then(responses => {
                const allSuccess = responses.every(r => r.status === 'success');
                if (allSuccess) {
                    showNotification('success', 'تم حفظ جميع التغييرات بنجاح');
                } else {
                    showNotification('error', 'حدث خطأ في بعض التحديثات');
                }
            }).catch(err => {
                showNotification('error', 'فشل الاتصال بالخادم');
            }).finally(() => {
                btn.html(originalHtml).prop('disabled', false);
            });
        });

        // Save Message Templates
        $('#saveMessageTemplates').on('click', function() {
            const btn = $(this);
            const originalHtml = btn.html();
            
            const templates = [
                { key: 'msg_registration', value: $('#msg_registration').val().trim() },
                { key: 'msg_new_activity', value: $('#msg_new_activity').val().trim() },
                { key: 'msg_status_1', value: $('#msg_status_1').val().trim() },
                { key: 'msg_status_0', value: $('#msg_status_0').val().trim() },
                { key: 'msg_status_2', value: $('#msg_status_2').val().trim() },
                { key: 'msg_certificate', value: $('#msg_certificate').val().trim() }
            ];

            btn.html('<i class="fa-solid fa-spinner fa-spin"></i> جارٍ حفظ القوالب...').prop('disabled', true);

            Promise.all(templates.map(tpl => {
                return $.ajax({
                    url: '<?= base_url('Admin/update_settings') ?>',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(tpl)
                });
            })).then(responses => {
                const allSuccess = responses.every(r => r.status === 'success');
                if (allSuccess) {
                    showNotification('success', 'تم تحديث جميع القوالب بنجاح');
                } else {
                    showNotification('error', 'حدث خطأ أثناء حفظ بعض القوالب');
                }
            }).catch(err => {
                showNotification('error', 'فشل الاتصال بالخادم');
            }).finally(() => {
                btn.html(originalHtml).prop('disabled', false);
            });
        });

        // Save Notification Toggles
        $('#saveNotificationToggles').on('click', function() {
            const btn = $(this);
            const originalHtml = btn.html();
            const toggles = [];
            
            $('.wa-toggle').each(function() {
                toggles.push({
                    key: $(this).data('key'),
                    value: $(this).is(':checked') ? '1' : '0'
                });
            });

            btn.html('<i class="fa-solid fa-spinner fa-spin"></i> جارٍ الحفظ...').prop('disabled', true);

            Promise.all(toggles.map(t => {
                return $.ajax({
                    url: '<?= base_url('Admin/update_settings') ?>',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(t)
                });
            })).then(responses => {
                const allSuccess = responses.every(r => r.status === 'success');
                if (allSuccess) {
                    showNotification('success', 'تم تحديث حالات الإرسال بنجاح');
                } else {
                    showNotification('error', 'حدث خطأ أثناء الحفظ');
                }
            }).catch(err => {
                showNotification('error', 'فشل الاتصال بالخادم');
            }).finally(() => {
                btn.html(originalHtml).prop('disabled', false);
            });
        });

        // API Key input focus effect
        $('#whatsapp_api_key_input').on('focus', function() {
            $(this).attr('type', 'text');
        }).on('blur', function() {
            $(this).attr('type', 'password');
        });

        // SEND TEST MESSAGES
        $('#sendTestSms').on('click', function() {
            const btn = $(this);
            const originalHtml = btn.html();
            const phone = $('#test_phone').val().trim();
            const resultArea = $('#testResultArea');
            const resultMsg = $('#testResultMessage');

            if (!phone) {
                showNotification('error', 'يرجى إدخال رقم هاتف صالح للاختبار');
                return;
            }

            btn.html('<i class="fa-solid fa-spinner fa-spin"></i> جارٍ الإرسال...').prop('disabled', true);
            resultArea.hide();

            $.ajax({
                url: '<?= base_url('Admin/test_whatsapp') ?>',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ phone: phone }),
                success: function(response) {
                    resultArea.fadeIn();
                    if (response.status === 'success') {
                        resultMsg.css({
                            'background': 'rgba(48, 67, 0, 0.1)',
                            'color': 'var(--primary)',
                            'border': '1px solid rgba(48, 67, 0, 0.2)'
                        }).html('<i class="fa-solid fa-circle-check"></i> ' + response.message + ' <br><small>API Response: ' + response.api_response + '</small>');
                    } else {
                        resultMsg.css({
                            'background': 'rgba(239, 68, 68, 0.1)',
                            'color': '#ef4444',
                            'border': '1px solid rgba(239, 68, 68, 0.2)'
                        }).html('<i class="fa-solid fa-circle-exclamation"></i> ' + response.message + ' <br><small>API Error: ' + response.api_response + '</small>');
                    }
                },
                error: function() {
                    showNotification('error', 'فشل الاتصال بالخادم');
                },
                complete: function() {
                    btn.html(originalHtml).prop('disabled', false);
                }
            });
        });
    });
</script>
