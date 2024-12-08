<?php

namespace wcf\data\user;

class UserExtended extends User {
    /**
     * Sucht einen Benutzer anhand eines benutzerdefinierten Felds.
     */
    public static function getUserByCustomField(string $field, $value) {
        $sql = "SELECT * FROM wcf1_user WHERE " . $field . " = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$value]);
        $row = $statement->fetchObject(static::class);

        return $row ?: null;
    }
}
