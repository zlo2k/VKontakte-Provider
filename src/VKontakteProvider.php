<?php

namespace Socialite\Provider;

use Socialite\Two\AbstractProvider;
use Socialite\Two\User;
use Socialite\Util\A;

class VKontakteProvider extends AbstractProvider
{
    /**
     * The request fields.
     *
     * @var array
     */
    protected $fields = [
        'uid',
        'email',
        'first_name',
        'last_name',
        'screen_name',
        'photo'
    ];

    /**
     * The lang.
     *
     * @var string
     */
    protected $lang = '';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['email'];

    /**
     * API version used to access VK.com API
     */
    const API_VERSION = '5.69';
    
    /**
     * {@inheritdoc}
     */
    protected $parameters = [
        'v' => self::API_VERSION,
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl(string $state)
    {
        return $this->buildAuthUrlFromBase(
            'https://oauth.vk.com/authorize', $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://oauth.vk.com/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken(string $token)
    {
        $response = $this->getHttpClient()->get(
		    'https://api.vk.com/method/users.get?'.http_build_query([
		        'access_token' => $token,
		        'fields' => implode(',', $this->fields),
		        'v' => self::API_VERSION,
		    ]) . $this->lang;
        );
        $contents = $response->getBody()->getContents();
        $response = json_decode($contents, true);
        if (!is_array($response) || !isset($response['response'][0])) {
            throw new \RuntimeException(sprintf(
                'Invalid JSON response from VK: %s', $contents
            ));
        }
        return $response['response'][0];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => A::get($user, 'uid'),
            'nickname' => A::get($user, 'screen_name'),
            'name' => trim(A::get($user, 'first_name') . ' ' . A::get($user, 'last_name')),
            'email' => A::get($user, 'email'),
            'avatar' => A::get($user, 'photo'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields(string $code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * Set the user fields to request from Vkontakte.
     *
     * @param array $fields
     *
     * @return $this
     */
    public function fields(array $fields)
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Set lang.
     *
     * @param string $lang
     *
     * @return $this
     */
    public function setLang(string $lang)
    {
        $this->lang = '&language=' . $lang;
        return $this;
    }
}
