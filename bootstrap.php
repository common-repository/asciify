<?php
/**
 * Plugin Name: Asciify
 * Plugin URI: https://wordpress.org/plugins/asciify/
 * Description: Creates ascii representation for uploaded images.
 * Version: 1.1.0
 * Author: Cyclonecode
 * Author URI: https://stackoverflow.com/users/1047662/cyclonecode?tab=profile
 * Copyright: Cyclonecode
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: asciify
 * Domain Path: /languages
 */

namespace Asciify;

require_once __DIR__ . '/vendor/autoload.php';

add_action('plugins_loaded', function () {
    if (!is_admin()) {
        Plugin::getInstance();
    }
});

register_activation_hook(__FILE__, array('Asciify\Plugin', 'activate'));
register_uninstall_hook(__FILE__, array('Asciify\Plugin', 'delete'));
