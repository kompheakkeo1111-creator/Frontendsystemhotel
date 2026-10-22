<?php
namespace App\Core;

use PDO;

/**
 * No Code Duplication Pattern - Base Model Class
 * All models inherit from this class to reuse DB connection
 * Avoids repeating "getDB()" in every model
 */
abstract class Model
{
    /** @var PDO Shared database connection (inherited by all models) */
    protected $db;

    /**
     * Constructor: Automatically connect to database
     * Called when any model is created (User, Room, Guest, etc.)
     */
    public function __construct()
    {
        $this->db = getDB(); // Reusable: one connection for all models
    }
}
