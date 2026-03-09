<?php
// services/ImageUploader.php
// Validates and stores service images. Returns relative web path or throws.

declare(strict_types=1);

class ImageUploader
{
    private const ALLOWED_MIME  = ['image/jpeg', 'image/png', 'image/webp'];
    private const ALLOWED_EXT   = ['jpg', 'jpeg', 'png', 'webp'];
    private const MAX_BYTES     = 2 * 1024 * 1024;   // 2 MB
    private const MAX_DIMENSION = 4000;               // px on any side

    public function __construct(
        private string $uploadDir,   // absolute FS path, e.g. /var/www/skillhub/assets/uploads/services
        private string $webRoot      // relative web path, e.g. /assets/uploads/services
    ) {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Process $_FILES['image'] entry.
     * Returns the relative web path to the stored file.
     * Throws RuntimeException with a user-friendly message on failure.
     */
    public function handle(array $file): string
    {
        $this->checkUploadError($file['error']);
        $this->checkSize($file['size']);
        $this->checkMime($file['tmp_name']);
        $ext = $this->checkExtension($file['name']);
        $this->checkDimensions($file['tmp_name']);

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest     = $this->uploadDir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('Could not save the uploaded file. Please try again.');
        }

        return rtrim($this->webRoot, '/') . '/' . $filename;
    }

    // ── Private validators ────────────────────────────────────

    private function checkUploadError(int $code): void
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds the server upload limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds the form upload limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
        ];
        if ($code !== UPLOAD_ERR_OK) {
            throw new RuntimeException($messages[$code] ?? 'Unknown upload error.');
        }
    }

    private function checkSize(int $bytes): void
    {
        if ($bytes > self::MAX_BYTES) {
            throw new RuntimeException(
                'Image is too large. Maximum allowed size is ' .
                (self::MAX_BYTES / 1024 / 1024) . ' MB.'
            );
        }
    }

    private function checkMime(string $tmpPath): void
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($tmpPath);
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            throw new RuntimeException(
                'Invalid file type. Allowed: JPG, PNG, WEBP.'
            );
        }
    }

    private function checkExtension(string $originalName): string
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            throw new RuntimeException(
                'Invalid file extension. Allowed: ' . implode(', ', self::ALLOWED_EXT) . '.'
            );
        }
        return $ext === 'jpeg' ? 'jpg' : $ext;
    }

    private function checkDimensions(string $tmpPath): void
    {
        $info = @getimagesize($tmpPath);
        if ($info === false) {
            throw new RuntimeException('Could not read image dimensions. File may be corrupt.');
        }
        [$w, $h] = $info;
        if ($w > self::MAX_DIMENSION || $h > self::MAX_DIMENSION) {
            throw new RuntimeException(
                "Image dimensions too large (max " . self::MAX_DIMENSION . "px on any side)."
            );
        }
    }

    /** Delete a previously stored file given its relative web path. */
    public function delete(string $webPath): void
    {
        if (empty($webPath)) {
            return;
        }
        $abs = $this->uploadDir . DIRECTORY_SEPARATOR . basename($webPath);
        if (is_file($abs)) {
            @unlink($abs);
        }
    }
}
