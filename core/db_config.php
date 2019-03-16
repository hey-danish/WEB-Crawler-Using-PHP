<?php
// MySQL Connection Making Parameters
define("DB_HOST", "localhost", false);
define("DB_USERNAME", "root", false);
define("DB_PASSWORD", "<DB Password>", false);
define("DB_DATABASE", "<DB Name>", false);  
class Database {
    function __construct() {
        $this->db_connect();
    }
    function db_connect() {
        // Global Database connection resource strings
        global $connection;
        global $db;
        // Establish the database connection
        $connection= mysqli_connect( DB_HOST, DB_USERNAME, DB_PASSWORD  ); 
        $db = mysqli_select_db($connection, DB_DATABASE);
        // Check connection
        if ( mysqli_connect_errno( ) ) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }    
        if(isset($db)) {  	           
            return $db;
        }
    }
}
?>
