<?php

namespace App\Support;

use App\Models\Member;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * QR codes drawn on the server as SVG (bacon/bacon-qr-code), and the code printed on a member card.
 */
final class QrCode
{
    public static function svg(string $text, int $size = 240): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 0), new SvgImageBackEnd));

        return $writer->writeString($text, ecLevel: ErrorCorrectionLevel::M());
    }

    /**
     * What a member's card QR holds: the member number and a signature, so the gate scanner
     * can tell a real card from a made-up one without being online.
     */
    public static function memberCode(Member $member): string
    {
        $number = (string) $member->member_no;
        $signature = substr(hash_hmac('sha256', $number, (string) config('app.key')), 0, 12);

        return 'GMC1:'.$number.':'.$signature;
    }

    public static function verifyMemberCode(string $code): ?string
    {
        if (! preg_match('/^GMC1:([A-Z0-9-]+):([a-f0-9]{12})$/', $code, $m)) {
            return null;
        }

        $expected = substr(hash_hmac('sha256', $m[1], (string) config('app.key')), 0, 12);

        return hash_equals($expected, $m[2]) ? $m[1] : null;
    }
}
