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
            'select',
            'config_cardfillmode',
            get_string('cardfillmode', 'block_my_certificates'),
            [
                'gradient' => get_string('cardfillmode_gradient', 'block_my_certificates'),
                'monotone' => get_string('cardfillmode_monotone', 'block_my_certificates'),
            ]
        );
        $mform->addHelpButton('config_cardfillmode', 'cardfillmode', 'block_my_certificates');
        $mform->setDefault('config_cardfillmode', 'gradient');
        $mform->setType('config_cardfillmode', PARAM_ALPHA);

        $mform->addElement(
            'select',
            'config_cardgradientdirection',
            get_string('cardgradientdirection', 'block_my_certificates'),
            [
                '0deg' => get_string('gradientdir_0', 'block_my_certificates'),
                '45deg' => get_string('gradientdir_45', 'block_my_certificates'),
                '90deg' => get_string('gradientdir_90', 'block_my_certificates'),
                '135deg' => get_string('gradientdir_135', 'block_my_certificates'),
                '180deg' => get_string('gradientdir_180', 'block_my_certificates'),
                '270deg' => get_string('gradientdir_270', 'block_my_certificates'),
            ]
        );
        $mform->addHelpButton('config_cardgradientdirection', 'cardgradientdirection', 'block_my_certificates');
        $mform->setDefault('config_cardgradientdirection', '135deg');
        $mform->setType('config_cardgradientdirection', PARAM_RAW_TRIMMED);

        $mform->addElement(
            'my_certificates_colourpicker',
            'config_cardgradientstart',
            get_string('cardgradientstart', 'block_my_certificates')
        );
        $mform->setDefault('config_cardgradientstart', '#667eea');
        $mform->setType('config_cardgradientstart', PARAM_RAW_TRIMMED);

        $mform->addElement(
            'my_certificates_colourpicker',
            'config_cardgradientend',
            get_string('cardgradientend', 'block_my_certificates')
        );
        $mform->setDefault('config_cardgradientend', '#764ba2');
        $mform->setType('config_cardgradientend', PARAM_RAW_TRIMMED);

        $mform->addElement(
            'select',
            'config_allcertsfillmode',
            get_string('allcertsfillmode', 'block_my_certificates'),
            [
                'gradient' => get_string('cardfillmode_gradient', 'block_my_certificates'),
                'monotone' => get_string('cardfillmode_monotone', 'block_my_certificates'),
            ]
        );
        $mform->addHelpButton('config_allcertsfillmode', 'allcertsfillmode', 'block_my_certificates');
        $mform->setDefault('config_allcertsfillmode', 'gradient');
        $mform->setType('config_allcertsfillmode', PARAM_ALPHA);

        $mform->addElement(
            'select',
            'config_allcertsgradientdirection',
            get_string('allcertsgradientdirection', 'block_my_certificates'),
            [
                '0deg' => get_string('gradientdir_0', 'block_my_certificates'),
                '45deg' => get_string('gradientdir_45', 'block_my_certificates'),
                '90deg' => get_string('gradientdir_90', 'block_my_certificates'),
                '135deg' => get_string('gradientdir_135', 'block_my_certificates'),
                '180deg' => get_string('gradientdir_180', 'block_my_certificates'),
                '270deg' => get_string('gradientdir_270', 'block_my_certificates'),
            ]
        );
        $mform->addHelpButton('config_allcertsgradientdirection', 'allcertsgradientdirection', 'block_my_certificates');
        $mform->setDefault('config_allcertsgradientdirection', '135deg');
        $mform->setType('config_allcertsgradientdirection', PARAM_RAW_TRIMMED);

        $mform->addElement(
            'my_certificates_colourpicker',
            'config_allcertscardbg',
            get_string('allcertscardbg', 'block_my_certificates')
        );
        $mform->setDefault('config_allcertscardbg', '#ffffff');
        $mform->setType('config_allcertscardbg', PARAM_RAW_TRIMMED);

        $mform->addElement(
            'my_certificates_colourpicker',
            'config_allcertsgradientend',
            get_string('allcertsgradientend', 'block_my_certificates')
        );
        $mform->setDefault('config_allcertsgradientend', '#eef2ff');
        $mform->setType('config_allcertsgradientend', PARAM_RAW_TRIMMED);

        $mform->addElement(
            'my_certificates_colourpicker',
            'config_allcertsitembg',
            get_string('allcertsitembg', 'block_my_certificates')
        );
        $mform->setDefault('config_allcertsitembg', '#f8fafc');
        $mform->setType('config_allcertsitembg', PARAM_RAW_TRIMMED);

        $mform->addElement(
            'my_certificates_colourpicker',
            'config_allcertsitemhoverbg',
            get_string('allcertsitemhoverbg', 'block_my_certificates')
        );
        $mform->setDefault('config_allcertsitemhoverbg', '#eef2ff');
        $mform->setType('config_allcertsitemhoverbg', PARAM_RAW_TRIMMED);

        $mform->addElement(
            'my_certificates_colourpicker',
            'config_accentcolor',
            get_string('accentcolor', 'block_my_certificates')
        );
        $mform->setDefault('config_accentcolor', '#6366f1');
        $mform->setType('config_accentcolor', PARAM_RAW_TRIMMED);

        $mform->addElement(
            'html',
            html_writer::div(
                html_writer::tag(
                    'button',
                    get_string('resetcolors', 'block_my_certificates'),
                    [
                        'type' => 'button',
                        'class' => 'btn btn-secondary',
                        'id' => 'id_my_certificates_reset_colors',
                        'onclick' => $this->get_reset_colors_onclick_js(),
                    ]
                ),
                'my-certificates-reset-colors'
            )
        );

        $mform->hideIf('config_cardgradientdirection', 'config_cardfillmode', 'eq', 'monotone');
        $mform->hideIf('config_cardgradientend', 'config_cardfillmode', 'eq', 'monotone');
        $mform->hideIf('config_allcertsgradientdirection', 'config_allcertsfillmode', 'eq', 'monotone');
        $mform->hideIf('config_allcertsgradientend', 'config_allcertsfillmode', 'eq', 'monotone');
    }

    /**
     * Returns default values for all color customization fields.
     *
     * @return array<string, string>
     */
    protected function get_default_color_values(): array {
        return [
            'config_cardfillmode' => 'gradient',
            'config_cardgradientdirection' => '135deg',
            'config_cardgradientstart' => '#667eea',
            'config_cardgradientend' => '#764ba2',
            'config_allcertsfillmode' => 'gradient',
            'config_allcertsgradientdirection' => '135deg',
            'config_allcertscardbg' => '#ffffff',
            'config_allcertsgradientend' => '#eef2ff',
            'config_allcertsitembg' => '#f8fafc',
            'config_allcertsitemhoverbg' => '#eef2ff',
            'config_accentcolor' => '#6366f1',
        ];
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

        $colorfields = [
            'config_cardgradientstart',
            'config_cardgradientend',
            'config_allcertscardbg',
            'config_allcertsgradientend',
            'config_allcertsitembg',
            'config_allcertsitemhoverbg',
            'config_accentcolor',
        ];

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
