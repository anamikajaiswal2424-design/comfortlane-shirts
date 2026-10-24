<?php
// Central store settings. Edit only the values inside quotes below.
// Blank UPI ID disables payment initiation. Blank Pixel ID disables Meta Pixel.
// Multiple Pixel IDs can be separated by commas. Never put a UPI PIN here.
function comfortlaneSettings(): array
{
    return [
        'upi_id' => '',                 // Example: yourbusiness@bank
        'merchant_name' => 'ComfyNest', // Name shown in UPI apps
        'upi_enabled' => true,
        'pixel_id' => '',               // Example: 123456789012345
        'paytm_mid' => '',              // Real Paytm merchant MID for verification
        'paytm_env' => 'production',    // production or test
    ];
}
