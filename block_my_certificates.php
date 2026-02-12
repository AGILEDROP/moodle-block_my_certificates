<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Block definition class for the block_my_certificates plugin.
 *
 * @package   block_my_certificates
 * @copyright Agiledrop, 2026 <developer@agiledrop.com>
 * @author    Matej Pal <matej.pal@agiledrop.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * My Certificates block.
 *
 * @package    block_my_certificates
 */
class block_my_certificates extends block_base {
    /**
     * Initialises the block.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_my_certificates');
    }

    /**
     * Specialise this block's title based on instance config.
     */
    public function specialization() {
        if (!empty($this->config->title)) {
            $this->title = format_string($this->config->title, true, ['context' => $this->context]);
        } else {
            $this->title = '';
        }
    }

    /**
     * Indicates that this block has global configuration settings.
     *
     * @return bool False - no global config, only instance config
     */
    public function has_config() {
        return false;
    }

    /**
     * Indicates that this block has instance configuration.
     *
     * @return bool True - allows per-instance configuration
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * Gets the block contents.
     *
     * @return stdClass|null The block HTML.
     */
    public function get_content() {
        global $OUTPUT, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        // Check if customcert module is available.
        if (!$this->is_customcert_available()) {
            $this->content = new stdClass();
            $this->content->text = html_writer::div(
                get_string('customcert_not_available', 'block_my_certificates'),
                'alert alert-warning'
            );
            $this->content->footer = '';
            return $this->content;
        }

        $provider = $this->get_certificate_data_provider();
        $displaysettings = $this->get_display_settings();
        $colorsettings = $this->get_color_settings();
        $showallcertificates = $displaysettings['showallcertificates'];
        $usercertificates = $provider->get_issued_for_user($USER->id);

        $diffuservsallcerts = [];

        if ($showallcertificates) {
            $allcertificates = $provider->get_all_certificates();
            $issuedcustomcertids = array_flip(array_column($usercertificates, 'customcertid'));

            foreach ($allcertificates as $certificate) {
                if (!isset($issuedcustomcertids[$certificate['id']])) {
                    $diffuservsallcerts[] = $certificate;
                }
            }
        }

        if ($displaysettings['showcertificatepreview'] && !empty($usercertificates)) {
            $this->page->requires->js(new moodle_url('/blocks/my_certificates/thirdparty/pdfjs/pdf.min.js'), true);

            $this->page->requires->js_call_amd('block_my_certificates/pdf_preview', 'init', [
                'workersrc' => (new moodle_url('/blocks/my_certificates/thirdparty/pdfjs/pdf.worker.min.js'))->out(false),
            ]);
        }

        $this->content = new stdClass();
        $this->content->footer = '';

        $nocertificatestext = $this->get_no_certificates_text();

        $safehtml = format_text(
            $nocertificatestext['text'],
            $nocertificatestext['format'],
            ['context' => $this->context],
        );

        $data = [
            'usercertificates' => $usercertificates,
            'allcertificates' => $diffuservsallcerts,
            'hasallcertificates' => $showallcertificates && !empty($diffuservsallcerts),
            'nocertificatestext' => $safehtml,
            'showcertificatepreview' => $displaysettings['showcertificatepreview'],
            'showcertificatename' => $displaysettings['showcertificatename'],
            'showcoursename' => $displaysettings['showcoursename'],
            'showcertificatedate' => $displaysettings['showcertificatedate'],
            'showdownloadbutton' => $displaysettings['showdownloadbutton'],
            'showalllistcertificatename' => $displaysettings['showalllistcertificatename'],
            'showalllistcoursename' => $displaysettings['showalllistcoursename'],
            'colorstyle' => $this->build_color_style($colorsettings),
        ];

        $this->content->text = $OUTPUT->render_from_template('block_my_certificates/content', $data);

        return $this->content;
    }

    /**
     * Get no-certificates editor data in a normalized format.
     *
     * @return array{text: string, format: int}
     */
    protected function get_no_certificates_text(): array {
        $default = [
            'text' => get_string('default_no_certificates_text', 'block_my_certificates'),
            'format' => FORMAT_HTML,
        ];

        if (empty($this->config) || !property_exists($this->config, 'text') || !is_array($this->config->text)) {
            return $default;
        }

        $text = trim((string)($this->config->text['text'] ?? ''));
        if (trim($text) === '') {
            return $default;
        }

        return [
            'text' => $text,
            'format' => (int)($this->config->text['format'] ?? $default['format']),
        ];
    }

    /**
     * Defines in which pages this block can be added.
     *
     * @return array of the pages where the block can be added.
     */
    public function applicable_formats() {
        return [
                'admin' => false,
                'site-index' => true,
                'course-view' => true,
                'mod' => false,
                'my' => true,
        ];
    }

    /**
     * Creates the certificate data provider.
     *
     * @return \block_my_certificates\local\certificate_data_provider
     */
    protected function get_certificate_data_provider(): \block_my_certificates\local\certificate_data_provider {
        return new \block_my_certificates\local\certificate_data_provider();
    }

    /**
     * Check if the customcert module is installed and available.
     *
     * @return bool True if customcert module is available, false otherwise.
     */
    protected function is_customcert_available(): bool {
        global $DB;

        static $available = null;

        if ($available === null) {
            $available = $DB->record_exists('modules', ['name' => 'customcert', 'visible' => 1]);
        }

        return $available;
    }

    /**
     * Returns effective display settings with defaults for existing block instances.
     *
     * @return array<string, bool>
     */
    protected function get_display_settings(): array {
        $defaults = [
            'showallcertificates' => true,
            'showcertificatepreview' => true,
            'showcertificatename' => true,
            'showcoursename' => true,
            'showcertificatedate' => true,
            'showdownloadbutton' => true,
            'showalllistcertificatename' => true,
            'showalllistcoursename' => true,
        ];

        $settings = [];
        foreach ($defaults as $setting => $defaultvalue) {
            $settings[$setting] = $this->get_config_bool($setting, $defaultvalue);
        }

        if (!$settings['showalllistcertificatename'] && !$settings['showalllistcoursename']) {
            $settings['showalllistcertificatename'] = true;
        }

        return $settings;
    }

    /**
     * Resolves a boolean setting from block instance config.
     *
     * @param string $setting Setting name.
     * @param bool $defaultvalue Default when not configured.
     * @return bool
     */
    protected function get_config_bool(string $setting, bool $defaultvalue): bool {
        if (empty($this->config) || !property_exists($this->config, $setting)) {
            return $defaultvalue;
        }

        return !empty($this->config->{$setting});
    }

    /**
     * Returns configured color settings with safe defaults.
     *
     * @return array<string, string>
     */
    protected function get_color_settings(): array {
        $defaults = [
            'cardgradientstart' => '#667eea',
            'cardgradientend' => '#764ba2',
            'allcertscardbg' => '#ffffff',
            'allcertsgradientend' => '#eef2ff',
            'allcertsitembg' => '#f8fafc',
            'allcertsitemhoverbg' => '#eef2ff',
            'accentcolor' => '#6366f1',
        ];

        $settings = [];
        foreach ($defaults as $setting => $defaultvalue) {
            $rawvalue = $defaultvalue;
            if (!empty($this->config) && property_exists($this->config, $setting)) {
                $rawvalue = (string)$this->config->{$setting};
            }
            $settings[$setting] = $this->normalize_hex_color($rawvalue, $defaultvalue);
        }

        $fillmode = 'gradient';
        if (!empty($this->config) && property_exists($this->config, 'cardfillmode')) {
            $fillmode = $this->normalize_card_fill_mode((string)$this->config->cardfillmode);
        }

        $direction = '135deg';
        if (!empty($this->config) && property_exists($this->config, 'cardgradientdirection')) {
            $direction = $this->normalize_gradient_direction((string)$this->config->cardgradientdirection);
        }

        $allcertsfillmode = 'gradient';
        if (!empty($this->config) && property_exists($this->config, 'allcertsfillmode')) {
            $allcertsfillmode = $this->normalize_card_fill_mode((string)$this->config->allcertsfillmode);
        }

        $allcertsdirection = '135deg';
        if (!empty($this->config) && property_exists($this->config, 'allcertsgradientdirection')) {
            $allcertsdirection = $this->normalize_gradient_direction((string)$this->config->allcertsgradientdirection);
        }

        $settings['cardgradientdirection'] = $direction;
        if ($fillmode === 'monotone') {
            $settings['cardgradientend'] = $settings['cardgradientstart'];
        }
        $settings['allcertsgradientdirection'] = $allcertsdirection;
        if ($allcertsfillmode === 'monotone') {
            $settings['allcertsgradientend'] = $settings['allcertscardbg'];
        }

        return $settings;
    }

    /**
     * Normalize a hex color value.
     *
     * @param string $value Raw configured color.
     * @param string $defaultvalue Fallback color.
     * @return string
     */
    protected function normalize_hex_color(string $value, string $defaultvalue): string {
        $value = trim($value);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            return strtolower($value);
        }

        return $defaultvalue;
    }

    /**
     * Normalize card fill mode.
     *
     * @param string $mode Raw configured mode.
     * @return string
     */
    protected function normalize_card_fill_mode(string $mode): string {
        $mode = trim(strtolower($mode));
        if ($mode === 'monotone') {
            return 'monotone';
        }

        return 'gradient';
    }

    /**
     * Normalize gradient direction.
     *
     * @param string $direction Raw configured direction.
     * @return string
     */
    protected function normalize_gradient_direction(string $direction): string {
        $direction = trim(strtolower($direction));
        $alloweddirections = ['0deg', '45deg', '90deg', '135deg', '180deg', '270deg'];

        if (in_array($direction, $alloweddirections, true)) {
            return $direction;
        }

        return '135deg';
    }

    /**
     * Build inline CSS variables for color customization.
     *
     * @param array<string, string> $colors Color settings.
     * @return string
     */
    protected function build_color_style(array $colors): string {
        $vars = [
            '--mc-card-gradient-start' => $colors['cardgradientstart'],
            '--mc-card-gradient-end' => $colors['cardgradientend'],
            '--mc-card-gradient-direction' => $colors['cardgradientdirection'],
            '--mc-all-card-gradient-start' => $colors['allcertscardbg'],
            '--mc-all-card-gradient-end' => $colors['allcertsgradientend'],
            '--mc-all-card-gradient-direction' => $colors['allcertsgradientdirection'],
            '--mc-all-row-bg' => $colors['allcertsitembg'],
            '--mc-all-row-hover-bg' => $colors['allcertsitemhoverbg'],
            '--mc-all-row-hover-border' => $colors['accentcolor'],
            '--mc-accent-color' => $colors['accentcolor'],
        ];

        $parts = [];
        foreach ($vars as $name => $value) {
            $parts[] = $name . ': ' . $value;
        }

        return implode('; ', $parts) . ';';
    }
}
