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
 * block_my_certificates edit_form.php description here.
 *
 * @package    block_my_certificates
 * @copyright  Agiledrop, 2026 <developer@agiledrop.com>
 * @author     Matej Pal <matej.pal@agiledrop.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/my_certificates/includes/colourpicker.php');

\MoodleQuickForm::registerElementType(
    'my_certificates_colourpicker',
    $CFG->dirroot . '/blocks/my_certificates/includes/colourpicker.php',
    'MoodleQuickForm_my_certificates_colourpicker'
);

/**
 * Block edit form class for the 'block_my_certificates' plugin.
 *
 * This class extends the generic block edit form to provide
 * specific editing capabilities for the 'block_my_certificates' block.
 *
 * It allows administrators or users with the necessary permissions
 * to edit and configure the settings for the block.
 */
class block_my_certificates_edit_form extends block_edit_form {
    /**
     * Prepare block defaults for form fields.
     *
     * This keeps compatibility with older instances where config_text
     * was stored as a plain string before using the editor element.
     *
     * @param stdClass $defaults Default values.
     * @return stdClass
     */
    protected function prepare_defaults(stdClass $defaults): stdClass {
        $defaults = parent::prepare_defaults($defaults);

        $config = new stdClass();
        foreach (get_object_vars($defaults) as $name => $value) {
            if (str_starts_with($name, 'config_')) {
                $config->{substr($name, 7)} = $value;
            }
        }
        $defaults->config_usedefaultcolors = block_my_certificates::uses_default_colors($config) ? 1 : 0;
        $lightdefaults = block_my_certificates::get_palette_defaults();
        $darkdefaults = block_my_certificates::get_palette_defaults('dark');
        if (!isset($config->usedefaultcolors)) {
            // Seed dark settings from legacy overrides without changing untouched theme colours.
            foreach ($lightdefaults as $setting => $value) {
                if (isset($config->{$setting}) && strtolower(trim((string)$config->{$setting})) !== $value) {
                    $darkdefaults[$setting] = $config->{$setting};
                }
            }
            foreach ([['cardgradientstart', 'cardgradientend'], ['allcertscardbg', 'allcertsgradientend']] as $pair) {
                if (
                    $darkdefaults[$pair[0]] !== block_my_certificates::get_palette_defaults('dark')[$pair[0]] ||
                        $darkdefaults[$pair[1]] !== block_my_certificates::get_palette_defaults('dark')[$pair[1]]
                ) {
                    foreach ($pair as $setting) {
                        $darkdefaults[$setting] = $config->{$setting} ?? $lightdefaults[$setting];
                    }
                }
            }
        }
        foreach ($darkdefaults as $setting => $value) {
            $key = 'config_dark_' . $setting;
            if (!property_exists($defaults, $key)) {
                $defaults->{$key} = $value;
            }
        }

        if (!property_exists($defaults, 'config_text')) {
            return $defaults;
        }

        $text = '';
        $format = FORMAT_HTML;

        if (is_array($defaults->config_text)) {
            $text = (string)($defaults->config_text['text'] ?? '');
            $format = (int)($defaults->config_text['format'] ?? FORMAT_HTML);
        } else if (is_object($defaults->config_text)) {
            $text = (string)($defaults->config_text->text ?? '');
            $format = (int)($defaults->config_text->format ?? FORMAT_HTML);
        } else {
            $text = (string)$defaults->config_text;
            if (property_exists($defaults, 'config_format')) {
                $format = (int)$defaults->config_format;
            }
        }

        $defaults->config_text = [
            'text' => $text,
            'format' => $format,
        ];

        return $defaults;
    }

    /**
     * Form definition.
     *
     * @param object $mform The form object to which specific elements will be added.
     * @return void
     */
    protected function specific_definition($mform) {
        // Block title.
        $mform->addElement(
            'text',
            'config_title',
            get_string('blocktitle', 'block_my_certificates')
        );
        $mform->setDefault('config_title', get_string('pluginname', 'block_my_certificates'));
        $mform->setType('config_title', PARAM_TEXT);

        // Text field.
        $editoroptions = [
                'subdirs' => 0,
                'maxbytes' => 0,
                'maxfiles' => 0,
                'enable_filemanagement' => false,
        ];

        $mform->addElement(
            'editor',
            'config_text',
            get_string('no_certificates', 'block_my_certificates'),
            null,
            $editoroptions
        );

        $mform->addHelpButton('config_text', 'no_certificates', 'block_my_certificates');
        $mform->setType('config_text', PARAM_RAW);
        $mform->addRule('config_text', null, 'required', null, 'client');
        $mform->setDefault('config_text', [
            'text' => get_string('default_no_certificates_text', 'block_my_certificates'),
            'format' => FORMAT_HTML,
        ]);

        $mform->addElement(
            'select',
            'config_showallcertificates',
            get_string('showallcertificates', 'block_my_certificates'),
            [
                1 => get_string('yes'),
                0 => get_string('no'),
            ]
        );
        $mform->addHelpButton('config_showallcertificates', 'showallcertificates', 'block_my_certificates');
        $mform->setDefault('config_showallcertificates', 1);

        $mform->addElement(
            'header',
            'config_displayoptions_main',
            get_string('displayoptions_main', 'block_my_certificates')
        );

        $mform->addElement(
            'advcheckbox',
            'config_showcertificatepreview',
            get_string('showcertificatepreview', 'block_my_certificates')
        );
        $mform->addHelpButton('config_showcertificatepreview', 'showcertificatepreview', 'block_my_certificates');
        $mform->setDefault('config_showcertificatepreview', 1);

        $mform->addElement(
            'advcheckbox',
            'config_showcertificatename',
            get_string('showcertificatename', 'block_my_certificates')
        );
        $mform->addHelpButton('config_showcertificatename', 'showcertificatename', 'block_my_certificates');
        $mform->setDefault('config_showcertificatename', 1);

        $mform->addElement(
            'advcheckbox',
            'config_showcoursename',
            get_string('showcoursename', 'block_my_certificates')
        );
        $mform->addHelpButton('config_showcoursename', 'showcoursename', 'block_my_certificates');
        $mform->setDefault('config_showcoursename', 1);

        $mform->addElement(
            'advcheckbox',
            'config_showcertificatedate',
            get_string('showcertificatedate', 'block_my_certificates')
        );
        $mform->addHelpButton('config_showcertificatedate', 'showcertificatedate', 'block_my_certificates');
        $mform->setDefault('config_showcertificatedate', 1);

        $mform->addElement(
            'advcheckbox',
            'config_showdownloadbutton',
            get_string('showdownloadbutton', 'block_my_certificates')
        );
        $mform->addHelpButton('config_showdownloadbutton', 'showdownloadbutton', 'block_my_certificates');
        $mform->setDefault('config_showdownloadbutton', 1);

        $mform->addElement(
            'header',
            'config_displayoptions_all',
            get_string('displayoptions_all', 'block_my_certificates')
        );

        $mform->addElement(
            'advcheckbox',
            'config_showalllistcertificatename',
            get_string('showalllistcertificatename', 'block_my_certificates')
        );
        $mform->addHelpButton('config_showalllistcertificatename', 'showalllistcertificatename', 'block_my_certificates');
        $mform->setDefault('config_showalllistcertificatename', 1);

        $mform->addElement(
            'advcheckbox',
            'config_showalllistcoursename',
            get_string('showalllistcoursename', 'block_my_certificates')
        );
        $mform->addHelpButton('config_showalllistcoursename', 'showalllistcoursename', 'block_my_certificates');
        $mform->setDefault('config_showalllistcoursename', 1);

        $mform->addElement(
            'header',
            'config_colorsettings',
            get_string('colorsettings', 'block_my_certificates')
        );
        $mform->setExpanded('config_colorsettings', false);

        $mform->addElement(
            'advcheckbox',
            'config_usedefaultcolors',
            get_string('usedefaultcolors', 'block_my_certificates')
        );
        $mform->addHelpButton('config_usedefaultcolors', 'usedefaultcolors', 'block_my_certificates');
        $mform->setDefault('config_usedefaultcolors', 1);
        $mform->setType('config_usedefaultcolors', PARAM_BOOL);

        foreach (['light', 'dark'] as $mode) {
            $this->add_palette_fields($mform, $mode);
        }
        $mform->addElement('button', 'config_resetcolors', get_string('resetcolors', 'block_my_certificates'), [
            'onclick' => $this->get_reset_colors_onclick_js(),
        ]);
        $mform->hideIf('config_resetcolors', 'config_usedefaultcolors', 'checked');
    }

    /**
     * Add one palette, retaining the existing field names for light mode.
     *
     * @param MoodleQuickForm $mform Instance form.
     * @param string $mode Light or dark.
     */
    protected function add_palette_fields(MoodleQuickForm $mform, string $mode): void {
        $prefix = 'config_' . ($mode === 'dark' ? 'dark_' : '');
        $heading = $prefix . 'palette';
        $mform->addElement('static', $heading, '', html_writer::tag('h4', get_string($mode . 'colors', 'block_my_certificates')));
        $mform->hideIf($heading, 'config_usedefaultcolors', 'checked');
        // Keep related card options together instead of listing all colour pickers first.
        $fields = [
            'cardfillmode', 'cardgradientdirection', 'cardgradientstart', 'cardgradientend',
            'allcertsfillmode', 'allcertsgradientdirection', 'allcertscardbg', 'allcertsgradientend',
            'allcertsitembg', 'allcertsitemhoverbg', 'accentcolor',
        ];
        $defaults = block_my_certificates::get_palette_defaults($mode);
        foreach ($fields as $setting) {
            $name = $prefix . $setting;
            $label = get_string($setting, 'block_my_certificates');
            if (str_ends_with($setting, 'fillmode')) {
                $options = [
                    'gradient' => get_string('cardfillmode_gradient', 'block_my_certificates'),
                    'monotone' => get_string('cardfillmode_monotone', 'block_my_certificates'),
                ];
                $mform->addElement('select', $name, $label, $options);
                $mform->addHelpButton($name, $setting, 'block_my_certificates');
                $mform->setType($name, PARAM_ALPHA);
            } else if (str_ends_with($setting, 'direction')) {
                $options = [];
                foreach ([0, 45, 90, 135, 180, 270] as $angle) {
                    $options[$angle . 'deg'] = get_string('gradientdir_' . $angle, 'block_my_certificates');
                }
                $mform->addElement('select', $name, $label, $options);
                $mform->addHelpButton($name, $setting, 'block_my_certificates');
                $mform->setType($name, PARAM_RAW_TRIMMED);
            } else {
                $mform->addElement('my_certificates_colourpicker', $name, $label);
                $mform->setType($name, PARAM_RAW_TRIMMED);
            }
            $mform->setDefault($name, $defaults[$setting]);
            $mform->hideIf($name, 'config_usedefaultcolors', 'checked');
        }
        foreach (
            [['cardgradientdirection', 'cardfillmode'], ['cardgradientend', 'cardfillmode'],
                  ['allcertsgradientdirection', 'allcertsfillmode'], ['allcertsgradientend', 'allcertsfillmode']] as $pair
        ) {
            $mform->hideIf($prefix . $pair[0], $prefix . $pair[1], 'eq', 'monotone');
        }
    }

    /**
     * Returns reset values for both palettes.
     *
     * @return array<string, string>
     */
    protected function get_default_color_values(): array {
        $defaults = [];
        foreach (['light', 'dark'] as $mode) {
            $prefix = 'config_' . ($mode === 'dark' ? 'dark_' : '');
            foreach (block_my_certificates::get_palette_defaults($mode) as $setting => $value) {
                $defaults[$prefix . $setting] = $value;
            }
        }
        return $defaults;
    }

    /**
     * Build inline reset JS for the reset colors button.
     *
     * @return string
     */
    protected function get_reset_colors_onclick_js(): string {
        $defaults = json_encode(
            $this->get_default_color_values(),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        return <<<JS
var defaults = {$defaults};
var form = this.closest('form');
if (!form) { return false; }
Object.keys(defaults).forEach(function(field) {
    var element = form.querySelector('[name="' + field + '"]') ||
        form.querySelector('[name="' + field + '[text]"]') ||
        form.querySelector('#id_' + field);
    if (!element) { return; }

    element.value = defaults[field];

    if (window.jQuery) {
        window.jQuery(element).trigger('input').trigger('change');
    } else {
        element.dispatchEvent(new Event('input', {bubbles: true}));
        element.dispatchEvent(new Event('change', {bubbles: true}));
    }

    var colourpicker = element.closest ? element.closest('.form-colourpicker') : null;
    if (!colourpicker) { return; }

    var current = colourpicker.querySelector('.currentcolour');
    var preview = colourpicker.querySelector('.previewcolour');
    if (current) { current.style.backgroundColor = defaults[field]; }
    if (preview) { preview.style.backgroundColor = defaults[field]; }
});
return false;
JS;
    }

    /**
     * Validate color fields.
     *
     * @param array $data Form data.
     * @param array $files File data.
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['config_usedefaultcolors'])) {
            return $errors;
        }

        $colorfields = [
            'config_cardgradientstart',
            'config_cardgradientend',
            'config_allcertscardbg',
            'config_allcertsgradientend',
            'config_allcertsitembg',
            'config_allcertsitemhoverbg',
            'config_accentcolor',
        ];

        $colorfields = array_merge($colorfields, array_map(
            static fn($field) => str_replace('config_', 'config_dark_', $field),
            $colorfields
        ));
        foreach ($colorfields as $field) {
            $value = trim((string)($data[$field] ?? ''));
            if ($value === '') {
                continue;
            }
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
                $errors[$field] = get_string('invalidhexcolor', 'block_my_certificates');
            }
        }

        return $errors;
    }
}
