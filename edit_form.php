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
     * Form definition.
     *
     * @param object $mform The form object to which specific elements will be added.
     * @return void
     */
    protected function specific_definition($mform) {
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
        $mform->setType('config_text', PARAM_CLEANHTML);
        $mform->addRule('config_text', null, 'required', null, 'client');

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
    }
}
