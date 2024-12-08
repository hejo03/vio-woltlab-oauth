namespace hejo03\viooauth;

use wcf\system\WCF;
use wcf\system\template\TemplateEngine;
use wcf\system\event\EventListener;

class OAuthButtonListener extends EventListener {
    /**
     * Gibt die abonnierten Events zurück.
     *
     * @return array
     */
    public static function getSubscribedEvents() {
        return [
            'wcf.UserLoginPage' => 'addOAuthButton', // Login-Seite
            'wcf.UserRegistrationPage' => 'addOAuthButton', // Registrierungs-Seite
        ];
    }

    /**
     * Fügt den OAuth-Button hinzu
     *
     * @param object $event
     */
    public function addOAuthButton($event) {
        // OAuth-Link generieren
        $clientId = WCF::getConfig()->get('viooauth.client_id');
        $redirectUri = 'https://hejo03.de/forum/oauth-callback'; // Ersetze mit deinem Redirect-URI
        $oauthUrl = 'https://apiv1.vio-v.com/api/oauth2/authorize?client_id=' . $clientId . '&redirect_uri=' . urlencode($redirectUri) . '&response_type=code&scope=openid';

        // Template-Variable übergeben
        $event->addTemplateVar('oauthUrl', $oauthUrl);
        $event->addTemplateFile('oauthButton.tpl');
    }
}
