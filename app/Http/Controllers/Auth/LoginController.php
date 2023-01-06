<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Facades\Activity;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Support\Facades\Redirect;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class LoginController extends AbstractLoginController
{
    private ViewFactory $view;

    /**
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * LoginController constructor.
     *
     * @param \Illuminate\Auth\AuthManager $auth
     * @param \Illuminate\Contracts\Config\Repository $config
     * @param \Illuminate\Contracts\Cache\Repository $cache
     * @param \Pterodactyl\Contracts\Repository\UserRepositoryInterface $repository
     * @param \Illuminate\Contracts\View\Factory $view
     * @param AlertsMessageBag $alert
     */
    public function __construct(
        AuthManager $auth,
        Repository $config,
        CacheRepository $cache,
        UserRepositoryInterface $repository,
        ViewFactory $view,
        AlertsMessageBag $alert
    ) {
        parent::__construct();

        $this->view = $view;
        $this->cache = $cache;
        $this->repository = $repository;
        $this->config = $config;
        $this->alert = $alert;
    }

    /**
     * Handle all incoming requests for the authentication routes and render the
     * base authentication view component. Vuejs will take over at this point and
     * turn the login area into a SPA.
     */
    public function index(): View
    {
        return $this->view->make('templates/auth.core');
    }

    /**
     * Handle a login request to the application.
     *
     * @return \Illuminate\Http\JsonResponse|void
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            $this->sendLockoutResponse($request);
        }

        try {
            $username = $request->input('user');

            /** @var \Pterodactyl\Models\User $user */
            $user = User::query()->where($this->getField($username), $username)->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            $this->sendFailedLoginResponse($request);
        }

        // Ensure that the account is using a valid username and password before trying to
        // continue. Previously this was handled in the 2FA checkpoint, however that has
        // a flaw in which you can discover if an account exists simply by seeing if you
        // can proceede to the next step in the login process.
        if (!password_verify($request->input('password'), $user->password)) {
            $this->sendFailedLoginResponse($request, $user);
        }

        if (!$user->use_totp) {
            return $this->sendLoginResponse($user, $request);
        }

        Activity::event('auth:checkpoint')->withRequestMetadata()->subject($user)->log();

        $request->session()->put('auth_confirmation_token', [
            'user_id' => $user->id,
            'token_value' => $token = Str::random(64),
            'expires_at' => CarbonImmutable::now()->addMinutes(5),
        ]);

        return new JsonResponse([
            'data' => [
                'complete' => false,
                'confirmation_token' => $token,
            ],
        ]);
    }

    /**
     * @param $provider
     * @return JsonResponse
     */
    public function redirectToProvider($provider): JsonResponse
    {
        return new JsonResponse(['redirect' => Socialite::driver($provider)->setScopes(['openid', 'email', 'profile'])->redirect()->getTargetUrl()]);
    }

    /**
     * @param Request $request
     * @param $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleProviderCallback(Request $request, $provider)
    {
        try {
            $user = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            Log::error('failed to extract sso user from request with socialite', [
                'ex' => $e
            ]);

            echo $e->getMessage();
            die();

            //return Redirect::route('auth.login');
        }

        $authUser = $this->findOrCreateUser($user);

        if (!$authUser) {
            Log::error('customers without a game-related server may not access the game panel.');

            $this->alert->danger('Customers without a game-related server may not access the game panel.')->flash();

            return redirect()->route('auth.login', [
                'error' => 'Customers without a game-related server may not access the game panel.'
            ]);
        }

        Auth::login($authUser, true);

        return Redirect::route('index');
    }

    /**
     * @param $oauthUser
     * @return false|mixed
     */
    protected function findOrCreateUser($oauthUser)
    {
        if ($authUser = $this->repository->findWhere([['whmcs_user_token', '=', $oauthUser->id]])->first()) {
            return $authUser;
        }

        if($authUser = $this->repository->findWhere([['email', '=', $oauthUser->email]])->first()) {
            $authUser->update([
                'whmcs_user_token' => $oauthUser->id
            ]);

            return $authUser;
        }

        return false;

        /* $details = [
            'uuid' => Uuid::uuid4()->toString(),
            'username' => str_random(8),
            'email' => $oauthUser->email,
            'name_first' => $oauthUser->name_first,
            'name_last' => $oauthUser->name_last,
            'whmcs_user_token' => $oauthUser->id,
        ];

        Log::info('creating user for whmcs', [
            'details' => $details
        ]);

        // NOTE: DONT USE ::CREATE
        //   To whomever originally made this, why would
        //   you use User::create when the uuid field is
        //   not marked as fillable and gets discarded..
        $user = new User($details);
        return $user->save(); */
    }
}
