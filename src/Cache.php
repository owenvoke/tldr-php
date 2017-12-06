<?php

namespace BrainMaestro\Tldr;

/**
 * Class Cache
 */
class Cache
{
    /**
     * The CDN index file location.
     */
    const PAGES_INDEX = 'https://tldr.sh/assets/index.json';
    /**
     * The CDN archive location.
     */
    const PAGES_ARCHIVE = 'https://tldr.sh/assets/tldr.zip';

    /**
     * @param string $page
     * @param string $platform
     * @return null|string
     */
    public static function get(string $page, string $platform = 'common')
    {
        self::makeDirIfNotExists();

        if (self::exists($page, $platform)) {
            $path = self::getPath('/pages/'.$platform.'/'.$page.'.md');
            $content = file_get_contents($path);

            return self::format($content);
        }

        return null;
    }

    /**
     * @param string $page
     * @param string $platform
     * @return bool
     */
    public static function exists(string $page, string $platform = 'common')
    {
        $indexFile = self::getPath('/pages/index.json');

        if (!file_exists($indexFile)) {
            return false;
        }

        $json = json_decode(file_get_contents($indexFile));
        $collected = collect($json->commands ?? []);

        $result = $collected
            ->where('name', $page)
            ->first();

        return (bool)($result && in_array($platform, $result->platform));
    }

    /**
     * @return bool
     */
    public static function update()
    {
        self::makeDirIfNotExists();

        $temp = tempnam(sys_get_temp_dir(), 'tldr_');
        $tempHandle = fopen($temp, 'w');
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => self::PAGES_ARCHIVE,
            CURLOPT_FILE           => $tempHandle,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        curl_exec($ch);
        curl_close($ch);

        fclose($tempHandle);

        $zip = new \ZipArchive();
        $zip->open($temp);

        return $zip->extractTo(self::getPath());
    }

    /**
     * @return int
     */
    public static function clear()
    {
        $cacheDir = self::getPath();
        $statusCode = 1;

        if (is_dir($cacheDir)) {
            passthru("rm -r {$cacheDir}", $statusCode);
        }

        return $statusCode;
    }

    /**
     *
     */
    public static function makeDirIfNotExists()
    {
        $cacheDir = self::getPath();

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0700, true);
        }
    }

    /**
     * @param string $content
     * @return string
     */
    public static function format(string $content): string
    {
        $rules = [
            // delete title of page that starts with #
            '/^# .*\n\n/' => '',
            // remove '>' from short description
            '/^> (.+)/m'  => '$1',
            // remove extra newline
            '/:\n$/m'     => ':',
            // color example command usage description
            '/^(- .+)/m'  => '<info>$1</info>',
            // color all text in example command usage
            '/^`(.+)`$/m' => '<fg=cyan>  $1</>',
            // color all other backticked text
            '/`(.+)`/m'   => '<fg=yellow>$1</>',
            // remove coloring from content between braces
            '/{{(.+?)}}/' => '</>$1</>',
        ];

        foreach ($rules as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    /**
     * @param string $subDir
     * @return string
     */
    public static function getPath(string $subDir = ''): string
    {
        return "{$_SERVER['HOME']}/.tldr".$subDir;
    }
}
