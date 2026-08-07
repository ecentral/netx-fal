<?php

declare(strict_types=1);

/*
 * This file is part of the "netx_fal" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Fairway\NetXFal\Utility;

use finfo;

class MimeTypeSniffer
{
    public function getMimeType(string $url, string $apiToken, int $byteCount = 4096, int $timeout = 5): string
    {
        // 1) Kleine Byte-Range laden
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_HTTPHEADER     => [
                'Range: bytes=0-' . max(0, $byteCount - 1),
                'Accept-Encoding: identity', // vermeidet gzip/deflate
                'User-Agent: mime-sniffer/1.0',
                'Authorization: apiToken ' . $apiToken
            ],
            // Append body bytes, some server not like bodyless HEAD requests
            CURLOPT_NOBODY         => false,
        ]);

        $chunk = curl_exec($ch);
        $err   = curl_error($ch);
        $code  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($chunk === false || ($code < 200 || $code >= 400)) {
            // Notfalls ohne Range erneut versuchen (manche ignorieren Range)
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_HTTPHEADER     => ['Accept-Encoding: identity',
                    'Authorization: apiToken ' . $apiToken],
            ]);
            $chunk = curl_exec($ch);
            curl_close($ch);
            if ($chunk === false) {
                return 'application/octet-stream';
            }
            // Nur die ersten $byteCount Bytes betrachten
            if (strlen($chunk) > $byteCount) {
                $chunk = substr($chunk, 0, $byteCount);
            }
        }

        // 2) Magic-Bytes erkennen
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->buffer($chunk) ?: '';

        // 3) Optional: Images präziser (liefert ebenfalls MIME)
        if (!$mime || str_starts_with($mime, 'text/')) {
            if ($img = @getimagesizefromstring($chunk)) {
                $mime = $img['mime'];
            }
        }

        return $mime ?: 'application/octet-stream';
    }
}
