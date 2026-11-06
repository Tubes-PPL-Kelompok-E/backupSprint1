<?php

return [
    // Jarak minimal antar donasi (hari). Sesuaikan dengan use case di proposal.
    'min_donation_interval_days' => 60,

    // Nama role persis seperti di RoleSeeder.php
    'donor_role'    => 'pendonor',
    'officer_roles' => ['admin_pmi'], // role yang boleh mencatat donasi
];