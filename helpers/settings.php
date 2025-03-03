<?php
require_once __DIR__ . '/database.php';

class Settings {
    private static $instance = null;
    private $settings = [];
    private $conn;

    private function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->loadSettings();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadSettings() {
        $stmt = $this->conn->query("SELECT setting_name, setting_value, setting_type FROM settings");
        while ($row = $stmt->fetch()) {
            $value = $row['setting_value'];
            if ($row['setting_type'] === 'boolean') {
                $value = (bool)$value;
            } elseif ($row['setting_type'] === 'number') {
                $value = (float)$value;
            }
            $this->settings[$row['setting_name']] = $value;
        }
    }

    public function get($key, $default = null) {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }

    public function set($key, $value) {
        try {
            $stmt = $this->conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_name = ?");
            $stmt->execute([$value, $key]);
            
            // Update local cache
            $this->settings[$key] = $value;
            return true;
        } catch (Exception $e) {
            error_log("Error updating setting: " . $e->getMessage());
            return false;
        }
    }

    public function getAll() {
        return $this->settings;
    }

    public function getByGroup($group) {
        $stmt = $this->conn->prepare("
            SELECT * FROM settings 
            WHERE setting_group = ?
            ORDER BY setting_name
        ");
        $stmt->execute([$group]);
        return $stmt->fetchAll();
    }

    public function getAllGroups() {
        $stmt = $this->conn->query("
            SELECT DISTINCT setting_group 
            FROM settings 
            ORDER BY setting_group
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Helper functions for easy access
function get_setting($key, $default = null) {
    return Settings::getInstance()->get($key, $default);
}

function set_setting($key, $value) {
    return Settings::getInstance()->set($key, $value);
}

function get_settings_by_group($group) {
    return Settings::getInstance()->getByGroup($group);
}

function get_all_setting_groups() {
    return Settings::getInstance()->getAllGroups();
} 