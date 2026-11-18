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

namespace block_my_certificates\local;

/**
 * Data provider for certificates displayed by the My Certificates block.
 *
 * @package   block_my_certificates
 * @copyright Agiledrop, 2026 <developer@agiledrop.com>
 * @author    Matej Pal <matej.pal@agiledrop.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate_data_provider {
    /**
     * Returns all certificates issued to a specific user, ordered by the most recent first.
     *
     * @param int $userid The Moodle user ID whose certificate issues will be retrieved.
     * @return array List of certificate issue data.
     */
    public function get_issued_for_user(int $userid): array {
        global $DB;

        $sql = "SELECT ci.id AS issueid,
                       ci.timecreated,
                       ci.code,
                       cc.id AS customcertid,
                       cc.name AS activityname,
                       ct.name AS certificatename,
                       c.id AS courseid,
                       c.fullname AS coursename,
                       cm.id AS cmid
                FROM {customcert_issues} ci
                INNER JOIN {customcert} cc ON cc.id = ci.customcertid
                LEFT JOIN {customcert_templates} ct ON ct.id = cc.templateid
                INNER JOIN {course} c ON c.id = cc.course
                INNER JOIN {course_modules} cm ON cm.instance = cc.id AND cm.course = cc.course
                INNER JOIN {modules} m ON m.id = cm.module AND m.name = 'customcert'
                WHERE ci.userid = :userid
                ORDER BY ci.timecreated DESC";

        $records = $DB->get_records_sql($sql, ['userid' => $userid]);

        $out = [];
        foreach ($records as $r) {
            $previewurl = new \moodle_url('/blocks/my_certificates/preview.php', ['id' => $r->cmid]);
            $certificatename = trim((string)($r->certificatename ?? ''));

            $out[] = [
                'certificate' => $certificatename !== '' ? $certificatename : $r->activityname,
                'course' => $r->coursename,
                'courseid' => $r->courseid,
                'customcertid' => $r->customcertid,
                'timecreated' => $r->timecreated,
                'date' => userdate($r->timecreated, get_string('strftimedateshort')),
                'previewurl' => $previewurl->out(false),
            ];
        }

        return $out;
    }

    /**
     * Returns certificate definitions the current user can access.
     *
     * @return array List of certificate metadata.
     */
    public function get_all_certificates(): array {
        global $DB;

        $sql = "SELECT cc.id,
                       cc.course,
                       cc.name AS activityname,
                       ct.name AS certificatename,
                       c.fullname AS coursename
                  FROM {customcert} cc
                  INNER JOIN {course} c ON c.id = cc.course
             LEFT JOIN {customcert_templates} ct ON ct.id = cc.templateid
              ORDER BY c.fullname, cc.name";
        $certificates = $DB->get_records_sql($sql);

        $certificatesdata = [];
        $courses = [];
        foreach ($certificates as $certificate) {
            if (!array_key_exists($certificate->course, $courses)) {
                $course = get_course($certificate->course);
                $courses[$certificate->course] = can_access_course($course, null, '', true);
            }
            if (!$courses[$certificate->course]) {
                continue;
            }

            $modinfo = get_fast_modinfo($certificate->course);
            $instances = $modinfo->get_instances_of('customcert');
            $cm = $instances[$certificate->id] ?? null;
            if (!$cm || !$cm->uservisible || $cm->deletioninprogress) {
                continue;
            }
            if (!has_capability('mod/customcert:view', $cm->context)) {
                continue;
            }

            $courseurl = (new \moodle_url('/course/view.php', ['id' => $certificate->course]))->out(false);
            $viewurl = (new \moodle_url('/mod/customcert/view.php', ['id' => $cm->id]))->out(false);
            $certificatename = trim((string)($certificate->certificatename ?? ''));

            $certificatesdata[] = [
                'id' => $certificate->id,
                'name' => $certificatename !== '' ? $certificatename : $certificate->activityname,
                'course' => $certificate->coursename,
                'courseurl' => $courseurl,
                'viewurl' => $viewurl,
            ];
        }

        return $certificatesdata;
    }
}
