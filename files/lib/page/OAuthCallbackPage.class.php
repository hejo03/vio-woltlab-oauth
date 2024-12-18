<?php

namespace wcf\page;

use Exception;
use wcf\system\exception\SystemException;
use wcf\system\WCF;
use wcf\data\user\UserExtended;
use wcf\data\user\User;
use wcf\data\user\UserAction;
use wcf\system\user\group\assignment\UserGroupAssignmentHandler;
use wcf\system\application\ApplicationHandler;
//use wcf\system\user\authentication\LoginRedirect;
//use wcf\event\user\authentication\UserLoggedIn;
use wcf\system\user\authentication\event\UserLoggedIn;
use wcf\system\exception\NamedUserException;
use wcf\system\language\LanguageFactory;
//use wcf\system\user\command\CreateRegistrationNotification;
use wcf\system\event\EventHandler;
use wcf\system\request\LinkHandler;
use wcf\util\HeaderUtil;


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


    /**
     * @throws SystemException
     */
    private function getRedirectURI(): string
    {
        return LinkHandler::getInstance()->getControllerLink(OAuthCallbackPage::class);
    }

    /**
     * @throws Exception
     */
    public function readData(): void
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

            throw new Exception(
                "Error while reading API Data."
            );
        }

        // Überprüfen, ob der State übereinstimmt
        if ($state !== $session_state) {
//            http_response_code(500);
            throw new Exception(
                "Error while reading API Data."
            );
        }

        // Fehlerbehandlung gemäß RFC 6749
        if ($error !== null) {
            throw new Exception(
                "Error while reading API Data."
            );
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
            if ($response === false) {
//                http_response_code(500);
                throw new Exception(
                    "error while fetching toke."
                );
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
                throw new Exception(
                    "access_token not found."
                );
            }

        } catch (Exception $e) {
            // Fehlerbehandlung

            throw new Exception(
                $e
            );
        }

    }

    public function assignVariables(): void
    {
        parent::assignVariables();

        WCF::getTPL()->assign([
            'userData' => json_encode($this->userData)
        ]);
    }

    /**
     * @throws NamedUserException
     * @throws SystemException
     */
    private function loginOrRegisterUser(): void
    {
        $userData = $this->userData;

        if (!$userData) {
            throw new NamedUserException($this->getInUseErrorMessage());
        }

        $userID = $userData['ID'] ?? null;
        $userName = $userData['Name'] ?? null;


        if (!$userID || !$userName) {
            throw new Exception(
                "userID or userName not found."
            );
        }

        $vioUser = UserExtended::getUserByCustomField('vioID', $userID);


        //User with vioid found
        if (!empty($vioUser)) {
            if (WCF::getUser()->userID) {
                // This account belongs to an existing user, but we are already logged in.
                // This can't be handled.

                throw new NamedUserException($this->getInUseErrorMessage());
            } else {
                // This account belongs to an existing user, we are not logged in.
                // Perform the login.
                $user = User::getUserByUsername($vioUser["username"]);
                WCF::getSession()->changeUser($user);
                WCF::getSession()->update();
                EventHandler::getInstance()->fire(
                    new UserLoggedIn($user)
                );

                $application = ApplicationHandler::getInstance()->getActiveApplication();
                $path = $application->getPageURL();
                HeaderUtil::redirect($path);
//                HeaderUtil::redirect(LoginRedirect::getUrl());
            }
        } else {
            if (!VIO_OAUTH_ALLOW_REGISTER) return;
            if (WCF::getUser()->userID) {
                // This account does not belong to anyone and we are already logged in.
                // Thus we want to connect this account.

                UserExtended::setUserCustomField(WCF::getUser()->userID, 'vioID', $userID);
            } else {
                // This account does not belong to anyone and we are not logged in.
                // Thus we want to connect this account to a newly registered user.

                if (User::getUserByUsername($userName)->userID) {
                    throw new Exception(
                        "Name bereits Registriert. Du kannst dich mit Vio in den Einstellungen verbinden."
                    );

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

                UserGroupAssignmentHandler::getInstance()->checkUsers([$user->userID]);


//                $command = new CreateRegistrationNotification($user);
//                $command();

                EventHandler::getInstance()->fire(
                    new UserLoggedIn($user)
                );

                $application = ApplicationHandler::getInstance()->getActiveApplication();
                $path = $application->getPageURL();
                HeaderUtil::delayedRedirect(
                    $path,
                    WCF::getLanguage()->getDynamicVariable('wcf.user.register.success', ['user' => $user]),
                    10,
                    'success',
                    true
                );
            }
        }
    }

    /**
     * @throws Exception
     */
    private function getUserData()
    {
        $access_token = WCF::getSession()->getVar('access_token') ?? null;


        if (!$access_token) {
//            http_response_code(500);
            throw new Exception(
                "Access token was not found."
            );
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


        // Schließe die cURL-Session
        curl_close($ch);

        // Verarbeite die Antwort (z.B. als JSON)
        // if (isset($responseData)) {
        return json_decode($response, true);
        // }
    }

    protected function getInUseErrorMessage(): string
    {
        return WCF::getLanguage()->getDynamicVariable(
            "wcf.user.3rdparty.vioAuth.connect.error.inuse"
        );
    }

    public static function getRandomPassword($length = 12): string
    {
        // Calculate the number of random bytes needed for the requested length.
        // Base64 encoding expands the data by a factor of ~4/3, so adjust accordingly.
        $requiredBytes = (int)ceil($length * 3 / 4);

        // Generate secure random bytes.
        $randomBytes = random_bytes($requiredBytes);

        // Encode the bytes in Base64 and trim to the desired length.
        return substr(base64_encode($randomBytes), 0, $length);
    }
}
