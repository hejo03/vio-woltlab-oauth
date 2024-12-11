<?php

namespace wcf\action;

use wcf\page\OAuthCallbackPage;
use wcf\system\request\LinkHandler;
use wcf\util\HeaderUtil;
use wcf\system\WCF;


final class VioAuthAction extends AbstractAction
{
    const AVAILABLE_DURING_OFFLINE_MODE = true;

    public function execute()
    {
        parent::execute();

//        session_start();

        $codeVerifier = bin2hex(random_bytes(60)); // Code Verifier generieren
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '='); // Code Challenge generieren

        // Speichere den Code Verifier in der Session
        WCF::getSession()->register('code_verifier', $codeVerifier);
//        $_SESSION['code_verifier'] = $codeVerifier;


        // Erstelle den State (wie von TypeScript)
        $state = rtrim(strtr(base64_encode(random_bytes(25)), '+/', '-_'), '=');

//        $_SESSION['state'] = $state;
        WCF::getSession()->register('state', $state);

        // Definiere die OAuth-Parameter
        $clientId = VIO_OAUTH_CLIENT_ID;
        $redirectUri = LinkHandler::getInstance()->getControllerLink(OAuthCallbackPage::class);
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

        HeaderUtil::redirect($oauthUrl);
    }

}