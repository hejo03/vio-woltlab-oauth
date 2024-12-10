<?php

namespace wcf\page;

use wcf\system\WCF;
use wcf\system\request\LinkHandler;

/**
 * Displays the OAuth Login Page.
 */
class OAuthLoginPage extends AbstractPage {
    /**
     * Name des Templates
     * @var string
     */
    public $templateName = 'oauthLogin';

    /**
     * OAuth-URL
     * @var string
     */
    public $oauthUrl;

    /**
     * Variablen zuweisen
     */
    public function assignVariables() {
        parent::assignVariables();
        session_start();

        $codeVerifier = bin2hex(random_bytes(60)); // Code Verifier generieren
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '='); // Code Challenge generieren
        
        // Speichere den Code Verifier in der Session
        $_SESSION['code_verifier'] = $codeVerifier;

        
        // Erstelle den State (wie von TypeScript)
        $state = rtrim(strtr(base64_encode(random_bytes(25)), '+/', '-_'), '=');
        
        $_SESSION['state'] = $state;

        // Definiere die OAuth-Parameter
        $clientId = VIO_OAUTH_CLIENT_ID;
        $redirectUri = LinkHandler::getInstance()->getControllerLink(OAuthCallbackPage::class);//'https://hejo03.de/forum/index.php?oauth-callback';
        $scopes = ['read.self'];
        $scopeString = implode(' ', $scopes);
        
        // Baue die OAuth-URL zusammen
        $oauthUrl = 'https://apiv1.vio-v.com/api/oauth2/authorize?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => urldecode($redirectUri),
            'response_type' => 'code',
            'scope' => $scopeString,
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256'
        ]);
        
        // Setze die OAuth-URL
        $this->oauthUrl = $oauthUrl;

        WCF::getTPL()->assign([
            'oauthUrl' => $this->oauthUrl
        ]);
    }
}
