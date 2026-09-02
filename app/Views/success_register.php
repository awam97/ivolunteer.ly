<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تم التسجيل بنجاح - أنا متطوع</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --auth-primary: #304300;
            --auth-secondary: #87A052;
            --auth-accent: #daac18;
            --auth-bg-gradient: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            --auth-card-bg: rgba(255, 255, 255, 0.85);
            --auth-radius: 24px;
        }

        body {
            margin: 0;
            font-family: 'Tajawal', sans-serif;
            background: var(--auth-bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .auth-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            z-index: 1;
            opacity: 0.4;
            animation: float 20s infinite alternate;
        }

        .orb-1 { width: 400px; height: 400px; background: var(--auth-secondary); top: -100px; right: -50px; }
        .orb-2 { width: 300px; height: 300px; background: var(--auth-accent); bottom: -50px; left: -50px; animation-delay: -5s; }

        @keyframes float { from { transform: translate(0, 0); } to { transform: translate(30px, 50px); } }

        .container {
            position: relative;
            z-index: 10;
            background: var(--auth-card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 60px 50px;
            border-radius: var(--auth-radius);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
            border: 1px solid rgba(255, 255, 255, 0.5);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .success-icon {
            font-size: 90px;
            color: var(--auth-accent);
            margin-bottom: 30px;
            filter: drop-shadow(0 10px 15px rgba(16, 185, 129, 0.2));
            animation: scaleIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes scaleIn { from { transform: scale(0); } to { transform: scale(1); } }

        h1 {
            color: var(--auth-primary);
            font-size: 32px;
            font-weight: 900;
            margin-bottom: 15px;
        }

        p {
            color: #64748b;
            font-size: 17px;
            line-height: 1.8;
            margin-bottom: 40px;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 18px 40px;
            background: var(--auth-primary);
            color: white;
            text-decoration: none;
            border-radius: 16px;
            font-weight: 800;
            font-size: 18px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(30, 58, 138, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(30, 58, 138, 0.3);
            background: #172554;
        }
    </style>
</head>
<body>
    <div class="auth-orb orb-1"></div>
    <div class="auth-orb orb-2"></div>

    <div class="container">
        <div class="success-icon">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <h1>تم التسجيل بنجاح !</h1>
        <p>مرحباً بك في مجتمعنا! 🎉<br>تم إنشاء حسابك بنجاح في <b>منصة أنا متطوع</b>.<br>يمكنك الآن تسجيل الدخول والبدء في رحلة التطوع.</p>
        <a href="<?= base_url('login') ?>" class="btn-primary">
            <i class="fa-solid fa-right-to-bracket"></i>
            تسجيل الدخول للحساب
        </a>
    </div>
</body>
</html>
