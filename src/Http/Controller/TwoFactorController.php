<?php

namespace Visanduma\NovaTwoFactor\Http\Controller;


use App\Http\Controllers\Controller;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA as G2fa;
use PragmaRX\Google2FA\Support\Constants;
use Visanduma\NovaTwoFactor\TwoFaAuthenticator;

class TwoFactorController extends Controller
{
    public function registerUser()
    {

        if(auth()->user()->twoFa && auth()->user()->twoFa->confirmed == 1){
            return response()->json([
                'message' => 'Already verified!'
            ]);
        }

        $google2fa = new G2fa();
        $secretKey = $google2fa->generateSecretKey(32);

        $recoveryKey = strtoupper(Str::random(16));
        $recoveryKey = str_split($recoveryKey,4);
        $recoveryKey = implode("-",$recoveryKey);

        $recoveryKeyHashed = bcrypt($recoveryKey);

        $data['recovery'] = $recoveryKey;


        $model = config('nova-two-factor.2fa_model');
        $userTwoFa = new $model();
        $userTwoFa::where('user_id', auth()->user()->id)->delete();
        $user2fa = new $userTwoFa();
        $user2fa->user_id = auth()->user()->id;
        $user2fa->google2fa_secret = $secretKey;
        $user2fa->recovery = $recoveryKeyHashed;
        $user2fa->save();



        $google2fa_url = $this->getQRCodeGoogleUrl(
            config('app.name'),
            auth()->user()->email,
            $secretKey
        );

        $data['google2fa_url'] = $google2fa_url;

        return $data;
    }

    public function verifyOtp()
    {
        $otp = request()->get('otp');
        request()->merge(['one_time_password' => $otp]);

        $authenticator = app(TwoFaAuthenticator::class)->boot(request());

        if ($authenticator->isAuthenticated()) {
            // otp auth success!

            auth()->user()->twoFa()->update([
                'confirmed' => true,
                'google2fa_enable' => true
            ]);

            return response()->json([
                'message' => '2FA security successfully activated!'
            ]);
        }

        // auth fail
        return response()->json([
            'message' => 'Invalid OTP! Please try again.'
        ],422);
    }

    public function toggle2Fa(Request $request)
    {
        $status = $request->get('status') === 1;
        auth()->user()->twoFa()->update([
            'google2fa_enable' => $status
        ]);

        return response()->json([
            'message' => $status ? '2FA feature enabled!' : '2FA feature disabled!'
        ]);
    }

    public function getStatus()
    {
        $user = auth()->user();

        $res = [
            "registered" => !empty($user->twoFa),
            "enabled" => auth()->user()->twoFa->google2fa_enable ?? false,
            "confirmed" => auth()->user()->twoFa->confirmed ?? false
        ];
        return $res;
    }

    public function getQRCodeGoogleUrl($company, $holder, $secret, $size = 200)
    {
        $g2fa = new G2fa();
        $url = $g2fa->getQRCodeUrl($company, $holder, $secret);

        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        return 'data:image/svg+xml;base64,' . base64_encode($writer->writeString($url));
    }

    public function authenticate(Request $request)
    {
        $authenticator = app(TwoFaAuthenticator::class)->boot(request());

        if ($authenticator->isAuthenticated()) {
            return redirect()->to(config('nova.path'));
        }

        return back()->withErrors(['Incorrect OTP!']);
    }

    public function recover(Request $request)
    {
        if($request->isMethod('get')){
            return view('nova-two-factor::recover');
        }

        if(Hash::check($request->get('recovery_code'), auth()->user()->twoFa->recovery)){
            // reset 2fa
            auth()->user()->twoFa()->delete();
            return redirect()->to(config('nova.path'));
        }else{
            return back()->withErrors(['Incorrect recovery code!']);
        }
    }
}