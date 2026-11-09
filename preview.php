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
 * Outputs the PDF of a certificate already issued to the current user.
 *
 * Unlike mod_customcert's view.php?downloadown=1 this never issues a certificate and does not
 * require a POST request, so it can be used for previews and download links.
 *
 * @package   block_my_certificates
 * @copyright Agiledrop, 2026 <developer@agiledrop.com>
 * @author    Matej Pal <matej.pal@agiledrop.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('customcert', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$customcert = $DB->get_record('customcert', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/customcert:view', $context);

// Only the owner can fetch their own, already issued, certificate.
if (!$DB->record_exists('customcert_issues', ['userid' => $USER->id, 'customcertid' => $customcert->id])) {
    throw new moodle_exception('You have not been issued a certificate');
}

\core\session\manager::write_close();

if (class_exists('\\mod_customcert\\service\\pdf_generation_service')) {
    // Custom Certificate 5.2 and later.
    $template = \mod_customcert\template::from_record(
        (new \mod_customcert\service\template_repository())->get_by_id_or_fail((int)$customcert->templateid)
    );
    \mod_customcert\service\pdf_generation_service::create()->generate_pdf($template, false, (int)$USER->id);
} else {
    // Older Custom Certificate versions.
    $templaterecord = $DB->get_record('customcert_templates', ['id' => $customcert->templateid], '*', MUST_EXIST);
    $template = new \mod_customcert\template($templaterecord);
    $template->generate_pdf(false, $USER->id);
}
exit();
