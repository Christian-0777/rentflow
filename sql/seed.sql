INSERT INTO `users` 
(`tenant_id`, `role`, `email`, `first_name`, `last_name`, `password_hash`, `status`, `confirmed`, 
 `cover_photo`, `profile_photo`, `business_name`, `location`, `twofa_enabled`, `remember_device_enabled`, 
 `device_token`, `password_reset_otp`, `password_reset_expires`, `password_reset_requested_at`, 
 `notif_email`, `notif_sms`)
VALUES
(NULL, 'admin', 'admin@rentflow.local', 'Admin', 'User', '$2y$10$examplehash', 'active', 1, 
 NULL, NULL, NULL, 'Baliwag Public Market', 0, 0, NULL, NULL, NULL, NULL, 1, 0);
