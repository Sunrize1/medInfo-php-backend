<?php
require_once 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHandler
{
    protected $jwt_secret;
    protected $token;
    protected $issuedAt;
    protected $expire;
    protected $jwt;

    public function __construct()
    {
        $this->issuedAt = time();
        $this->expire = $this->issuedAt + 3600;
        $this->jwt_secret = $_ENV['JWT_SECRET'];;
    }

    public function jwtEncodeData($iss, $data)
    {
        $this->token = array(
            "iss" => $iss,
            "aud" => $iss,
            "iat" => $this->issuedAt,
            "exp" => $this->expire,
            "data" => $data
        );

        $this->jwt = JWT::encode($this->token, $this->jwt_secret, 'HS256');
        return $this->jwt;
    }

    public function jwtDecodeData($jwt_token)
    {
        try {
            $decode = JWT::decode($jwt_token, new Key($this->jwt_secret, 'HS256'));
            return [
                "data" => $decode->data
            ];
        } catch (\Exception $e) {
            return [
                "message" => $e->getMessage()
            ];
        }
    }
}
?>