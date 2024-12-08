namespace hejo03\viooauth;

use wcf\system\oauth\CustomOAuthProvider;
use wcf\system\WCF;
use wcf\util\StringUtil;

class VioOAuthProvider implements CustomOAuthProvider {
    
    /**
     * OAuth-Login durchführen
     * 
     * @param string $code Der OAuth-Code, der nach der erfolgreichen Authentifizierung zurückgegeben wird
     * @return array Die Benutzerdaten (ID, Benutzername, etc.)
     */
    public function fetchUserData($code) {
        $clientId = WCF::getConfig()->get('viooauth.client_id');
        $clientSecret = WCF::getConfig()->get('viooauth.client_secret');
        $redirectUri = 'https://hejo03.de/forum/oauth-callback';  // Dein Callback-URL
        
        // Schritt 1: Token mit dem Code vom OAuth-Dienst anfordern
        $tokenUrl = 'https://apiv1.vio-v.com/oauth2/token';  // Ersetze mit der richtigen Token-URL
        $postData = [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ];

        // Sende POST-Anfrage, um das Token zu erhalten
        $response = $this->postRequest($tokenUrl, $postData);
        if (!$response || empty($response['access_token'])) {
            return [];
        }

        // Schritt 2: Benutzerinformationen abfragen
        $userInfoUrl = 'https://apiv1.vio-v.com/api/v3/self';  // Ersetze mit der richtigen URL
        $userInfo = $this->getRequest($userInfoUrl, $response['access_token']);
        
        // Rückgabe der Benutzerinformationen (z.B. ID, Name)
        return [
            'vioID' => $userInfo['vioID'],
            'username' => $userInfo['username'],
        ];
    }

    /**
     * POST-Anfrage an den OAuth-Dienst senden
     */
    protected function postRequest($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    /**
     * GET-Anfrage an den OAuth-Dienst senden
     */
    protected function getRequest($url, $accessToken) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    /**
     * Die OAuth-Login-URL zurückgeben
     *
     * @return string Die URL für die OAuth-Authentifizierung
     */
    public function getOAuthLoginUrl() {
        $clientId = WCF::getConfig()->get('viooauth.client_id');
        $redirectUri = 'https://hejo03.de/forum/oauth-callback';  // Dein Callback-URL
        
        return 'https://apiv1.vio-v.com/api/oauth2/authorize?client_id=' . $clientId .
               '&redirect_uri=' . urlencode($redirectUri) . '&response_type=code';
    }
}
