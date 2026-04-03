<?php

declare(strict_types=1);

namespace App\Uploads;

use Vortex\Config\Repository;

/**
 * Resolved avatar upload rules from {@code config/files.php} for {@see \Vortex\Files\Storage::storeUpload()}.
 */
final readonly class AvatarUploadSpec
{
    /**
     * @param array<string, string> $mimeExtensions
     */
    public function __construct(
        public string $directory,
        public array $mimeExtensions,
        public int $maxBytes,
    ) {
    }

    public static function fromConfig(): self
    {
        $avatarCfg = Repository::get('files.avatar', []);
        $dir = is_array($avatarCfg)
            ? trim((string) ($avatarCfg['directory'] ?? 'uploads/avatars'), '/')
            : 'uploads/avatars';

        /** @var mixed $mimeMap */
        $mimeMap = is_array($avatarCfg) ? ($avatarCfg['mime_extensions'] ?? []) : [];
        if (! is_array($mimeMap)) {
            $mimeMap = [];
        }

        $mimeExtensions = [];
        foreach ($mimeMap as $mime => $ext) {
            if (is_string($mime) && is_string($ext) && $mime !== '' && $ext !== '') {
                $mimeExtensions[$mime] = $ext;
            }
        }

        return new self(
            $dir,
            $mimeExtensions,
            (int) Repository::get('files.max_upload_bytes', 2_097_152),
        );
    }
}
