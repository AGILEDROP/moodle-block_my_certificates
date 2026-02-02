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
 * @copyright Agiledrop, 2026  <developer@agiledrop.com>
 * @author    Matej Pal <matej.pal@agiledrop.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 *
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
     * Gets the block contents.
     *
     * @return stdClass|null The block HTML.
     */
    public function get_content() {
        global $OUTPUT, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $allcertificates = $this->get_all_certificates();

        $usercertificates = $this->get_issued_for_user($USER->id);

        $diffuservsallcerts = [];

        foreach ($allcertificates as $certificate) {
            if (!in_array($certificate['id'], array_column($usercertificates, 'customcertid'))) {
                $diffuservsallcerts[] = $certificate;
            }
        }

        $this->page->requires->js(new moodle_url('/blocks/my_certificates/js/pdf.min.js'), true);

        $this->page->requires->js_call_amd('block_my_certificates/pdf_preview', 'init', [
            'workersrc' => (new moodle_url('/blocks/my_certificates/js/pdf.worker.min.js'))->out(false),
        ]);

        $this->content = new stdClass();
        $this->content->footer = '';

        $nocertificatestext = $this->config->text ?? ['text' => '', 'format' => FORMAT_HTML];

        $safehtml = format_text(
                $nocertificatestext['text'],
                $nocertificatestext['format'],
                ['context' => $this->context],
        );

        $data = [
            'usercertificates' => $usercertificates,
            'allcertificates' => $diffuservsallcerts,
            'nocertificatestext' => $safehtml,
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
     * Returns all certificates issued to a specific user, ordered by the most recent first.
     *
     * Builds a render-friendly structure for each issue, including certificate and course
     * metadata, formatted date, and direct links to download and verify the certificate.
     *
     * @param int $userid The Moodle user ID whose certificate issues will be retrieved.
     * @return array List of certificate issue data.
     */
    protected function get_issued_for_user(int $userid): array {
        global $DB;

        $sql = "SELECT ci.id AS issueid,
                   ci.timecreated,
                   ci.code,
                   cc.id AS customcertid,
                   cc.name AS certname,
                   c.id AS courseid,
                   c.fullname AS coursename,
                   cm.id AS cmid
              FROM {customcert_issues} ci
              JOIN {customcert} cc ON cc.id = ci.customcertid
              JOIN {course} c ON c.id = cc.course
              JOIN {course_modules} cm ON cm.instance = cc.id
              JOIN {modules} m ON m.id = cm.module AND m.name = 'customcert'
             WHERE ci.userid = :userid
          ORDER BY ci.timecreated DESC";

        $records = $DB->get_records_sql($sql, ['userid' => $userid]);

        $out = [];
        foreach ($records as $r) {
            $previewurl = new moodle_url('/mod/customcert/view.php', ['id' => $r->cmid, 'downloadown' => 1]);

            $out[] = [
                    'certificate' => $r->certname,
                    'course' => $r->coursename,
                    'courseid' => $r->courseid,
                    'customcertid' => $r->customcertid,
                    'timecreated' => $r->timecreated,
                    'date' => userdate($r->timecreated, get_string('strdaymonthyear', 'block_my_certificates')),
                    'previewurl' => $previewurl->out(false),
            ];
        }
        return $out;
    }

    /**
     * Returns a list of all custom certificate definitions available site-wide.
     *
     * Each item includes the certificate ID, name, a link to the course page, and a link
     * to the certificate activity view page when the course module exists (empty string otherwise).
     *
     * @return array List of certificate metadata.
     */
    protected function get_all_certificates(): array {
        global $DB;

        $certificates = $DB->get_records('customcert', null, '', 'id, name, course');

        $certificatesdata = [];
        foreach ($certificates as $certificate) {
            $courseurl = (new moodle_url('/course/view.php', ['id' => $certificate->course]))->out(false);

            if ($cm = get_coursemodule_from_instance('customcert', $certificate->id, $certificate->course, false, IGNORE_MISSING)) {
                $viewurl = (new moodle_url('/mod/customcert/view.php', ['id' => $cm->id]))->out(false);
            }

            $certificatesdata[] = [
                    'id' => $certificate->id,
                    'name' => $certificate->name,
                    'courseurl' => $courseurl,
                    'viewurl' => $viewurl ?? '',
            ];
        }

        return $certificatesdata;
    }
}
