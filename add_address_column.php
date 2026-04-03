<?php
$mysqli = new mysqli('127.0.0.1', 'root', '', 'spend_it');
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}
$query = 'ALTER TABLE users ADD COLUMN address VARCHAR(255) NULL';
if ($mysqli->query($query)) {
    echo 'Address column added successfully';
} else {
    echo 'Error: ' . $mysqli->error;
}
$mysqli->close();
