<?php
use wcf\system\database\Migration;

class Migration_001_add_vioid_column extends Migration {
    /**
     * Führt die Migration aus.
     */
    public function up() {
        $this->addColumn('wcf1_user', 'vioID', 'VARCHAR(255)', false, null);
    }

    /**
     * Setzt die Migration zurück.
     */
    public function down() {
        $this->dropColumn('wcf1_user', 'vioID');
    }
}
