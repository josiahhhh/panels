<?php

namespace Pterodactyl\Socialite;

use GuzzleHttp\RequestOptions;
use Laravel\Socialite\Two\User;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;

class WHMCSProvider extends AbstractProvider implements ProviderInterface
{
    public const IDENTIFIER = 'WHMCS';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['openid', 'email', 'profile'];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(config('services.whmcs.url') . '/oauth/authorize.php', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return config('services.whmcs.url') . '/oauth/token.php';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            config('services.whmcs.url') . '/oauth/userinfo.php?' . http_build_query(
                [
                    'access_token' => $token,
                ]
            ),
            [
                RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                ],
            ]
        );

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['sub'],
            'email' => $user['email'],
            'name_first' => $user['given_name'],
            'name_last' => $user['family_name'],
        ]);
    }

}
