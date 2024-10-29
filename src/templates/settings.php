<div class="wrap">
    <h1><?php _e('Asciify Settings', self::TEXT_DOMAIN); ?></h1>
    <form action="<?php echo admin_url('admin-post.php'); ?>" method="POST">
        <?php wp_nonce_field('asciify-settings-action', 'asciify-settings-nonce'); ?>
        <input type="hidden" name="action" value="asciify_save_settings" />
        <table class="form-table">
            <tr>
                <th><label for=""><?php _e('Max width', self::TEXT_DOMAIN); ?></label></th>
                <td>
                    <input type="number" min="0" name="max_width" value="<?php echo $this->settings->get('max_width'); ?>" />
                    <p class="description"><?php _e('The maximum width of asciified image. Set this to 0 to allow any width.', self::TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for=""><?php _e('Max height', self::TEXT_DOMAIN); ?></label></th>
                <td>
                    <input type="number" min="0" name="max_height" value="<?php echo $this->settings->get('max_height'); ?>" />
                    <p class="description"><?php _e('The maximum height of asciified image. Set this to 0 to allow any height.', self::TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for=""><?php _e('Fontsize', self::TEXT_DOMAIN); ?></label></th>
                <td>
                    <input type="number" min="1" name="font_size" value="<?php echo $this->settings->get('font_size'); ?>" />
                    <p class="description"><?php _e('The font size. Notice that the width and height will increase depending on this value.', self::TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for=""><?php _e('Black char', self::TEXT_DOMAIN); ?></label></th>
                <td>
                    <input type="text" name="black_char" value="<?php echo $this->settings->get('black_char'); ?>" />
                    <p class="description"><?php _e('The character to use for filled pixels.', self::TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for=""><?php _e('White char', self::TEXT_DOMAIN); ?></label></th>
                <td>
                    <input type="text" name="white_char" value="<?php echo $this->settings->get('white_char'); ?>" />
                    <p class="description"><?php _e('The character to use for unfilled pixels.', self::TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for=""><?php _e('Foreground color', self::TEXT_DOMAIN); ?></label></th>
                <td>
                    <input type="text" name="foreground_color" value="<?php echo $this->settings->get('foreground_color'); ?>" />
                    <p class="description"><?php _e('The text color.', self::TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for=""><?php _e('Background color', self::TEXT_DOMAIN); ?></label></th>
                <td>
                    <input type="text" name="background_color" value="<?php echo $this->settings->get('background_color'); ?>" />
                    <p class="description"><?php _e('The background color.', self::TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for=""><?php _e('Monochrome', self::TEXT_DOMAIN); ?></label></th>
                <td>
                    <input type="checkbox" name="monochrome"<?php checked($this->settings->get('monochrome')); ?> />
                    <p class="description"><?php _e('Create monochrome image from original.', self::TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for=""><?php _e('Transparent', self::TEXT_DOMAIN); ?></label></th>
                <td>
                    <input type="checkbox" name="transparent"<?php checked($this->settings->get('transparent')); ?> />
                    <p class="description"><?php _e('The background color will be transparent.', self::TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <!--
            <tr>
                <th><label for=""><?php _e('Resize strategy', self::TEXT_DOMAIN); ?></label></th>
                <td>
                    <select name="resize_strategy">
                        <option value=""><?php _e('Select', self::TEXT_DOMAIN); ?></option>
                        <?php foreach ($this->getResizeStrategies() as $key => $strategy) : ?>
                        <option value="<?php echo $key; ?>"<?php selected($this->settings->get('resize_strategy') === $key); ?>><?php echo $strategy['label']; ?></option>;
                        <?php endforeach; ?>
                        ?>
                        </select>
                    <p class="description"><?php _e('The resize strategy to use when resizing images.', self::TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            -->
            <tr>
                <th><label for=""><?php _e('Font file', self::TEXT_DOMAIN); ?></label></th>
                <td>
                    <select name="font_file">
                        <option value=""><?php _e('Select font', self::TEXT_DOMAIN); ?></option>
                        <?php foreach ($this->fonts as $font) : ?>
                            <?php $file = substr_replace(wp_basename($font), '', -4); ?>
                        <option value="<?php echo $file; ?>"<?php selected($this->settings->get('font_file') === $file) ; ?>><?php echo $file; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('The font to use.', self::TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for=""><?php _e('Font spacing', self::TEXT_DOMAIN); ?></label></th>
                <td>
                    <input type="text" name="font_spacing" value="<?php echo $this->settings->get('font_spacing'); ?>" />
                    <p class="description"><?php _e('The number of sequential pixels to check.', self::TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
            <!--
            <tr>
                <th><label for=""><?php _e('Pure text', self::TEXT_DOMAIN); ?></label></th>
                <td>
                    <input type="checkbox" name="pure_text"<?php checked($this->settings->get('pure_text')); ?> />
                    <p class="description"><?php _e('Generate only text.', self::TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            -->
        </table>
        <?php echo get_submit_button(__('Save settings', self::TEXT_DOMAIN), 'primary', 'asciify-settings'); ?>
    </form>
</div>
