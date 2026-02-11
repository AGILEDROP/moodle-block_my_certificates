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
    }
}
