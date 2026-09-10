<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\SetsunaBrandService;
use Illuminate\Console\Command;

class GenerateSetsunaLogo extends Command
{
    protected $signature = 'setsuna:logo
                            {name=SETSUNA : Nama merek pada lockup dan OG image}
                            {--kana=せつな : Tulisan kana di bawah nama}
                            {--apply : Arahkan setting merek ke berkas yang baru dibuat}';

    protected $description = 'Bangun logo SETSUNA: ensō vektor, set favicon, apple touch icon, dan OG image';

    public function handle(SetsunaBrandService $brand): int
    {
        $name = (string) $this->argument('name');
        $kana = (string) $this->option('kana');

        $this->info("Membuat logo untuk \"{$name}\" ({$kana}) ...");

        $assets = $brand->generate($name, $kana);

        foreach ($assets as $key => $value) {
            $this->line(sprintf('  <fg=gray>%-18s</> %s', $key, $value));
        }

        if ($this->option('apply')) {
            Setting::set('site_name', $name);
            Setting::set('site_logo', $assets['logo']);
            Setting::set('site_favicon', $assets['favicon']);
            Setting::set('site_og_image', $assets['og_image']);
            Setting::set('site_theme_color', $assets['theme_color']);
            Setting::clearCache();

            $this->info('Setting merek diarahkan ke berkas baru.');
        }

        return self::SUCCESS;
    }
}
