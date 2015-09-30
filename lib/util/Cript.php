<?php

class Cript {

    function __construct() {
        
    }

    public static function cript($data) {
        $encrypt_method = "AES-256-CBC";
        $key = hash("SHA256", Config::get('key'), true);
        $iv = substr(hash('sha256', Config::get('salt')), 0, 16);
        //$data = serialize($data);
        $ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv);
        $ciphertext = $iv . $ciphertext;
        $ciphertext_base64 = self::base64url_encode($ciphertext);
        return ($ciphertext_base64);
    }

    public static function decript($data) {
        $ciphertext_dec = self::base64url_decode(($data));
        $iv_dec = substr(hash('sha256', Config::get('salt')), 0, 16);
        $ciphertext_dec = substr($ciphertext_dec, 16);
        $key = hash("SHA256", Config::get('key'), true);
        $data = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);
        //$data = unserialize($data);
        return $data;
    }

    public static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

}

?>
