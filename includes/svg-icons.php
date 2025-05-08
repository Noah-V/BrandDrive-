<?php
/**
 * SVG Icons for BrandDrive
 *
 * This file contains functions to output SVG icons used throughout the plugin.
 */

// Exit if accessed directly
if (!defined('WPINC')) {
    die;
}

/**
 * Output the right arrow icon
 */
function branddrive_right_arrow_icon() {
    echo '<svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg" class="filter-arrow">
        <path d="M12 21.5C16.9706 21.5 21 17.4706 21 12.5C21 7.52944 16.9706 3.5 12 3.5C7.02944 3.5 3 7.52944 3 12.5C3 17.4706 7.02944 21.5 12 21.5Z" fill="#E6F1FB" stroke="#0A5FFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M13.5 12.5L10.5 15.5" stroke="#0A5FFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M10.5 9.5L13.5 12.5" stroke="#0A5FFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>';
}

/**
 * Output the dropdown arrow icon
 */
function branddrive_dropdown_arrow_icon() {
    echo '<svg width="20" height="21" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="10" cy="10.5" r="10" fill="#E6F1FB"/>
        <path d="M10.2477 12.0826L14.5297 7.96056C14.6047 7.88576 14.7063 7.84375 14.8122 7.84375C14.9181 7.84375 15.0197 7.88576 15.0947 7.96056C15.1317 7.99712 15.161 8.04064 15.1811 8.08862C15.2011 8.1366 15.2114 8.18807 15.2114 8.24007C15.2114 8.29206 15.2011 8.34353 15.1811 8.39151C15.161 8.43949 15.1317 8.48301 15.0947 8.51956L10.5307 13.0386C10.4557 13.1134 10.3541 13.1554 10.2482 13.1554C10.1423 13.1554 10.0407 13.1134 9.9657 13.0386L5.4017 8.51956C5.3646 8.48308 5.33514 8.43958 5.31503 8.39159C5.29491 8.34361 5.28455 8.2921 5.28455 8.24007C5.28455 8.18803 5.29491 8.13652 5.31503 8.08854C5.33514 8.04055 5.3646 7.99705 5.4017 7.96056C5.47669 7.88576 5.57828 7.84375 5.6842 7.84375C5.79012 7.84375 5.89172 7.88576 5.9667 7.96056L10.2477 12.0826Z" fill="#011B60"/>
    </svg>';
}

/**
 * Output the back arrow icon
 */
function branddrive_back_arrow_icon() {
    echo '<svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 21.5C16.9706 21.5 21 17.4706 21 12.5C21 7.52944 16.9706 3.5 12 3.5C7.02944 3.5 3 7.52944 3 12.5C3 17.4706 7.02944 21.5 12 21.5Z" fill="#0A5FFF"/>
        <path d="M10.5 12.5L13.5 9.5" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M13.5 15.5L10.5 12.5" stroke="white" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>';
}
