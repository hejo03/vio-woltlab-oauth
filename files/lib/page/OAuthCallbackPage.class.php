<?php

namespace wcf\page;

use wcf\system\WCF;
use wcf\data\user\UserExtended;
use wcf\data\user\User;
use wcf\data\user\UserAction;
use wcf\system\user\group\assignment\UserGroupAssignmentHandler;
use wcf\system\user\authentication\LoginRedirect;
use wcf\event\user\authentication\UserLoggedIn;
use wcf\system\language\LanguageFactory;
use wcf\system\user\command\CreateRegistrationNotification;
use wcf\system\event\EventHandler;
use wcf\system\request\LinkHandler;
use wcf\util\UserRegistrationUtil;
use wcf\util\HeaderUtil;

//session_start();

/**
 * Displays the OAuth Login Page.
 */
class OAuthCallbackPage extends AbstractPage
{
    // /**
    //  * Name des Templates
    //  * @var string
    //  */
    public $templateName = 'oauthCallback';


    public $userData;

    private function sendError($mes): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $mes
        ]);
        exit();
    }

    private function getRedirectURI(): string
    {
        $redirectUri = LinkHandler::getInstance()->getControllerLink(OAuthCallbackPage::class);
        return $redirectUri;
    }

    public function readData()
    {
        parent::readData();

        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;
        $iss = $_GET['iss'] ?? null;
        $error = $_GET['error'] ?? null;

        // Überprüfen, ob der Code Verifier und State in der Session vorhanden sind
        $session_code_verifier = WCF::getSession()->getVar('code_verifier') ?? null;
        $session_state = WCF::getSession()->getVar('state') ?? null;

        if (empty($session_code_verifier) || empty($state) || $iss !== 'https://apiv1.vio-v.com') {
//            http_response_code(500);
            $this->sendError("code_verifier:" . $session_code_verifier . "state:" . $state . "iss;", $iss);
        }

        // Überprüfen, ob der State übereinstimmt
        if ($state !== $session_state) {
//            http_response_code(500);
            $this->sendError("state" . $state);
        }

        // Fehlerbehandlung gemäß RFC 6749
        if ($error !== null) {
            $this->sendError($error);
            // Fehlerbehandlung hier hinzufügen, z.B. loggen oder eine Fehlernachricht anzeigen
        }

        try {
            // Vorbereiten der POST-Daten für den Token-Austausch
            $params = [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'code_verifier' => $session_code_verifier,
                'redirect_uri' => $this->getRedirectURI(),
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
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Überprüfen, ob die Antwort erfolgreich war
            if ($response === false && $http_code !== 200) {
//                http_response_code(500);
                $this->sendError("error while fetching token" . curl_error($ch));
            }

            $responseData = json_decode($response, true);

            // Zugriffstoken und Refresh-Token speichern
            if (isset($responseData['access_token']) && isset($responseData['refresh_token'])) {
                // Speichern Sie das access_token und refresh_token, z.B. in einer Datenbank oder Session
                WCF::getSession()->register('access_token', $responseData['access_token']);
                WCF::getSession()->register('refresh_token', $responseData['refresh_token']);
//                $_SESSION['access_token'] = $responseData['access_token'];
//                $_SESSION['refresh_token'] = $responseData['refresh_token'];

                $this->userData = $this->getUserData();
                $this->loginOrRegisterUser();

            } else {
//                http_response_code(500);
                $this->sendError("access_token not here");
            }

        } catch (\Exception $e) {
            // Fehlerbehandlung
//            http_response_code(500);
            $this->sendError($e);
        }

    }

    public function assignVariables()
    {
        parent::assignVariables();

        WCF::getTPL()->assign([
            'userData' => json_encode($this->userData)
        ]);
    }

    private function loginOrRegisterUser(): void
    {
        $userData = $this->userData;

        if (!$userData) {
//            http_response_code(500);
            $this->sendError("userData not found");
        }

        $userID = $userData['ID'] ?? null;
        $userName = $userData['Name'] ?? null;


        if (!$userID || !$userName) {
//            http_response_code(500);
            $this->sendError("userID or userName not found");
        }


        $vioUser = UserExtended::getUserByCustomField('vioID', $userID);


//        echo json_encode($vioUser);


        //User with vioid found
        if (!empty($vioUser)) {
            if (WCF::getUser()->userID) {
                // This account belongs to an existing user, but we are already logged in.
                // This can't be handled.

                http_response_code(500);
                $this->sendError("Fatal Error!");
            } else {
                // This account belongs to an existing user, we are not logged in.
                // Perform the login.
                $user = User::getUserByUsername($vioUser["username"]);
                WCF::getSession()->changeUser($user);
                WCF::getSession()->update();
                EventHandler::getInstance()->fire(
                    new UserLoggedIn($user)
                );

                HeaderUtil::redirect(LoginRedirect::getUrl());

            }
        } else {
            if (!VIO_OAUTH_ALLOW_REGISTER) return;
            if (WCF::getUser()->userID) {
                // This account does not belong to anyone and we are already logged in.
                // Thus we want to connect this account.

                http_response_code(500);
                $this->sendError("Fatal Error! no vio id");
            } else {
                // This account does not belong to anyone and we are not logged in.
                // Thus we want to connect this account to a newly registered user.


                if (User::getUserByUsername($userName)->userID) {
                    http_response_code(500);
                    $this->sendError("Diesen Namen gibt es bereits. Bitte kontaktiere einen Administrator.");
                }

                $langs = LanguageFactory::getInstance()->getDefaultLanguageID();
                $additionalFields['languageID'] = WCF::getLanguage()->languageID;

                $data = [
                    'data' => \array_merge($additionalFields, [
                        'username' => $userName,
                        'email' => strtolower($userName) . VIO_OAUTH_EMAIL_PLACEHOLDER,
                        'password' => $this->getRandomPassword(16),
                        'blacklistMatches' => '',
                        'signatureEnableHtml' => 1,
                        'vioID' => $userID
                    ]),
                    'groups' => [],
                    'languageIDs' => [$langs],
                    'addDefaultGroups' => true,
                ];

                $objectAction = new UserAction([], 'create', $data);
                $result = $objectAction->executeAction();
                /** @var User $user */
                $user = $result['returnValues'];


                WCF::getSession()->changeUser($user);
//                WCF::getSession()->update();

                UserGroupAssignmentHandler::getInstance()->checkUsers([$user->userID]);


                $command = new CreateRegistrationNotification($user);
                $command();

                EventHandler::getInstance()->fire(
                    new UserLoggedIn($user)
                );
//                EventHandler::getInstance()->fireAction($this, 'saved');


                HeaderUtil::delayedRedirect(
                    LoginRedirect::getUrl(),
                    WCF::getLanguage()->getDynamicVariable('wcf.user.register.success', ['user' => $user]),
                    15,
                    'success',
                    true
                );
            }
        }
    }

    private function getUserData()
    {
        $access_token = WCF::getSession()->getVar('access_token') ?? null;
//        $access_token = $_SESSION['access_token'] ?? null;

        if (!$access_token) {
//            http_response_code(500);
            $this->sendError("access_token nicht gefunden");
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
        if (curl_errno($ch)) {
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

    function consoleLog($message): void
    {
        // Escape special characters for safe output
        $message = addslashes($message);
        echo "<script>console.log('$message');</script>";
    }

    public static function getRandomPassword($length = 12)
    {
        // Calculate the number of random bytes needed for the requested length.
        // Base64 encoding expands the data by a factor of ~4/3, so adjust accordingly.
        $requiredBytes = (int)ceil($length * 3 / 4);

        // Generate secure random bytes.
        $randomBytes = random_bytes($requiredBytes);

        // Encode the bytes in Base64 and trim to the desired length.
        $password = substr(base64_encode($randomBytes), 0, $length);

        return $password;
    }
}
