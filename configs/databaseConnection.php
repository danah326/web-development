<?php
            /* db.php - File to connect to the database */
            $servername = "localhost";
            $username = "root";
            $password = "root";
            $dbname = "shelftrade";

            $conn = mysqli_connect($servername, $username, $password, $dbname,3306);
            
            if (!$conn) {
                die("Connection failed: " . mysqli_connect_error());
            }
 ?>
