<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrSvg
{
    public static function render(string $payload, int $size = 280): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 2), new SvgImageBackEnd));

        // Hapus deklarasi XML agar aman disisipkan langsung ke HTML.
        return preg_replace('/^<\?xml[^>]*\?>\s*/', '', $writer->writeString($payload));
    }
}
