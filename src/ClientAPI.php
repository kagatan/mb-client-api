<?php


namespace kagatan\MikBillClientAPI;


class ClientAPI
{
    private $host;
    private $token;

    public function __construct($host)
    {
        $this->host = $host;
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

        return $this->sendRequest('POST', '/api/v1/cabinet/auth/login', $params);
    }

    /**
     * Карточка пользователя
     *
     * @return mixed
     */
    public function getUser()
    {
        return $this->sendRequest('GET', '/api/v1/cabinet/user');
    }

    /**
     * Получить токен
     *
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    private function sendRequest($method, $resource, $dataArray = array())
    {
        $url = $this->host . $resource;

        $headers = [];

        if (!empty($this->token)) {
            $headers[] = "Authorization: " . $this->token;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_VERBOSE, FALSE);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($method = 'POST') {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataArray);
        }

        $result = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($result, true);

        if (isset($response['data']['token'])) {
            $this->token = $response['data']['token'];
        }

        return $response;
    }

}