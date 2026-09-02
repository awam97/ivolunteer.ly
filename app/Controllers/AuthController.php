<?php

namespace App\Controllers;
use App\Models\UserModel;
use CodeIgniter\Controller;

class AuthController extends Controller
{
    // Show Forgot Password Form
    public function forgotPassword()
    {
        return view('auth/forgot_password');
    }

    // Send OTP via WhatsApp
    public function sendOtp()
    {
        $phone = $this->request->getPost('phone');
        $userModel = new UserModel();
        $user = $userModel->where('phone', $phone)->first();

        if (!$user) {
            return redirect()->back()->with('error', 'Phone number not registered.');
        }

        $otp = rand(100000, 999999);
        session()->set('otp', $otp);
        session()->set('phone', $phone);

        // Send OTP via WhatsApp API
        $this->sendWhatsAppOtp($phone, $otp);

        return redirect()->to('/verify-otp')->with('success', 'OTP sent to WhatsApp.');
    }

    // Verify OTP
    public function verifyOtp()
    {
        $enteredOtp = $this->request->getPost('otp');
        $sessionOtp = session()->get('otp');

        if ($enteredOtp == $sessionOtp) {
            session()->set('otp_verified', true);
            return redirect()->to('/reset-password');
        } else {
            return redirect()->back()->with('error', 'Invalid OTP.');
        }
    }

    // Reset Password
    public function resetPassword()
    {
        if (!session()->get('otp_verified')) {
            return redirect()->to('/forgot-password')->with('error', 'Unauthorized access.');
        }

        $phone = session()->get('phone');
        $newPassword = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);

        $userModel = new UserModel();
        $userModel->where('phone', $phone)->set(['password' => $newPassword])->update();

        session()->remove(['otp', 'otp_verified', 'phone']);
        return redirect()->to('/login')->with('success', 'Password reset successful.');
    }

    // Function to Send OTP via WhatsApp
    private function sendWhatsAppOtp($phone, $otp)
    {
        $apiKey = 'YOUR_API_KEY_HERE'; // Replace with actual API key
        $message = "Your OTP is: $otp";

        // Example for Twilio API
        $url = "https://api.twilio.com/2010-04-01/Accounts/YOUR_ACCOUNT_SID/Messages.json";
        $data = [
            'To' => "whatsapp:$phone",
            'From' => "whatsapp:+YOUR_TWILIO_NUMBER",
            'Body' => $message
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, "YOUR_ACCOUNT_SID:YOUR_AUTH_TOKEN");
        curl_exec($ch);
        curl_close($ch);
    }
}
