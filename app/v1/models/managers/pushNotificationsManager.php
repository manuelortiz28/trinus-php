<?php
use Phalcon\DI\InjectionAwareInterface;

/**
 * Created by PhpStorm.
 * User: montm243
 * Date: 7/18/16
 * Time: 9:04 AM
 */
class PushNotificationsManager implements InjectionAwareInterface
{
    // API access key from Google API's Console
    const API_ACCESS_KEY = "AIzaSyA5b7usl3Y9hICD76nL91oxDuDR-FBf3Ts";
    const GCM_URL = "https://android.googleapis.com/gcm/send";// https://gcm-http.googleapis.com/gcm/send , https://android.googleapis.com/gcm/send
    var $headers = array
        (
            'Authorization: key=' . PushNotificationsManager::API_ACCESS_KEY,
            'Content-Type: application/json'
        );
    protected $_di;

    public function setDI(Phalcon\DiInterface $dependencyInjector)
    {
        $this->_di = $dependencyInjector;
    }

    public function getDI()
    {
        return $this->_di;
    }

    public function send($message, $registrationIds) {
        $fields = array
        (
            'registration_ids'  => $registrationIds,
            'data'              => $message
        );


        $ch = curl_init();
        curl_setopt( $ch,CURLOPT_URL, PushNotificationsManager::GCM_URL);
        curl_setopt( $ch,CURLOPT_POST, true );
        curl_setopt( $ch,CURLOPT_HTTPHEADER, $this->headers );
        curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
        $result = curl_exec($ch );
        curl_close( $ch );
    }
}