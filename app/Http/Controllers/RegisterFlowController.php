<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterFlowController extends Controller
{
    // Standard: 10 min (kan overrideas via config/services om du vill)
    private const CODE_TTL_SECONDS = 600;

    private function countries(): array
    {
        return [
            "Afghanistan","Albania","Algeria","Andorra","Angola","Antigua and Barbuda","Argentina","Armenia",
            "Australia","Austria","Azerbaijan","Bahamas","Bahrain","Bangladesh","Barbados","Belarus","Belgium",
            "Belize","Benin","Bhutan","Bolivia","Bosnia and Herzegovina","Botswana","Brazil","Brunei","Bulgaria",
            "Burkina Faso","Burundi","Cabo Verde","Cambodia","Cameroon","Canada","Central African Republic","Chad",
            "Chile","China","Colombia","Comoros","Congo (Congo-Brazzaville)","Costa Rica","Croatia","Cuba","Cyprus",
            "Czechia","Denmark","Djibouti","Dominica","Dominican Republic","Ecuador","Egypt","El Salvador","Equatorial Guinea",
            "Eritrea","Estonia","Eswatini","Ethiopia","Fiji","Finland","France","Gabon","Gambia","Georgia","Germany",
            "Ghana","Greece","Grenada","Guatemala","Guinea","Guinea-Bissau","Guyana","Haiti","Holy See","Honduras",
            "Hungary","Iceland","India","Indonesia","Iran","Iraq","Ireland","Israel","Italy","Jamaica","Japan","Jordan",
            "Kazakhstan","Kenya","Kiribati","Kuwait","Kyrgyzstan","Laos","Latvia","Lebanon","Lesotho","Liberia","Libya",
            "Liechtenstein","Lithuania","Luxembourg","Madagascar","Malawi","Malaysia","Maldives","Mali","Malta","Marshall Islands",
            "Mauritania","Mauritius","Mexico","Micronesia","Moldova","Monaco","Mongolia","Montenegro","Morocco","Mozambique",
            "Myanmar","Namibia","Nauru","Nepal","Netherlands","New Zealand","Nicaragua","Niger","Nigeria","North Korea",
            "North Macedonia","Norway","Oman","Pakistan","Palau","Palestine State","Panama","Papua New Guinea","Paraguay",
            "Peru","Philippines","Poland","Portugal","Qatar","Romania","Russia","Rwanda","Saint Kitts and Nevis","Saint Lucia",
            "Saint Vincent and the Grenadines","Samoa","San Marino","Sao Tome and Principe","Saudi Arabia","Senegal","Serbia",
            "Seychelles","Sierra Leone","Singapore","Slovakia","Slovenia","Solomon Islands","Somalia","South Africa","South Korea",
            "South Sudan","Spain","Sri Lanka","Sudan","Suriname","Sweden","Switzerland","Syria","Tajikistan","Tanzania","Thailand",
            "Timor-Leste","Togo","Tonga","Trinidad and Tobago","Tunisia","Turkey","Turkmenistan","Tuvalu","Uganda","Ukraine",
            "United Arab Emirates","United Kingdom","United States of America","Uruguay","Uzbekistan","Vanuatu","Venezuela","Vietnam",
            "Yemen","Zambia","Zimbabwe"
        ];
    }

    private function regClosed(): bool
    {
        // max 3 testkonton (som du vill)
        return User::count() >= 10;
    }

    private function resetFlow(): void
    {
        session()->forget('reg');
    }

    /**
     * Guard så man inte kan hoppa steg.
     */
    private function ensureStep(string $required): ?\Illuminate\Http\RedirectResponse
    {
        $current = (string) session('reg.step', '1');
        if ($current !== $required) {
            return redirect()->route('register.flow.show');
        }
        return null;
    }

    private function codeTtlSeconds(): int
    {
        // Om du vill styra via config i framtiden:
        // return (int) config('services.registration.code_ttl_minutes', 10) * 60;
        return self::CODE_TTL_SECONDS;
    }

    public function show(Request $request)
    {
        $step = (string) session('reg.step', '1');

        // Om registration stängd: visa alltid step 1
        if ($this->regClosed()) {
            $step = '1';
        }

        // Extra safety: om någon försöker gå till step 2 utan verifierad mail -> backa
        if ($step === '2' && !session('reg.email_verified')) {
            $step = '1.5';
            session(['reg.step' => '1.5']);
        }

        // Extra safety: om step 3 men saknar password_hash -> backa
        if ($step === '3' && !session('reg.password_hash')) {
            $step = '2';
            session(['reg.step' => '2']);
        }

        return view('auth.create-account', [
            'step'       => $step,
            'reg_closed' => $this->regClosed(),
            'countries'  => $this->countries(),
            'email'      => session('reg.email'),
        ]);
    }

    public function sendCode(Request $request)
    {
        if ($this->regClosed()) {
            $this->resetFlow();
            return back()->withErrors(['email' => 'Registration is closed. Max 3 test accounts allowed.']);
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        $email = strtolower(trim($data['email']));
        $code  = (string) random_int(100000, 999999);
        $ttlMinutes = (int) floor($this->codeTtlSeconds() / 60);

        session([
            'reg.step'          => '1.5',
            'reg.email'         => $email,
            // SÄKERT: lagra hash istället för koden i klartext
            'reg.code_hash'     => Hash::make($code),
            'reg.code_sent_at'  => time(),
            'reg.email_verified'=> false,
        ]);

        // Skicka mail (lokalt: MAIL_MAILER=log => hamnar i laravel.log)
        Mail::raw(
            "Your verification code is: {$code}\n\nThis code is valid for {$ttlMinutes} minutes.",
            fn ($m) => $m->to($email)->subject('Your Verification Code')
        );

        return redirect()->route('register.flow.show');
    }

    public function resendCode(Request $request)
    {
        if ($this->regClosed()) {
            $this->resetFlow();
            return redirect()->route('register.flow.show')
                ->withErrors(['email' => 'Registration is closed. Max 3 test accounts allowed.']);
        }

        $email = session('reg.email');
        if (!$email) {
            $this->resetFlow();
            return redirect()->route('register.flow.show')
                ->withErrors(['email' => 'Your session expired. Please start again.']);
        }

        // (valfritt) cooldown 20s för att stoppa spam lokalt
        $last = (int) session('reg.code_sent_at', 0);
        if ($last && (time() - $last) < 20) {
            return redirect()->route('register.flow.show')
                ->withErrors(['verification_code' => 'Please wait a moment before resending a new code.']);
        }

        $code = (string) random_int(100000, 999999);
        $ttlMinutes = (int) floor($this->codeTtlSeconds() / 60);

        session([
            'reg.step'          => '1.5',
            'reg.code_hash'     => Hash::make($code),
            'reg.code_sent_at'  => time(),
            'reg.email_verified'=> false,
        ]);

        Mail::raw(
            "Your new verification code is: {$code}\n\nThis code is valid for {$ttlMinutes} minutes.",
            fn ($m) => $m->to($email)->subject('Your New Verification Code')
        );

        return redirect()->route('register.flow.show')
            ->with('status', 'A new code has been sent.');
    }

    public function verifyCode(Request $request)
    {
        if ($this->regClosed()) {
            $this->resetFlow();
            return redirect()->route('register.flow.show')
                ->withErrors(['email' => 'Registration is closed. Max 3 test accounts allowed.']);
        }

        if ($redir = $this->ensureStep('1.5')) return $redir;

        $request->validate([
            'verification_code' => ['required', 'digits:6'],
        ]);

        $email     = (string) session('reg.email', '');
        $hash      = (string) session('reg.code_hash', '');
        $sentAt    = (int) session('reg.code_sent_at', 0);

        if (!$email || !$hash || !$sentAt) {
            $this->resetFlow();
            return redirect()->route('register.flow.show')
                ->withErrors(['email' => 'Your session expired. Please start again.']);
        }

        if ((time() - $sentAt) > $this->codeTtlSeconds()) {
            return redirect()->route('register.flow.show')
                ->withErrors(['verification_code' => 'Your verification code has expired. Request a new one.']);
        }

        $entered = (string) $request->input('verification_code');

        if (!Hash::check($entered, $hash)) {
            return redirect()->route('register.flow.show')
                ->withErrors(['verification_code' => 'Incorrect verification code.']);
        }

        session([
            'reg.email_verified' => true,
            'reg.step' => '2',
        ]);

        return redirect()->route('register.flow.show');
    }

    public function savePassword(Request $request)
    {
        if ($this->regClosed()) {
            $this->resetFlow();
            return redirect()->route('register.flow.show')
                ->withErrors(['email' => 'Registration is closed. Max 3 test accounts allowed.']);
        }

        if ($redir = $this->ensureStep('2')) return $redir;

        if (!session('reg.email_verified')) {
            session(['reg.step' => '1.5']);
            return redirect()->route('register.flow.show')
                ->withErrors(['verification_code' => 'Please verify your email first.']);
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        // SÄKERT: spara hash i session, inte klartext
        session([
            'reg.password_hash' => Hash::make($data['password']),
            'reg.step'          => '3',
        ]);

        return redirect()->route('register.flow.show');
    }

    public function finish(Request $request)
    {
        if ($this->regClosed()) {
            $this->resetFlow();
            return redirect()->route('register.flow.show')
                ->withErrors(['email' => 'Registration is closed. Max 3 test accounts allowed.']);
        }

        if ($redir = $this->ensureStep('3')) return $redir;

        if (!session('reg.email_verified')) {
            session(['reg.step' => '1.5']);
            return redirect()->route('register.flow.show')
                ->withErrors(['verification_code' => 'Please verify your email first.']);
        }

        $data = $request->validate([
            'name'    => ['required', 'string', 'max:60'],
            'country' => ['required', 'string', 'max:80'],
            'gender'  => ['required', 'in:Male,Female,Other'],
        ]);

        $email        = (string) session('reg.email', '');
        $passwordHash = (string) session('reg.password_hash', '');

        if (!$email || !$passwordHash) {
            $this->resetFlow();
            return redirect()->route('register.flow.show')
                ->withErrors(['email' => 'Your session expired. Please start again.']);
        }

        // Unik slug
        $base = Str::slug($data['name']) ?: 'user';
        do {
            $slug = $base . '-' . Str::lower(Str::random(6));
        } while (User::where('slug', $slug)->exists());

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $email,
            // passwordHash är redan en bcrypt-hash
            'password' => $passwordHash,
            'slug'     => $slug,
            'country'  => $data['country'],
            'gender'   => $data['gender'],
        ]);

        // undvik att hamna på /dashboard via intended
        session()->forget('url.intended');

        auth()->login($user);
        $request->session()->regenerate();

        $this->resetFlow();

        return redirect()->to("/u/{$user->slug}?edit=1");
    }
}
