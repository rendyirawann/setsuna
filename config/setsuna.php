<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Slug yang tidak boleh dipakai portal klien
    |--------------------------------------------------------------------------
    |
    | Portal acara hidup di root ("/pernikahan-emma"), jadi slug-nya tidak
    | boleh menabrak rute aplikasi. Daftar ini dipakai saat pemesanan dan
    | saat admin mengubah slug.
    |
    */

    'reserved_slugs' => [
        'admin', 'api', 'auth', 'login', 'logout', 'register', 'password',
        'storage', 'build', 'assets', 'vendor', 'css', 'js', 'img', 'images',
        'media', 'favicon.ico', 'robots.txt', 'sitemap.xml',
        'harga', 'pesan', 'faq', 'kontak', 'tentang', 'blog', 'privacy', 'terms',
        'dashboard', 'settings', 'profile', 'users', 'roles', 'up', 'health',
    ],

    /*
    |--------------------------------------------------------------------------
    | Batas unggahan dari kamera tamu (KB)
    |--------------------------------------------------------------------------
    */

    'upload' => [
        'photo_max_kb' => 8192,      // 8 MB
        'video_max_kb' => 61440,     // 60 MB, cukup untuk 15 detik
        'boomerang_max_kb' => 20480, // 20 MB
        'poster_max_kb' => 4096,
    ],

    /*
    |--------------------------------------------------------------------------
    | Nilai bawaan saat membuat acara baru dari admin
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'photo_quota' => 18,
        'video_quota' => 2,
        'video_duration' => 15,
        'boomerang_quota' => 1,
        'film_preset' => 'natural',
        'gallery_reveal' => 'after_event',
        'storage_days' => 90,
    ],

];
