<?php

/**
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 * (c) Víctor Pàmies i Riudor <vpamies@gmail.com>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

declare(strict_types=1);

const IMAGE_MIN_WIDTH = 350;
const SIZE_THRESHOLD = 5000;
const PNG_MIN_QUALITY = 70;
const PNG_MAX_QUALITY = 95;

/**
 * Returns a list of small images.
 */
function background_test_small_image(string $source_directory, int $minimum_width = IMAGE_MIN_WIDTH): string
{
    $output = '';
    $directory_iterator = new DirectoryIterator($source_directory);
    foreach ($directory_iterator as $file_info) {
        if ($file_info->isDot()) {
            continue;
        }

        $source_file = $file_info->getPathname();
        $filename = $file_info->getFilename();

        try {
            $imagick = new Imagick();
            $imagick->readImage($source_file);
            $width = $imagick->getImageWidth();
            if ($width < $minimum_width) {
                $output .= "{$filename} ({$width} px)\n";
            }
        } catch (Exception) {
            $output .= "Error while trying to open {$filename}\n";
        }
    }

    return $output;
}

/**
 * Resizes and optimizes images in bulk.
 *
 * TODO: Review default options used in https://github.com/spatie/image-optimizer.
 *      Monitor new tool jpegli development
 *      Consider https://github.com/imagemin/imagemin-cli and https://www.npmjs.com/search?q=keywords:imageminplugin
 */
function resize_and_optimize_images_bulk(string $source_directory, string $target_directory, int $width): void
{
    $directory_iterator = new DirectoryIterator($source_directory);
    foreach ($directory_iterator as $file_info) {
        if ($file_info->isDot()) {
            continue;
        }
        $source_file = $file_info->getPathname();
        $target_file = $target_directory . '/' . $file_info->getFilename();

        // Only process the file once.
        if (is_file($target_file)) {
            continue;
        }

        $extension = mb_strtolower($file_info->getExtension());

        switch ($extension) {
            case 'gif':
                process_gif($source_file, $target_file);

                break;

            case 'png':
                process_png($source_file, $target_file, $width);

                break;

            case 'jpg':
            case 'jpeg':
                process_jpg($source_file, $target_file, $width);

                break;
        }
    }
}

/**
 * Returns whether two files have similar sizes, where $bigger_file is expected to be bigger.
 */
function files_have_similar_sizes(string $bigger_file, string $smaller_file): bool
{
    $bigger_file_size = filesize($bigger_file);
    $smaller_file_size = filesize($smaller_file);

    // Discard compression of images that do not save enough bytes.
    return $bigger_file_size > 0 && $smaller_file_size > 0 && ($bigger_file_size - SIZE_THRESHOLD) <= $smaller_file_size;
}

/**
 * Optimizes a GIF image.
 */
function process_gif(string $source_file, string $target_file): void
{
    // Avoid resizing of GIFs, as they may be animated and that could be problematic
    // Optimize GIF with lossless compression.
    exec("gifsicle --no-warnings -O3 \"{$source_file}\" -o \"{$target_file}\"");

    // Restore original file if its size is not bigger than generated file, or if file was not written.
    if (!is_file($target_file) || filesize($source_file) <= filesize($target_file)) {
        copy($source_file, $target_file);
    }

    // GIF -> WEBP conversion.
    $webp_file = str_ireplace('.gif', '.webp', $target_file);

    // Only process the file once.
    if (is_file($webp_file)) {
        return;
    }

    // Apply lossless compression.
    exec("gif2webp -quiet \"{$target_file}\" -o \"{$webp_file}\"");

    // Delete WEBP file if original file is not bigger.
    if (files_have_similar_sizes($target_file, $webp_file)) {
        unlink($webp_file);

        // Try with lossy compression.
        exec("gif2webp -quiet -lossy \"{$target_file}\" -o \"{$webp_file}\"");
        if (files_have_similar_sizes($target_file, $webp_file)) {
            unlink($webp_file);
        }
    }
}

/**
 * Resizes an image.
 */
function resize_image(string $source_file, string $target_file, int $width): void
{
    // Scale PNG and JPG images if bigger than provided $width.
    try {
        $imagick = new Imagick();
        $imagick->readImage($source_file);
        if ($imagick->getImageWidth() > $width) {
            $imagick->resizeImage($width, 0, Imagick::FILTER_LANCZOS, 1);
            $imagick->writeImage($target_file);
        }
    } catch (Exception $e) {
        echo "Error while trying to resize {$source_file}: {$e->getMessage()}";
    }

    // Restore original file if its size is not bigger than the generated file, or if the file was not written.
    if (!is_file($target_file) || filesize($source_file) <= filesize($target_file)) {
        copy($source_file, $target_file);
    }
}

/**
 * Optimizes a PNG image and creates a WEBP version.
 */
function process_png(string $source_file, string $target_file, int $width): void
{
    // Resize PNG image.
    resize_image($source_file, $target_file, $width);

    // Apply lossy compression to PNGs.
    exec('pngquant --skip-if-larger --quality=' . PNG_MIN_QUALITY . '-' . PNG_MAX_QUALITY . " --ext .png --force \"{$target_file}\"");

    // Optimize PNG with lossless compression using optipng.
    exec("optipng -quiet \"{$target_file}\"");

    // Optimize PNG with extreme lossless compression using oxipng. This is currently very slow.
    exec("oxipng --quiet -o3 --strip safe --zopfli \"{$target_file}\"");

    // PNG -> WEBP conversion.
    // Rather than base it in the original file like it is done in the JPG -> AVIF conversion, we use the optimized PNG
    // instead. This is because pngquant lossy degradation is small, may be beneficial to webp, and we prefer using cweb
    // rather than imagemagick to create the webp images. The fact that here the images have been resized previously
    // should not impact the image quality considerably.
    $webp_file = str_ireplace('.png', '.webp', $target_file);

    // Only process the file once.
    if (is_file($webp_file)) {
        return;
    }

    // Create WEBP with lossless compression.
    exec("cwebp -quiet \"{$target_file}\" -o \"{$webp_file}\"");

    // Delete WEBP file if original file is not bigger.
    if (files_have_similar_sizes($target_file, $webp_file)) {
        unlink($webp_file);

        // Try with lossy compression.
        exec("cwebp -quiet -q 60 \"{$target_file}\" -o \"{$webp_file}\"");
        if (files_have_similar_sizes($target_file, $webp_file)) {
            unlink($webp_file);
        }
    }
}

/**
 * Optimizes a JPEG image and creates an AVIF version.
 */
function process_jpg(string $source_file, string $target_file, int $width): void
{
    // Resize JPG image.
    resize_image($source_file, $target_file, $width);

    // Optimize JPG with lossless compression.
    exec("jpegoptim --quiet \"{$target_file}\"");

    // JPEG -> AVIF conversion.
    // TODO: Convert animated GIFs and transparent PNGs too, maybe with https://github.com/lovell/sharp or
    //       https://github.com/GoogleChromeLabs/squoosh/tree/dev/libsquoosh.
    //       But note that 8-bit images may compress better in PNG than in AVIF at this point (see
    //       https://vincent.bernat.ch/en/blog/2021-webp-avif-nginx) and that AVIF sequences have compatibility issues
    //       in Firefox and Safari (see https://bugzilla.mozilla.org/show_bug.cgi?id=1686338#c28).
    $avif_file = str_ireplace('.jpg', '.avif', $target_file);

    // Only process the file once.
    if (is_file($avif_file)) {
        return;
    }

    // Create AVIF image.
    // Use the original file as source rather than the resized one, as JPEG is lossy.
    try {
        $imagick = new Imagick();
        $imagick->readImage($source_file);
        $imagick->setImageFormat('avif');
        if ($imagick->getImageWidth() > $width) {
            $imagick->resizeImage($width, 0, Imagick::FILTER_LANCZOS, 1);
        }
        $imagick->writeImage($avif_file);

        // Delete AVIF file if original file is not bigger.
        if (files_have_similar_sizes($target_file, $avif_file)) {
            unlink($avif_file);
        }
    } catch (Exception $e) {
        echo "Error while trying to process {$target_file}: {$e->getMessage()}";
    }
}
