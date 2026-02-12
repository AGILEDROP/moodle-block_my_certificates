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
 * PHPUnit tests for block_my_certificates.
 *
 * @package    block_my_certificates
 * @copyright  Agiledrop, 2026 <developer@agiledrop.com>
 * @author     Matej Pal <matej.pal@agiledrop.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_my_certificates;

/**
 * Tests for the My Certificates block.
 *
 * @covers \block_my_certificates\local\certificate_data_provider
 * @covers \block_my_certificates
 */
final class block_my_certificates_test extends \advanced_testcase {
    /**
     * Whether the test environment provides mod_customcert with a PHPUnit generator.
     *
     * @return bool
     */
    private function has_customcert_generator(): bool {
        global $CFG, $DB;

        if (!$DB->record_exists('modules', ['name' => 'customcert', 'visible' => 1])) {
            return false;
        }

        return is_readable($CFG->dirroot . '/mod/customcert/tests/generator/lib.php');
    }

    /**
     * Creates a provider test double with call counters.
     *
     * @param array $issued Returned data for get_issued_for_user().
     * @param array $all Returned data for get_all_certificates().
     * @return \block_my_certificates\local\certificate_data_provider
     */
    private function create_provider_stub(
        array $issued = [],
        array $all = []
    ): \block_my_certificates\local\certificate_data_provider {
        return new class ($issued, $all) extends \block_my_certificates\local\certificate_data_provider {
            /** @var int Number of get_issued_for_user() calls. */
            public int $issuedforusercalls = 0;
            /** @var int Number of get_all_certificates() calls. */
            public int $allcertificatescalls = 0;
            /** @var array */
            private array $issuedforuserresult;
            /** @var array */
            private array $allcertificatesresult;

            /**
             * Constructor.
             *
             * @param array $issued Returned data for get_issued_for_user().
             * @param array $all Returned data for get_all_certificates().
             */
            public function __construct(array $issued, array $all) {
                $this->issuedforuserresult = $issued;
                $this->allcertificatesresult = $all;
            }

            /**
             * Returns mocked issued certificate data for a user.
             *
             * @param int $userid The user ID.
             * @return array Issued certificate rows.
             */
            public function get_issued_for_user(int $userid): array {
                $this->issuedforusercalls++;
                return $this->issuedforuserresult;
            }

            /**
             * Returns mocked certificate definitions.
             *
             * @return array Certificate definition rows.
             */
            public function get_all_certificates(): array {
                $this->allcertificatescalls++;
                return $this->allcertificatesresult;
            }
        };
    }

    /**
     * Creates a block instance that uses the supplied provider and bypasses mod_customcert availability checks.
     *
     * @param \block_my_certificates\local\certificate_data_provider $provider Certificate data provider.
     * @return \block_my_certificates
     */
    private function create_block_with_provider(
        \block_my_certificates\local\certificate_data_provider $provider
    ): \block_my_certificates {
        // Ensure the block class is loaded before creating a test subclass.
        \block_instance('my_certificates');

        return new class ($provider) extends \block_my_certificates {
            /** @var \block_my_certificates\local\certificate_data_provider */
            private \block_my_certificates\local\certificate_data_provider $provider;

            /**
             * Constructor.
             *
             * @param \block_my_certificates\local\certificate_data_provider $provider Certificate data provider.
             */
            public function __construct(\block_my_certificates\local\certificate_data_provider $provider) {
                $this->provider = $provider;
                parent::__construct();
            }

            /**
             * Returns the certificate data provider.
             *
             * @return \block_my_certificates\local\certificate_data_provider
             */
            protected function get_certificate_data_provider(): \block_my_certificates\local\certificate_data_provider {
                return $this->provider;
            }

            /**
             * Forces module availability in block-focused unit tests.
             *
             * @return bool
             */
            protected function is_customcert_available(): bool {
                return true;
            }
        };
    }

    /**
     * Ensure issued certificates are returned with expected fields and order.
     *
     * @covers \block_my_certificates\local\certificate_data_provider::get_issued_for_user
     */
    public function test_get_issued_for_user_returns_ordered_data(): void {
        global $DB;

        $this->resetAfterTest(true);

        $this->assertTrue(
            $this->has_customcert_generator(),
            'mod_customcert generator is required for this test. Ensure CI installs mdjnelson/moodle-mod_customcert.'
        );

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();

        $cert1 = $generator->create_module('customcert', [
            'course' => $course->id,
            'name' => 'Certificate 1',
        ]);
        $cert2 = $generator->create_module('customcert', [
            'course' => $course->id,
            'name' => 'Certificate 2',
        ]);

        $now = time();
        $DB->insert_record('customcert_issues', (object) [
            'customcertid' => $cert1->id,
            'userid' => $user->id,
            'code' => 'CODE1',
            'timecreated' => $now - 3600,
        ]);
        $DB->insert_record('customcert_issues', (object) [
            'customcertid' => $cert2->id,
            'userid' => $user->id,
            'code' => 'CODE2',
            'timecreated' => $now,
        ]);

        $provider = new \block_my_certificates\local\certificate_data_provider();
        $issued = $provider->get_issued_for_user($user->id);

        $this->assertCount(2, $issued);
        $this->assertSame('Certificate 2', $issued[0]['certificate']);
        $this->assertSame('Certificate 1', $issued[1]['certificate']);
        $this->assertArrayHasKey('course', $issued[0]);
        $this->assertArrayHasKey('date', $issued[0]);
        $this->assertArrayHasKey('previewurl', $issued[0]);
        $this->assertArrayHasKey('customcertid', $issued[0]);
        $this->assertArrayHasKey('courseid', $issued[0]);
        $this->assertArrayHasKey('timecreated', $issued[0]);
    }

    /**
     * Ensure certificate name uses the custom certificate template name when available.
     *
     * @covers \block_my_certificates\local\certificate_data_provider::get_issued_for_user
     * @covers \block_my_certificates\local\certificate_data_provider::get_all_certificates
     */
    public function test_provider_uses_template_name_for_certificate_name(): void {
        global $DB;

        $this->resetAfterTest(true);

        $this->assertTrue(
            $this->has_customcert_generator(),
            'mod_customcert generator is required for this test. Ensure CI installs mdjnelson/moodle-mod_customcert.'
        );

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();

        $customcert = $generator->create_module('customcert', [
            'course' => $course->id,
            'name' => 'Activity name',
        ]);

        $customcertrecord = $DB->get_record('customcert', ['id' => $customcert->id], '*', MUST_EXIST);
        $DB->set_field('customcert_templates', 'name', 'Certificate name', ['id' => $customcertrecord->templateid]);

        $DB->insert_record('customcert_issues', (object) [
            'customcertid' => $customcert->id,
            'userid' => $user->id,
            'code' => 'CERTCODE',
            'timecreated' => time(),
        ]);

        $provider = new \block_my_certificates\local\certificate_data_provider();
        $issued = $provider->get_issued_for_user($user->id);
        $allcertificates = $provider->get_all_certificates();

        $this->assertNotEmpty($issued);
        $this->assertSame('Certificate name', $issued[0]['certificate']);

        $allcertificatesbyid = array_column($allcertificates, null, 'id');
        $this->assertArrayHasKey($customcert->id, $allcertificatesbyid);
        $this->assertSame('Certificate name', $allcertificatesbyid[$customcert->id]['name']);
    }

    /**
     * Ensure all certificates include course and view URLs when module exists.
     *
     * @covers \block_my_certificates\local\certificate_data_provider::get_all_certificates
     */
    public function test_get_all_certificates_returns_view_urls(): void {
        $this->resetAfterTest(true);

        $this->assertTrue(
            $this->has_customcert_generator(),
            'mod_customcert generator is required for this test. Ensure CI installs mdjnelson/moodle-mod_customcert.'
        );

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $generator->create_module('customcert', [
            'course' => $course->id,
            'name' => 'Certificate A',
        ]);
        $generator->create_module('customcert', [
            'course' => $course->id,
            'name' => 'Certificate B',
        ]);

        $provider = new \block_my_certificates\local\certificate_data_provider();
        $certs = $provider->get_all_certificates();

        $this->assertCount(2, $certs);
        foreach ($certs as $cert) {
            $this->assertArrayHasKey('courseurl', $cert);
            $this->assertArrayHasKey('viewurl', $cert);
            $this->assertNotEmpty($cert['courseurl']);
            $this->assertNotEmpty($cert['viewurl']);
        }
    }

    /**
     * Ensure all certificates section is controlled by config.
     *
     * @covers \block_my_certificates::get_content
     */
    public function test_all_certificates_section_respects_config(): void {
        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $this->setUser($user);

        $page = new \moodle_page();
        $page->set_url(new \moodle_url('/'));
        $page->set_context(\context_system::instance());
        $page->set_pagelayout('standard');

        $providerdisabled = $this->create_provider_stub([], [[
            'id' => 1,
            'name' => 'Certificate A',
            'courseurl' => '/course/view.php?id=2',
            'viewurl' => '/mod/customcert/view.php?id=10',
        ]]);
        $block = $this->create_block_with_provider($providerdisabled);
        $this->assertInstanceOf(\block_base::class, $block);
        $block->page = $page;
        $block->context = \context_system::instance();
        $block->config = (object) ['showallcertificates' => 0];
        $content = $block->get_content();
        $this->assertNotEmpty($content);
        $this->assertStringNotContainsString('all-certificates-card', $content->text);
        $this->assertSame(1, $providerdisabled->issuedforusercalls);
        $this->assertSame(0, $providerdisabled->allcertificatescalls);

        $providerenabled = $this->create_provider_stub([], [[
            'id' => 1,
            'name' => 'Certificate A',
            'courseurl' => '/course/view.php?id=2',
            'viewurl' => '/mod/customcert/view.php?id=10',
        ]]);
        $block = $this->create_block_with_provider($providerenabled);
        $this->assertInstanceOf(\block_base::class, $block);
        $block->page = $page;
        $block->context = \context_system::instance();
        $block->config = (object) ['showallcertificates' => 1];
        $content = $block->get_content();
        $this->assertNotEmpty($content);
        $this->assertStringContainsString('all-certificates-card', $content->text);
        $this->assertSame(1, $providerenabled->issuedforusercalls);
        $this->assertSame(1, $providerenabled->allcertificatescalls);
    }

    /**
     * Ensure all certificates are not loaded when disabled in config.
     *
     * @covers \block_my_certificates::get_content
     */
    public function test_all_certificates_query_is_skipped_when_disabled(): void {
        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $this->setUser($user);

        $page = new \moodle_page();
        $page->set_url(new \moodle_url('/'));
        $page->set_context(\context_system::instance());
        $page->set_pagelayout('standard');

        $provider = $this->create_provider_stub([], [[
            'id' => 1,
            'name' => 'Certificate X',
            'courseurl' => '/course/view.php?id=2',
            'viewurl' => '/mod/customcert/view.php?id=10',
        ]]);

        $block = $this->create_block_with_provider($provider);
        $block->page = $page;
        $block->context = \context_system::instance();
        $block->config = (object) ['showallcertificates' => 0];
        $content = $block->get_content();

        $this->assertNotEmpty($content);
        $this->assertSame(1, $provider->issuedforusercalls);
        $this->assertSame(0, $provider->allcertificatescalls);
    }

    /**
     * Ensure the default no-certificates message is used when instance text is empty.
     *
     * @covers \block_my_certificates::get_content
     */
    public function test_default_no_certificates_text_is_used_when_empty(): void {
        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $this->setUser($user);

        $page = new \moodle_page();
        $page->set_url(new \moodle_url('/'));
        $page->set_context(\context_system::instance());
        $page->set_pagelayout('standard');

        $provider = $this->create_provider_stub([], []);
        $block = $this->create_block_with_provider($provider);
        $block->page = $page;
        $block->context = \context_system::instance();
        $block->config = (object) [
            'showallcertificates' => 0,
            'text' => [
                'text' => '',
                'format' => FORMAT_HTML,
            ],
        ];

        $content = $block->get_content();

        $this->assertNotEmpty($content);
        $this->assertStringContainsString('You do not have any certificates yet.', $content->text);
    }
}
