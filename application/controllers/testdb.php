<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Testdb extends CI_Controller {

    public function index() {
        if ($this->db->conn_id) {
            echo "<h2 style='color: green;'>Successfully connected to phpMyAdmin database: " . $this->db->database . "</h2>";
            
            $tables = $this->db->list_tables();
            echo "<h4>Tables loaded in database:</h4><ul>";
            foreach ($tables as $table) {
                echo "<li>" . $table . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<h2 style='color: red;'>Database connection failed!</h2>";
        }
    }
}