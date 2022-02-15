<?php


namespace Kagatan\MikBillClientAPI;


use GuzzleHttp\Client;

class ClientAPI
{
    private $secret_key = null;
    private $jwt_token = null;
    private $client = null;


    public function __construct($host, $secret_key = null)
    {
        $this->secret_key = $secret_key;

        $this->client = new Client([
            'base_uri' => $host,
            'verify'   => false
        ]);
    }

    public function setJWT($token)
    {
        $this->jwt_token = $token;
    }


    /**
     * Поиск в биллинге абонента по user_id telegram`a
     *
     * p.s. Запрос необходимо подписывать. Не пользовательское API
     *
     * @param $value
     * @param string $key
     * @return bool|mixed
     */
    public function searchUser($value, $key = 'user_id')
    {
        $params = [
            'field' => $key,
            'value' => $value
        ];

        return $this->sendRequest('/api/v1/billing/users/search', 'POST', $params, true);
    }


    /**
     * Привязка абонента к user_id telegram`a
     *
     * p.s. Запрос необходимо подписывать. Не пользовательское API
     *
     * @param $user_id
     * @param $uid
     * @return bool|mixed
     */
    public function bindUser($user_id, $uid)
    {
        $params = [
            'user_id' => $user_id,
            'uid'     => $uid,
        ];

        return $this->sendRequest('/api/v1/billing/users/bind', 'POST', $params, true);
    }


    /**
     * Получим JWT токен для работы пользовательским API
     *
     * p.s. Запрос необходимо подписывать. Не пользовательское API
     *
     * @param $uid
     * @return bool|mixed
     */
    public function getUserToken($uid)
    {
        $params = [
            "uid" => $uid
        ];
        $response = $this->sendRequest('/api/v1/billing/users/token', 'POST', $params, true);

        // Если пришел токен пропишем его
        if (isset($response['data']['token'])) {
            $this->setJWT($response['data']['token']);
        }

        return $response;
    }


    /**
     * Получить информацию об абоненте
     *
     * @return bool|mixed
     */
    public function getUser()
    {
        return $this->sendRequest('/api/v1/cabinet/user', 'GET');
    }


    /**
     * Авторизация пользователя
     *
     * @param $login
     * @param $pass
     * @return mixed
     */
    public function auth($login, $pass)
    {
        $params = [
            'login'    => $login,
            'password' => $pass,
        ];

        return $this->sendRequest('/api/v1/cabinet/auth/login', 'POST', $params);
    }


    /**
     * Авторизация по телефону
     *
     * @param $phone
     * @return bool|mixed
     */
    public function authPhone($phone)
    {
        $params = [
            'phone' => $phone,
        ];

        return $this->sendRequest('/api/v1/cabinet/auth/phone', 'POST', $params);
    }


    private function sendRequest($uri, $method = 'POST', $params = [], $sign = false)
    {
        $headers = [];

        if ($sign) {
            $salt = uniqid();
            $params['salt'] = $salt;
            $params['sign'] = hash_hmac('sha512', $salt, $this->secret_key);
        } else {
            $headers['Authorization'] = $this->jwt_token;
        }

        $res = $this->client->request($method, $uri, [
            'form_params' => $params,
            'headers'     => $headers
        ]);

        if ($res->getStatusCode() == 200) { // 200 OK
            return json_decode($res->getBody()->getContents(), true);
        }

        return false;
    }

}