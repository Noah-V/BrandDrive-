<?php
/**
 * Handles encryption for secure communication with BrandDrive.
 */
class BrandDrive_Encryption {
    /**
     * Generate AES key from plugin key.
     */
    public function generate_aes_key($plugin_key) {
        if (empty($plugin_key)) {
            return false;
        }
        
        // Split the key into required parts
        $part1 = substr($plugin_key, 4, 6);   // plugin-key[4, 10]
        $part2 = substr($plugin_key, 0, 4);   // plugin-key[0-4]
        $part3 = substr($plugin_key, 15);     // plugin[15, last index]
        $part4 = substr($plugin_key, 10, 5);  // plugin[10, 15]
        
        // Combine parts to create AES key
        return $part1 . $part2 . $part3 . $part4;
    }
    
    /**
     * Encrypt data using AES.
     */
    public function encrypt($data, $plugin_key) {
        if (empty($data) || empty($plugin_key)) {
            return false;
        }

        $aes_key = $this->generate_aes_key($plugin_key);
        if (!$aes_key) {
            return false;
        }

        // Generate random IV
        $iv = openssl_random_pseudo_bytes(16);
        
        // Encrypt data
        $encrypted = openssl_encrypt(
            json_encode($data),
            'AES-256-CBC',
            $aes_key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            return false;
        }

        // Combine IV and encrypted data
        $result = base64_encode($iv . $encrypted);
        
        return $result;
    }
    
    /**
     * Decrypt data using AES.
     */
    public function decrypt($encrypted_data, $plugin_key) {
        if (empty($encrypted_data) || empty($plugin_key)) {
            return false;
        }

        $aes_key = $this->generate_aes_key($plugin_key);
        if (!$aes_key) {
            return false;
        }

        // Decode base64
        $decoded = base64_decode($encrypted_data);
        if ($decoded === false) {
            return false;
        }

        // Extract IV (first 16 bytes)
        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        
        // Decrypt data
        $decrypted = openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            $aes_key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            return false;
        }

        // Decode JSON
        $data = json_decode($decrypted, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        return $data;
    }
}

