<?php

namespace wcf\data\user;

use wcf\system\WCF;


class UserExtended
{
    /**
     * Sucht einen Benutzer anhand eines benutzerdefinierten Felds.
     */
    public static function getUserByCustomField(string $field, $value): array
    {
        $sql = "SELECT      user_option_value.*, user_table.*
                    FROM        wcf" . WCF_N . "_user user_table
                    LEFT JOIN   wcf" . WCF_N . "_user_option_value user_option_value
                    ON          user_option_value.userID = user_table.userID
                    WHERE       user_table." . $field . " = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$value]);
        $row = $statement->fetchArray();

        // enforce data type 'array'
        if ($row === false) {
            $row = [];
        }
        return $row;
    }
}
