<?php
/**
 * SocialConnect project
 * @author: Patsura Dmitry https://github.com/ovr <talk@dmtry.me>
 */

namespace SocialConnect\Auth\Provider\OAuth2;

use InvalidArgumentException;
use SocialConnect\Auth\Exception\InvalidAccessToken;
use SocialConnect\Auth\Provider\AbstractBaseProvider;
use SocialConnect\Common\Entity\User;
use SocialConnect\Common\Http\Client\Client;

abstract class AbstractProvider extends AbstractBaseProvider
{
    /**
     * HTTP method for access token request
     *
     * @var string
     */
    protected $requestHttpMethod = Client::POST;

    /**
     * @return array
     */
    public function getAuthUrlParameters()
    {
        return array(
            'client_id' => $this->consumer->getKey(),
            'redirect_uri' => $this->getRedirectUrl()
        );
    }

    /**
     * @return string
     */
    public function makeAuthUrl()
    {
        $urlParameters = $this->getAuthUrlParameters();

        if (count($this->scope) > 0) {
            $urlParameters['scope'] = $this->getScopeInline();
        }

        if (count($this->fields) > 0) {
            $urlParameters['fields'] = $this->getFieldsInline();
        }

        return $this->getAuthorizeUri() . '?' . http_build_query($urlParameters);
    }

    /**
     * Parse access token from response's $body
     *
     * @param $body
     * @return AccessToken
     * @throws InvalidAccessToken
     */
    public function parseToken($body)
    {
        parse_str($body, $token);

        if (!is_array($token) || !isset($token['access_token'])) {
            throw new InvalidAccessToken('Provider API returned an unexpected response');
        }

        return new AccessToken($token['access_token']);
    }

    /**
     * @param string $code
     * @return AccessToken
     */
    public function getAccessToken($code)
    {
        if (!is_string($code)) {
            throw new InvalidArgumentException('Parameter $code must be a string');
        }

        $parameters = array(
            'client_id' => $this->consumer->getKey(),
            'client_secret' => $this->consumer->getSecret(),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->getRedirectUrl()
        );

        $response = $this->service->getHttpClient()->request(
            $this->getRequestTokenUri() . '?' . http_build_query($parameters),
            array(),
            $this->requestHttpMethod
        );
        $body = $response->getBody();

        return $this->parseToken($body);
    }


    /**
     * @param array $parameters
     * @return AccessToken
     */
    public function getAccessTokenByRequestParameters(array $parameters)
    {
        return $this->getAccessToken($parameters['code']);
    }

    /**
     * Get current user identity from social network by $accessToken
     *
     * @param AccessToken $accessToken
     * @return User
     */
    abstract public function getIdentity(AccessToken $accessToken);
}
