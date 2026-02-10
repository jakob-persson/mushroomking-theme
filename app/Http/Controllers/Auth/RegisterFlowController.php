<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegisterFlowController extends Controller
{
    private array $reservedSlugs = [
        'login','register','insights','forgot-password','wp-admin','wp-login.php',
        'create-account','dashboard','profile','settings','adventures','u'
    ];

    private function regClosed(): bool
    {
        $max = (int) config('services.registration.max_users', 0);
        if ($max <= 0) return false;
        return User::count() >= $max;
    }

    public function show(Request $request)
    {
        $step = $request->session()->get('reg.step', '1');

        // Om session saknar pre-reqs: backa
        if ($step === '1.5' && !$request->session()->get('reg.email')) $step = '1';
        if ($step === '2' && !$request->session()->get('reg.email_verified')) $step = '1';
        if ($step === '3' && !$request->session()->get('reg.password_plain')) $step = '2';

        return view('auth.create-account', [
            'step' => $step,
            'regClosed' => $this->regClosed(),
            'error' => session('reg.error'),
            'success' => session('reg.success'),
        ]);
    }

    public function post(Request $request)
    {
        $step = $request->input('step', '1');

        // Blockera allt om stängt
        if ($this->regClosed()) {
            return back()->with('reg.error', 'Registration is closed. Max test accounts reached.');
        }

        // STEP 1: send code
        if ($step === '1' && $request->has('send_code')) {
            $data = $request->validate([
                'email' => ['required','email','max:255','unique:users,email'],
            ]);

            $email = strtolower(trim($data['email']));

            $code = random_int(100000, 999999);
            $request->session()->put('reg.email', $email);
            $request->session()->put('reg.code', (string) $code);
            $request->session()->put('reg.code_time', now()->timestamp);
            $request->session()->put('reg.step', '1.5');
            $request->session()->forget('reg.email_verified');

            Mail::raw(
                "Your verification code is: {$code}\n\nThis code is valid for " . config('services.registration.code_ttl_minutes', 10) . " minutes.",
                fn($m) => $m->to($email)->subject('Your Verification Code')
            );

            return redirect()->route('create-account.show');
        }

        // STEP 1.5: resend
        if ($step === '1.5' && $request->has('resend_code')) {
            $email = $request->session()->get('reg.email');
            if (!$email) {
                $request->session()->forget('reg');
                return redirect()->route('create-account.show')->with('reg.error', 'Session expired. Start again.');
            }

            $code = random_int(100000, 999999);
            $request->session()->put('reg.code', (string) $code);
            $request->session()->put('reg.code_time', now()->timestamp);
            $request->session()->put('reg.step', '1.5');

            Mail::raw(
                "Your new verification code is: {$code}\n\nThis code is valid for " . config('services.registration.code_ttl_minutes', 10) . " minutes.",
                fn($m) => $m->to($email)->subject('Your New Verification Code')
            );

            return redirect()->route('create-account.show')->with('reg.success', 'A new code has been sent.');
        }

        // STEP 1.5 -> 2: verify
        if ($step === '1.5' && $request->has('verify_code')) {
            $request->validate([
                'verification_code' => ['required','digits:6'],
            ]);

            $ttl = (int) config('services.registration.code_ttl_minutes', 10) * 60;
            $sentAt = (int) ($request->session()->get('reg.code_time') ?? 0);
            $isExpired = (now()->timestamp - $sentAt) > $ttl;

            if ($isExpired) {
                return back()->with('reg.error', 'Your verification code has expired. Request a new one.');
            }

            $entered = (string) $request->input('verification_code');
            $actual = (string) ($request->session()->get('reg.code') ?? '');

            if ($entered !== $actual) {
                return back()->with('reg.error', 'Incorrect verification code.');
            }

            $request->session()->put('reg.email_verified', true);
            $request->session()->put('reg.step', '2');

            return redirect()->route('create-account.show');
        }

        // STEP 2 -> 3: save password in session
        if ($step === '2' && $request->has('continue_to_step3')) {
            if (!$request->session()->get('reg.email_verified')) {
                $request->session()->put('reg.step', '1');
                return redirect()->route('create-account.show')->with('reg.error', 'Please verify email first.');
            }

            $request->validate([
                'password' => ['required','string','min:8'],
            ]);

            // OBS: session-lagring bara för flödet (du kan byta till hash om du vill)
            $request->session()->put('reg.password_plain', (string) $request->input('password'));
            $request->session()->put('reg.step', '3');

            return redirect()->route('create-account.show');
        }

        // STEP 3: finish + create user + login
        if ($step === '3' && $request->has('finish_register')) {
            $email = $request->session()->get('reg.email');
            $password = $request->session()->get('reg.password_plain');

            if (!$email || !$password) {
                $request->session()->forget('reg');
                return redirect()->route('create-account.show')->with('reg.error', 'Session expired. Start again.');
            }

            $data = $request->validate([
                'username' => ['required','string','max:60'],
                'country' => ['required','string','max:80'],
                'gender' => ['required','in:Male,Female,Other'],
            ]);

            $name = trim($data['username']);

            // slug
            $base = Str::slug($name) ?: 'user';
            if (in_array($base, $this->reservedSlugs, true)) {
                $base .= '-' . random_int(10, 99);
            }

            $slug = $base;
            $i = 0;
            while (User::where('slug', $slug)->exists()) {
                $i++;
                $slug = $base . '-' . Str::lower(Str::random(4)) . $i;
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'slug' => $slug,
                'country' => $data['country'],
                'gender' => $data['gender'],
            ]);

            auth()->login($user);

            $request->session()->forget('reg');

            return redirect("/u/{$user->slug}?edit=1");
        }

        // fallback
        return redirect()->route('create-account.show');
    }
}
