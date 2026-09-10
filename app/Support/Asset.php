<?php

namespace App\Support;

/**
 * URL aset dengan penanda versi.
 *
 * CSS dan JS di public/ tidak lewat build, jadi browser gemar menahannya
 * di cache dan perubahan tidak terlihat sampai pengguna melakukan hard
 * refresh. Menempelkan waktu ubah berkas membuat URL-nya berganti tiap
 * kali berkasnya benar-benar berubah.
 */
class Asset
{
    /** @var array<string,string> */
    private static array $cache = [];

    public static function v(string $path): string
    {
        if (isset(self::$cache[$path])) {
            return self::$cache[$path];
        }

        $url = asset($path);
        $file = public_path($path);

        if (is_file($file)) {
            $url .= '?v=' . filemtime($file);
        }

        return self::$cache[$path] = $url;
    }
}
