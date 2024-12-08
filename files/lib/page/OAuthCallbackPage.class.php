<?php

namespace wcf\page;

use wcf\system\WCF;
use wcf\data\user\UserExtended;
use wcf\data\user\UserEditor;
use wcf\system\exception\UserInputException;
use wcf\util\PasswordUtil;

/**
 * Displays the OAuth Login Page.
 */
class OAuthCallbackPage extends AbstractPage {
    // /**
    //  * Name des Templates
    //  * @var string
    //  */
    public $templateName = 'oauthCallback';


    public $userData;

    private function sendError($mes) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $mes
        ]);
    }
    public function readData() {
        parent::readData();

        session_start();

        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;
        $iss = $_GET['iss'] ?? null;
        $error = $_GET['error'] ?? null;

        // Überprüfen, ob der Code Verifier und State in der Session vorhanden sind
        if (empty($_SESSION['code_verifier']) || empty($state) || $iss !== 'https://apiv1.vio-v.com') {
            http_response_code(500);
            $this->sendError("code_verifier:" . $_SESSION['code_verifier'] . "state:" . $state . "iss;",$iss);
            exit;
        }

        // Überprüfen, ob der State übereinstimmt
        if ($state !== $_SESSION['state']) {
            http_response_code(500);
            $this->sendError("state" . $_SESSION['state']);
            exit;
        }

        // Fehlerbehandlung gemäß RFC 6749
        if ($error !== null) {
            $this->sendError($error);
            // Fehlerbehandlung hier hinzufügen, z.B. loggen oder eine Fehlernachricht anzeigen
            return;
        }

        try {
            // Vorbereiten der POST-Daten für den Token-Austausch
            $params = [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'code_verifier' => $_SESSION['code_verifier'],
                'redirect_uri' => 'https://hejo03.de/forum/index.php?oauth-callback',
                'client_id' => VIO_OAUTH_CLIENT_ID,
                'client_secret' => VIO_OAUTH_CLIENT_SECRET, 
            ];
        
            // Sende eine POST-Anfrage zum Token-Endpunkt
            $ch = curl_init('https://apiv1.vio-v.com/api/oauth2/token');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded'
            ]);
        
            $response = curl_exec($ch);
            curl_close($ch);
        
            // Überprüfen, ob die Antwort erfolgreich war
            if ($response === false) {
                http_response_code(500);
                $this->sendError("response is false");
                exit;
            }
        
            $responseData = json_decode($response, true);
        
            // Zugriffstoken und Refresh-Token speichern
            if (isset($responseData['access_token']) && isset($responseData['refresh_token'])) {
                // Speichern Sie das access_token und refresh_token, z.B. in einer Datenbank oder Session
                $_SESSION['access_token'] = $responseData['access_token'];
                $_SESSION['refresh_token'] = $responseData['refresh_token'];

                $this->userData = $this->getUserData();
               
            } else {
                http_response_code(500);
                $this->sendError("access_token not here");
                exit;
            }
        
        } catch (Exception $e) {
            // Fehlerbehandlung
            http_response_code(500);
            $this->sendError($e);
            exit;
        }
        $this->loginOrRegisterUser();
    }

    public function assignVariables() {
        parent::assignVariables();

        WCF::getTPL()->assign([
            'userData' => json_encode($this->userData)
        ]);
    }

    private function loginOrRegisterUser() {
        if (!$this->userData) {
            throw new UserInputException('login');
        }

        $userID = $userData['ID'] ?? null;
        $userName = $userData['Name'] ?? null;

        if (!$userID || !$userName) {
            throw new UserInputException('login');
        }

        $user = UserExtended::getUserByCustomField('vioID', $userID);

        // echo json_encode($user);

        if (!$user) {
            // Benutzer erstellen
            $userEditor = UserEditor::create([
                'username' => $userName,
                'password' => \wcf\util\PasswordUtil::getRandomPassword(),
                'email' => strtolower($userName) . VIO_OAUTH_EMAIL_PLACEHOLDER, // Fallback-E-Mail
                'vioID' => $userID
            ]);
            return $userEditor->getDecoratedObject();
        }

        return $user;
    }
 
    private function getUserData() {
        $access_token = $_SESSION['access_token'] ?? null;

        if (!$access_token) {
            http_response_code(500);
            $this->sendError("access_token nicht gefunden");
            exit;
        }

        $apiUrl = 'https://apiv1.vio-v.com/api/v3/self'; 

        // Initialisiere cURL
        $ch = curl_init();

        // Setze cURL-Optionen
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token
        ]);

        // Sende die Anfrage und speichere die Antwort
        $response = curl_exec($ch);

        // Fehlerüberprüfung
        if(curl_errno($ch)) {
            echo 'Fehler: ' . curl_error($ch);
            exit;
        }

        // Schließe die cURL-Session
        curl_close($ch);

        // Verarbeite die Antwort (z.B. als JSON)
        $responseData = json_decode($response, true);

        // if (isset($responseData)) {
        return $responseData;
        // }
    }
}
