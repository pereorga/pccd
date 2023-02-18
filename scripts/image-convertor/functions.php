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

/** Discard lossy compression of images that do not save enough bytes */
const SIZE_THRESHOLD = 5000;

const PNG_MIN_QUALITY = 70;
const PNG_MAX_QUALITY = 95;

// image-convertor script functions.

/**
 * Returns list of small images.
 */
function background_test_small_image(string $source_directory, int $minimum_width = IMAGE_MIN_WIDTH): string
{
    $output = '';
    $files = scandir($source_directory);
    if ($files === false) {
        error_log("Error scanning {$source_directory} directory.");

        return '';
    }

    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $source_file = $source_directory . '/' . $file;
            $pos = mb_strpos($source_file, '/src/images/');
            if ($pos !== false) {
                $pos += strlen('/src/images/');
                $filename = mb_substr($source_file, $pos);

                try {
                    $imagick = new Imagick();
                    $imagick->readImage($source_file);
                    $width = $imagick->getImageWidth();
                    if ($width < $minimum_width) {
                        $output .= "{$filename} ({$width} px)\n";
                    }
                } catch (Exception) {
                    $output .= "Error intentant obrir {$filename}\n";
                }
            }
        }
    }

    return $output;
}

/**
 * Resizes and optimizes images in bulk.
 */
function pccd_image_resize_bulk(string $source_directory, string $target_directory, int $width): void
{
    $output = null;
    $ret = null;
    $imagick = null;

    $files = scandir($source_directory);
    if ($files === false) {
        error_log("Error scanning {$source_directory} directory.");

        return;
    }

    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $source_file = $source_directory . '/' . $file;
            $target_file = $target_directory . '/' . $file;

            // Only process the file once.
            if (!is_file($target_file)) {
                $is_gif = str_ends_with(mb_strtolower($file), '.gif');
                $is_png = str_ends_with(mb_strtolower($file), '.png');
                $is_jpg = str_ends_with(mb_strtolower($file), '.jpg');
                if ($is_gif) {
                    // Avoid resizing of GIFs, as they may be animated and that could be problematic.
                    // Optimize GIF with lossless compression.
                    exec("gifsicle --no-warnings -O3 \"{$source_file}\" -o \"{$target_file}\"", $output, $ret);

                    // Restore original file if its size is not bigger than
                    // generated file, or if file was not written.
                    if (!is_file($target_file) || filesize($source_file) <= filesize($target_file)) {
                        copy($source_file, $target_file);
                    }

                    // GIF -> WEBP conversion.
                    $optimized_file = str_ireplace('.gif', '.webp', $target_file);

                    // Only process the file once.
                    if (!is_file($optimized_file)) {
                        // Apply lossless compression.
                        exec("gif2webp -quiet \"{$target_file}\" -o \"{$optimized_file}\"", $output, $ret);

                        // Delete WEBP file if original file is not bigger.
                        $optimized_file_size = filesize($optimized_file);
                        $target_file_size = filesize($target_file);
                        if (
                            is_file($optimized_file)
                            && $optimized_file_size > 0
                            && $target_file_size > 0
                            && ($target_file_size - SIZE_THRESHOLD) <= $optimized_file_size
                        ) {
                            unlink($optimized_file);

                            // Try with lossy compression.
                            exec("gif2webp -quiet -lossy \"{$target_file}\" -o \"{$optimized_file}\"", $output, $ret);
                            $optimized_file_size = filesize($optimized_file);
                            if (
                                is_file($optimized_file)
                                && $optimized_file_size > 0
                                && ($target_file_size - SIZE_THRESHOLD) <= $optimized_file_size
                            ) {
                                unlink($optimized_file);
                            }
                        }
                    }
                } else {
                    // Scale PNG and JPG images if bigger than provided $width.
                    try {
                        $imagick = new Imagick();
                        $imagick->readImage($source_file);
                        if ($imagick->getImageWidth() > $width) {
                            $imagick->scaleImage($width, 0);
                        }
                        $imagick->writeImage($target_file);
                    } catch (Exception) {
                    }

                    // Restore original file if its size is not bigger than the generated file, or if the file was not
                    // written.
                    if (!is_file($target_file) || filesize($source_file) <= filesize($target_file)) {
                        copy($source_file, $target_file);
                    }

                    if ($is_jpg) {
                        // Optimize JPG with lossless compression.
                        exec("jpegoptim --quiet \"{$target_file}\"", $output, $ret);

                        // JPEG -> AVIF conversion.
                        // TODO: Convert animated GIFs and transparent PNGs too, maybe with
                        //       https://github.com/lovell/sharp or
                        //       https://github.com/GoogleChromeLabs/squoosh/tree/dev/libsquoosh.
                        //       But note that 8-bit images may compress better in PNG than in AVIF at this point (see
                        //       https://vincent.bernat.ch/en/blog/2021-webp-avif-nginx) and that AVIF sequences have
                        //       serious compatibility issues in Firefox (see
                        //       https://bugzilla.mozilla.org/show_bug.cgi?id=1686338#c28).
                        $optimized_file = str_ireplace('.jpg', '.avif', $target_file);

                        // Only process the file once.
                        if ($imagick !== null && !is_file($optimized_file)) {
                            try {
                                $imagick->setImageFormat('avif');
                                $imagick->writeImage($optimized_file);

                                // Delete AVIF file if original file is not bigger.
                                $optimized_file_size = filesize($optimized_file);
                                $target_file_size = filesize($target_file);
                                if (
                                    is_file($optimized_file)
                                    && $optimized_file_size > 0
                                    && $target_file_size > 0
                                    && ($target_file_size - SIZE_THRESHOLD) <= $optimized_file_size
                                ) {
                                    unlink($optimized_file);
                                }
                            } catch (Exception) {
                            }
                        }
                    } elseif ($is_png) {
                        // Apply lossy compression to PNGs.
                        $min_quality = PNG_MIN_QUALITY;
                        $max_quality = PNG_MAX_QUALITY;
                        exec("pngquant --skip-if-larger --quality={$min_quality}-{$max_quality} --ext .png --force \"{$target_file}\"", $output, $ret);

                        // Optimize PNG with lossless compression using optipng.
                        exec("optipng -quiet \"{$target_file}\"", $output, $ret);

                        // Optimize PNG with lossless compression using oxipng.
                        // To install oxipng, see https://github.com/shssoichiro/oxipng
                        exec("oxipng --quiet -o3 --strip safe --zopfli \"{$target_file}\"", $output, $ret);

                        // PNG -> WEBP conversion.
                        // Rather than base it in the original file like it is done in the JPG -> AVIF conversion, we
                        // use the optimized PNG instead. This is because pngquant lossy degradation is small, may be
                        // beneficial to webp and we prefer using cweb rather than imagemagick to create the webp
                        // images. The fact that here the images have been resized previously should not impact the
                        // image quality considerably.
                        $optimized_file = str_ireplace('.png', '.webp', $target_file);

                        // Only process the file once.
                        if (!is_file($optimized_file)) {
                            // Create WEBP with lossless compression.
                            exec("cwebp -quiet \"{$target_file}\" -o \"{$optimized_file}\"", $output, $ret);

                            // Delete WEBP file if original file is not bigger.
                            $optimized_file_size = filesize($optimized_file);
                            $target_file_size = filesize($target_file);
                            if (
                                is_file($optimized_file)
                                && $optimized_file_size > 0
                                && $target_file_size > 0
                                && ($target_file_size - SIZE_THRESHOLD) <= $optimized_file_size
                            ) {
                                unlink($optimized_file);

                                // Try with lossy compression.
                                exec("cwebp -quiet -q 60 \"{$target_file}\" -o \"{$optimized_file}\"", $output, $ret);
                                $optimized_file_size = filesize($optimized_file);
                                if (
                                    is_file($optimized_file)
                                    && $optimized_file_size > 0
                                    && ($target_file_size - SIZE_THRESHOLD) <= $optimized_file_size
                                ) {
                                    unlink($optimized_file);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
