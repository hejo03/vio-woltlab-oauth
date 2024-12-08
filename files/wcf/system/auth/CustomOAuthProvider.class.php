namespace wcf\system\auth;

use wcf\data\user\User;
use wcf\data\user\UserEditor;
use wcf\system\exception\UserInputException;
use wcf\util\PasswordUtil;

class CustomOAuthProvider implements IAuthProvider {
    /**
     * Loggt den Benutzer basierend auf OAuth-Daten ein.
     */
    public function login($loginData, $accessToken) {
        // Benutzerdaten von der API abrufen
        $userData = $this->fetchUserData($accessToken);

        if (!$userData || empty($userData['id'])) {
            throw new UserInputException('login', 'Invalid user data from API.');
        }

        // Benutzer anhand von vioID zuordnen oder erstellen
        return $this->getOrCreateUser($userData);
    }

    /**
     * Ruft die Benutzerdaten von der API ab.
     */
    protected function fetchUserData($accessToken) {
        // API-Aufruf an /api/v3/self
        $url = 'https://api.example.com/api/v3/self';
        $response = @file_get_contents($url . '?access_token=' . urlencode($accessToken));

        if (!$response) {
            return null;
        }

        $userData = json_decode($response, true);

        // Rückgabedaten überprüfen
        if (!isset($userData['id']) || !isset($userData['username'])) {
            return null;
        }

        return [
            'id' => $userData['id'], // vioID
            'username' => $userData['username'],
        ];
    }

    /**
     * Sucht den Benutzer anhand von vioID oder erstellt einen neuen.
     */
    protected function getOrCreateUser(array $userData) {
        // Überprüfen, ob der Benutzer mit der vioID existiert
        $user = User::getUserByCustomField('vioID', $userData['id']);

        if (!$user) {
            // Benutzer neu erstellen
            $userEditor = UserEditor::create([
                'username' => $userData['username'],
                'password' => PasswordUtil::getRandomPassword(),
                'email' => $userData['username'] . '@example.com', // Platzhalter-E-Mail
                'vioID' => $userData['id'], // Speichere die vioID
            ]);
            return $userEditor->getDecoratedObject();
        }

        return $user;
    }
}
