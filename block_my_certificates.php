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

        $defaultnocertificatestext = [
            'text' => get_string('default_no_certificates_text', 'block_my_certificates'),
            'format' => FORMAT_HTML,
        ];
        $nocertificatestext = $this->config->text ?? $defaultnocertificatestext;

        if (trim((string)($nocertificatestext['text'] ?? '')) === '') {
            $nocertificatestext = $defaultnocertificatestext;
        }

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
        ];

        $this->content->text = $OUTPUT->render_from_template('block_my_certificates/content', $data);

        return $this->content;
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
}
