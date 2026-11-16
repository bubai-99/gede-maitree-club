<?php

/*
| Sign-ins created by the sample seeder (database/seeders/SampleUsersSeeder.php).
| Leave the passwords blank to have the seeder make random ones and print them once.
| Never use real people's details here.
*/

return [
    'admin_email' => env('SEED_ADMIN_EMAIL', 'admin@example.org'),
    'admin_password' => env('SEED_ADMIN_PASSWORD'),
    'member_email' => env('SEED_MEMBER_EMAIL', 'member@example.org'),
    'member_password' => env('SEED_MEMBER_PASSWORD'),
];
