<?php
/**
 * Logout Script
 * 
 * Logs out the current user and destroys session
 * 
 * Developer: Benjamin NIYOMURINZI
 */

require_once '../config/config.php';
require_once '../includes/auth.php';

// Logout user
logout_user();

// This will redirect to login page
?>