<?php

namespace Asciify;

use Cyclonecode\Plugin\Settings;
use Cyclonecode\Plugin\Singleton;

class Plugin extends Singleton
{

    /**
     * Plugin specific constants.
     */
    const VERSION = '1.1.0';
    const MAIN_MENU_SLUG = 'asciify';
    const IMAGE_METAKEY = 'asciify_full';
    const SETTINGS_NAME = 'asciify_settings';
    const TEXT_DOMAIN = 'asciify';
    const DEFAULT_CAPABILITY = 'manage_options';
    const CAPABILITY_FILTER = 'asciify_cap';

    /**
     * Image related constants.
     */
    const DEFAULT_IMAGE_WIDTH = 300;
    const DEFAULT_IMAGE_HEIGHT = 300;

    /**
     * Font related constants.
     */
    const FONT_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'fonts';
    const DEFAULT_FONT_FILE = 'courier';
    const DEFAULT_FONT_SIZE = 10;
    const DEFAULT_FONT_SPACING = 1;

    /**
     * Text related constants.
     */
    const DEFAULT_BLACK_CHAR = '#';
    const DEFAULT_WHITE_CHAR = ' ';
    const DEFAULT_BACKGROUND_COLOR = '0xffffff';
    const DEFAULT_FOREGROUND_COLOR = '0x000000';
    const REGEXP_HEXADECIMAL = '/0[xX][0-9a-fA-F]+/';

    /**
     * Resize strategy constants.
     */
    const RESIZE_STRATEGY_EXACT = 'exact';
    const RESIZE_STRATEGY_CROP = 'crop';
    const RESIZE_STRATEGY_PORTRAIT = 'portrait';
    const RESIZE_STRATEGY_LANDSCAPE = 'landscape';
    const RESIZE_STRATEGY_FIT = 'fit';
    const RESIZE_STRATEGY_SQUARE = 'square';
    const RESIZE_STRATEGY_FILL = 'fill';
    const RESIZE_STRATEGY_AUTO = 'auto';
    const DEFAULT_RESIZE_STRATEGY = self::RESIZE_STRATEGY_AUTO;

    /**
     * Contains all settings.
     *
     * @var Settings $settings
     */
    private $settings;

    /**
     * Contains all loaded fonts.
     *
     * @var array $fonts
     */
    private $fonts = array();

    /**
     * The capabilities used to use the plugin.
     *
     * @var string $capabilities
     */
    private $capability = self::DEFAULT_CAPABILITY;

    /**
     * Default settings.
     *
     * @var array $default_settings
     */
    private static $default_settings = array(
        'max_width' => self::DEFAULT_IMAGE_WIDTH,
        'max_height' => self::DEFAULT_IMAGE_HEIGHT,
        'black_char' => self::DEFAULT_BLACK_CHAR,
        'white_char' => self::DEFAULT_WHITE_CHAR,
        'monochrome' => true,
        'font_file' => self::DEFAULT_FONT_FILE,
        'font_size' => self::DEFAULT_FONT_SIZE,
        'font_spacing' => self::DEFAULT_FONT_SPACING,
        'background_color' => self::DEFAULT_BACKGROUND_COLOR,
        'foreground_color' => self::DEFAULT_FOREGROUND_COLOR,
        'transparent' => false,
        'auto_insert' => false,
        'scale' => false,
        'resize_strategy' => self::DEFAULT_RESIZE_STRATEGY,
        'pure_text' => false,
        'image_styles' => array(
            'original',
        ),
        'version' => self::VERSION,
    );

    /**
     * Initializes plugin;
     */
    public function init()
    {
        $this->settings = new Settings(self::SETTINGS_NAME);

        // Allow people to change what capability is needed.
        $this->capability =  apply_filters(self::CAPABILITY_FILTER, $this->capability);

        // $this->checkForUpgrade();
        $this->localize();
        $this->addActions();
        $this->addFilters();
        $this->loadFonts();
    }

    /**
     * Check if plugin is upgraded and make sure default settings
     * are added and finally set new version.
     */
    private static function checkForUpgrade()
    {
        $settings = new Settings(self::SETTINGS_NAME);
        if (version_compare($settings->version, self::VERSION, '<')) {
            // Set defaults.
            foreach (self::default_settings as $key => $value) {
                $settings->add($key, $value);
            }
            $settings->set('version', self::VERSION);
            $settings->save();
        }
    }

    /**
     * Localize plugin.
     */
    private function localize()
    {
        load_plugin_textdomain(
            self::TEXT_DOMAIN,
            false,
            dirname(plugin_basename(__FILE__)) . DIRECTORY_SEPARATOR . 'languages'
        );
    }

    /**
     * Register all actions.
     */
    private function addActions()
    {
        //add_action('wp_head', array($this, 'addStyles'), 100);
        add_action('admin_post_asciify_save_settings', array($this, 'saveSettings'));
        add_action('admin_menu', array($this, 'addMenu'));
        add_action(
            'init',
            function () {
                add_image_size(self::IMAGE_METAKEY, 9999, 9999);
            }
        );
    }

    /**
     * Register all filters.
     */
    public function addFilters()
    {
        add_filter('wp_generate_attachment_metadata', array($this, 'onGenerateAttachmentMetaData'));
        add_filter('image_size_names_choose', array($this, 'onImageSizeNamesChoose'));
    }

    /**
     * @param string $size_name  Name of image size.
     * @param mixed  $image_meta Attachment metadata.
     *
     * @return bool
     */
    public static function getImageSizeFromMeta($size_name, $image_meta)
    {
        return (!empty($image_meta['sizes'][$size_name]) ? $image_meta['sizes'][$size_name] : false);
    }

    /**
     * Delete all asciffy image size references from
     * metadata. Notice that references in post_content will NOT
     * be removed as this point.
     */
    private static function cleanAttachmentMetaData()
    {
        global $wpdb;

        include_once ABSPATH . 'wp-admin/includes/image.php';

        $result = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment'");

        foreach ($result as $attachment_id) {
            $metadata = get_post_meta($attachment_id, '_wp_attachment_metadata', true);
            $upload_dir = wp_upload_dir(null, false);
            $image_size = self::getImageSizeFromMeta(self::IMAGE_METAKEY, $metadata);
            if ($image_size) {
                $filename = $upload_dir['basedir'] .
                    DIRECTORY_SEPARATOR .
                    dirname($metadata['file']) .
                    DIRECTORY_SEPARATOR .
                    $metadata['sizes'][self::IMAGE_METAKEY]['file'];
                if (file_exists($filename)) {
                    $delete = apply_filters('wp_delete_file', $filename);
                    if (!empty($delete)) {
                        @unlink($delete);
                    }
                    unset($metadata['sizes'][self::IMAGE_METAKEY]);
                    update_post_meta($attachment_id, '_wp_attachment_metadata', $metadata);
                }
            }
        }
    }

    /**
     * Removes all settings.
     */
    public static function delete()
    {
        delete_option(self::SETTINGS_NAME);
        self::cleanAttachmentMetaData();
    }

    /**
     * Plugin is activated.
     * TODO: verify so this really happens after an upgrade as well.
     */
    public static function activate()
    {
        self::checkForUpgrade();
    }

    /**
     * Add menu option in administration.
     */
    public function addMenu()
    {
        add_management_page(
            __('Asciify', self::TEXT_DOMAIN),
            __('Asciify', self::TEXT_DOMAIN),
            $this->capability,
            self::MAIN_MENU_SLUG,
            array($this, 'settingsPage')
        );
    }

    /**
     * Load all fonts.
     */
    private function loadFonts()
    {
        putenv('GDFONTPATH=' . realpath(self::FONT_DIR));

        $fontDir = opendir(self::FONT_DIR);
        if ($fontDir) {
            while ($fontFile = readdir($fontDir)) {
                if ($fontFile[0] != '.') {
                    $this->fonts[] = self::FONT_DIR . DIRECTORY_SEPARATOR . $fontFile;
                }
            }
            closedir($fontDir);
        }
    }

    /**
     * @param array $sizes
     *
     * @return array
     */
    public function onImageSizeNamesChoose(array $sizes)
    {
        return array_merge(
            $sizes,
            array(
                self::IMAGE_METAKEY => __('Ascii Full Size'),
            )
        );
    }

    /**
     * @param array $metadata
     *
     * @return array
     */
    public function onGenerateAttachmentMetaData(array $metadata)
    {
        $upload_dir = wp_upload_dir(null, false);
        $filename = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $metadata['file'];

        if ($this->settings->get('pure_text')) {
            $this->asciifyText($filename);
        } else {
            $this->asciifyImage($filename, $metadata);
        }

        return $metadata;
    }

    /**
     * Add custom styles for ascii text container.
     */
    public function addStyles()
    {
        $foreground_color = $this->settings->get('foreground_color');
        $font_size = $this->settings->get('font_size');
        $font_file = $this->settings->get('font_file');
        echo '<style>
        .asciify-container {
            font-size: ' . $font_size . 'px;
            font-family: ' . $font_file . ';
            letter-spacing: 1px;
            display: block;
            unicode-bidi: embed;
            font-family: monospace;
            white-space: pre;
            color: ' . str_replace('0x', '#', $foreground_color) . ';
        }
        </style>';
    }

    /**
     * @param resource $image
     * @param int      $new_width
     * @param int      $new_height
     *
     * @return resource
     */
    public function scaleKeepAspectRatio($image, $new_width, $new_height)
    {
        if ($new_width == 0 && $new_height == 0) {
            return $image;
        }
        $old_x = imagesx($image);
        $old_y = imagesy($image);

        if ($old_x > $old_y) {
            $thumb_w = $new_width;
            $thumb_h = $old_y * ($new_height / $old_x);
        } elseif ($old_x < $old_y) {
            $thumb_w = $old_x * ($new_width / $old_y);
            $thumb_h = $new_height;
        } else {
            $thumb_w = $new_width;
            $thumb_h = $new_height;
        }

        $dst_img = imagecreatetruecolor($thumb_w, $thumb_h);

        imagecopyresampled(
            $dst_img,
            $image,
            0,
            0,
            0,
            0,
            $thumb_w,
            $thumb_h,
            $old_x,
            $old_y
        );

        // Delete original image.
        imagedestroy($image);

        return $dst_img;
    }

    /**
     * @param string $filename
     */
    public function asciifyText($filename)
    {
        $image = imagecreatefromstring(file_get_contents($filename));
        if ($this->settings->get('max_width') != 0 || $this->settings->get('max_height') != 0) {
            $image = $this->scaleKeepAspectRatio(
                $image,
                $this->settings->get('max_width'),
                $this->settings->get('max_height')
            );
        }
        $width = imagesx($image);
        $height = imagesy($image);

        // Convert to grayscale and apply full contrast.
        if ($this->settings->get('monochrome')) {
            imagefilter($image, IMG_FILTER_GRAYSCALE);
            imagefilter($image, IMG_FILTER_CONTRAST, -255);
        }

        $font_spacing = $this->settings->get('font_spacing');
        $black_char = $this->settings->get('black_char');
        $white_char = $this->settings->get('white_char');

        $output = '';

        for ($i = 0; $i < $height; $i += $font_spacing) {
            for ($j = 0; $j < $width; $j += $font_spacing) {
                $rgb = imagecolorat($image, $j, $i);
                if ($rgb != 0) {
                    if ($white_char != '') {
                        $output .= $white_char;
                    }
                } else {
                    if ($black_char != '') {
                        $output .= $black_char;
                    }
                }
            }
            $output .= PHP_EOL;
        }

        imagedestroy($image);

        $output = '
        <div class="asciify-container">' . $output . '</div>';
        echo $output;
    }

    /**
     * @param string $filename
     * @param array  $metadata
     *
     * @return bool
     */
    public function asciifyImage($filename, array &$metadata)
    {
        include_once ABSPATH . '/wp-includes/functions.php';

        // Store and set infinite execution time.
        $time_limit = ini_get('max_execution_time');
        set_time_limit(0);

        $image = imagecreatefromstring(file_get_contents($filename));
        if ($this->settings->get('max_width') != 0 || $this->settings->get('max_height') != 0) {
            $image = $this->scaleKeepAspectRatio(
                $image,
                $this->settings->get('max_width'),
                $this->settings->get('max_height')
            );
        }
        $width = imagesx($image);
        $height = imagesy($image);

        // Convert to grayscale and apply full contrast.
        if ($this->settings->get('monochrome')) {
            imagefilter($image, IMG_FILTER_GRAYSCALE);
            imagefilter($image, IMG_FILTER_CONTRAST, -255);
        }

        $info = pathinfo($filename);
        $mime_type = wp_check_filetype($filename);
        $base_filename = $info['filename'] . '-' . $width . 'x' . $height . '-ascii.' . $info['extension'];
        $upload_dir = dirname($filename);
        $ascii_filename = $upload_dir . DIRECTORY_SEPARATOR . $base_filename;
        $font_file = $this->settings->get('font_file');
        $font_size = $this->settings->get('font_size');
        $font_spacing = $this->settings->get('font_spacing');
        $black_char = $this->settings->get('black_char');
        $white_char = $this->settings->get('white_char');
        $foreground_color = $this->settings->get('foreground_color');
        $background_color = $this->settings->get('background_color');

        $ascii = imagecreate(
            $width * $font_size,
            $height * $font_size
        );

        // Setup the text color.
        $red = (hexdec($foreground_color) >> 16) & 0xFF;
        $green = (hexdec($foreground_color) >> 8) & 0XFF;
        $blue = hexdec($foreground_color) & 0xFF;
        $text_color = imagecolorallocate($ascii, $red, $green, $blue);

        // Setup background color.
        $red = (hexdec($background_color) >> 16) & 0xFF;
        $green = (hexdec($background_color) >> 8) & 0XFF;
        $blue = hexdec($background_color) & 0xFF;
        $background_color = imagecolorallocate($ascii, $red, $green, $blue);

        // Make the background color transparent.
        if ($this->settings->get('transparent')) {
            imagecolortransparent($ascii, $background_color);
        }
        imagefill($ascii, 0, 0, $background_color);

        // Set meta data.
        $metadata['sizes'][self::IMAGE_METAKEY] = array(
            'file' => $base_filename,
            'width' => $width * $font_size,
            'height' => $height * $font_size,
            'mime-type' => $mime_type['type'],
        );

        for ($i = 0; $i < $height; $i += $font_spacing) {
            for ($j = 0; $j < $width; $j += $font_spacing) {
                $rgb = imagecolorat($image, $j, $i);
                if ($rgb != 0) {
                    if ($white_char != '') {
                        imagettftext(
                            $ascii,
                            $font_size,
                            0,
                            $j * $font_size,
                            $i * $font_size,
                            $text_color,
                            $font_file,
                            $white_char
                        );
                    }
                } else {
                    if ($black_char != '') {
                        imagettftext(
                            $ascii,
                            $font_size,
                            0,
                            $j * $font_size,
                            $i * $font_size,
                            $text_color,
                            $font_file,
                            $black_char
                        );
                    }
                }
            }
        }

        // Save the image in correct format.
        switch ($mime_type['type']) {
            case 'image/jpeg':
                imagejpeg($ascii, $ascii_filename, apply_filters('jpeg_quality', 90, 'edit_image'));
                break;
            case 'image/png':
                imagepng($ascii, $ascii_filename);
                break;
            case 'image/gif':
                imagegif($ascii, $ascii_filename);
                break;
            default:
                // Unsupported extension.
                break;
        }
        imagedestroy($ascii);
        imagedestroy($image);

        // Reset max_execution_time.
        set_time_limit($time_limit);
    }

    /**
     * Update settings based on values sent from
     * configuration form.
     */
    public function saveSettings()
    {
        // Validate so user has correct privileges.
        if (!current_user_can($this->capability)) {
            die(__('You are not allowed to perform this action.', self::TEXT_DOMAIN));
        }

        // Check if settings form is submitted.
        if (filter_input(INPUT_POST, 'asciify-settings', FILTER_SANITIZE_STRING)) {
            // Verify nonce and referer.
            if (check_admin_referer('asciify-settings-action', 'asciify-settings-nonce')) {
                // Filter and sanitize form values.
                $this->settings->max_width = filter_input(
                    INPUT_POST,
                    'max_width',
                    FILTER_VALIDATE_INT,
                    array(
                        'options' => array(
                            'default' => self::DEFAULT_IMAGE_WIDTH,
                            'min_range' => 0,
                            'max_range' => 999,
                        ),
                    )
                );
                $this->settings->max_height = filter_input(
                    INPUT_POST,
                    'max_height',
                    FILTER_VALIDATE_INT,
                    array(
                        'options' => array(
                            'default' => self::DEFAULT_IMAGE_HEIGHT,
                            'min_range' => 0,
                            'max_range' => 999,
                        ),
                    )
                );
                $this->settings->font_size = (int) filter_input(
                    INPUT_POST,
                    'font_size',
                    FILTER_VALIDATE_INT,
                    array(
                        'options' => array(
                            'default' => self::DEFAULT_FONT_SIZE,
                            'min_range' => 1,
                            'max_range' => 100,
                        ),
                    )
                );
                $this->settings->black_char = filter_input(INPUT_POST, 'black_char', FILTER_SANITIZE_STRING);
                if (ord($this->settings->black_char) < 1) {
                    $this->settings->black_char = self::DEFAULT_BLACK_CHAR;
                }
                $this->settings->white_char = filter_input(INPUT_POST, 'white_char', FILTER_SANITIZE_STRING);
                if (ord($this->settings->white_char) < 1) {
                    $this->settings->white_char = self::DEFAULT_WHITE_CHAR;
                }
                $this->settings->foreground_color = filter_input(
                    INPUT_POST,
                    'foreground_color',
                    FILTER_VALIDATE_REGEXP,
                    array(
                        'options' => array(
                            'regexp' => self::REGEXP_HEXADECIMAL,
                        ),
                    )
                ) | self::DEFAULT_FOREGROUND_COLOR;
                $this->settings->background_color = filter_input(
                    INPUT_POST,
                    'background_color',
                    FILTER_VALIDATE_REGEXP,
                    array(
                        'options' => array(
                            'regexp' => self::REGEXP_HEXADECIMAL,
                        ),
                    )
                ) | self::DEFAULT_BACKGROUND_COLOR;
                $this->settings->font_spacing = filter_input(
                    INPUT_POST,
                    'font_spacing',
                    FILTER_VALIDATE_INT,
                    array(
                        'options' => array(
                            'default' => 1,
                            'min_range' => 1,
                            'max_range' => 20,
                        ),
                    )
                );
                $this->settings->font_file = filter_input(INPUT_POST, 'font_file', FILTER_SANITIZE_STRING);
                if (!in_array(self::FONT_DIR . DIRECTORY_SEPARATOR . $this->settings->font_file . '.ttf', $this->fonts)) {
                    $this->settings->font_file = self::DEFAULT_FONT_FILE;
                }
                $this->settings->transparent = (bool) filter_input(INPUT_POST, 'transparent', FILTER_VALIDATE_BOOLEAN);
                $this->settings->monochrome = (bool) filter_input(INPUT_POST, 'monochrome', FILTER_VALIDATE_BOOLEAN);
                //$this->settings->pure_text = (bool) filter_input(INPUT_POST, 'pure_text', FILTER_VALIDATE_BOOLEAN);
                /*
                $this->settings->resize_strategy = filter_input(INPUT_POST, 'resize_strategy', FILTER_SANITIZE_STRING);
                $strategies = array_keys($this->getResizeStrategies());
                if (!in_array($this->settings->resize_strategy, $strategies)) {
                    $this->settings->resize_strategy = self::DEFAULT_RESIZE_STRATEGY;
                }
                */
                $this->settings->save();
            }
        }
        wp_safe_redirect(admin_url('tools.php?page=' . self::MAIN_MENU_SLUG));
    }

    /**
     * @return array
     */
    public function getResizeStrategies()
    {
        return array(
            self::RESIZE_STRATEGY_AUTO => array(
                'label' => __('Auto', self::TEXT_DOMAIN),
            ),
            self::RESIZE_STRATEGY_EXACT => array(
                'label' => __('Exact', self::TEXT_DOMAIN),
            ),
            self::RESIZE_STRATEGY_CROP => array(
                'label' => __('Crop', self::TEXT_DOMAIN),
            ),
            self::RESIZE_STRATEGY_FILL => array(
                'label' => __('Fill', self::TEXT_DOMAIN),
            ),
            self::RESIZE_STRATEGY_FIT => array(
                'label' => __('Fit', self::TEXT_DOMAIN),
            ),
            self::RESIZE_STRATEGY_SQUARE => array(
                'label' => __('Square', self::TEXT_DOMAIN),
            ),
            self::RESIZE_STRATEGY_PORTRAIT => array(
                'label' => __('Portrait', self::TEXT_DOMAIN),
            ),
            self::RESIZE_STRATEGY_LANDSCAPE => array(
                'label' => __('Landscape', self::TEXT_DOMAIN),
            ),
        );
    }

    /**
     * Display the settings page.
     */
    public function settingsPage()
    {
        // $this->updateSettings();
        include_once __DIR__ . '/templates/settings.php';
    }
}
