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
        $this->setAdminUser();

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
        $this->setAdminUser();

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

    /**
     * Hidden, restricted and inaccessible certificates must not be advertised to students.
     *
     * @covers \block_my_certificates\local\certificate_data_provider::get_all_certificates
     */
    public function test_all_certificates_respects_user_access(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $student = $generator->create_user();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $hiddencourse = $generator->create_course(['visible' => 0]);
        $othercourse = $generator->create_course();
        $generator->enrol_user($student->id, $course->id, 'student');
        $generator->enrol_user($student->id, $hiddencourse->id, 'student');
        $visible = $generator->create_module('customcert', ['course' => $course->id]);
        $generator->create_module('customcert', ['course' => $course->id, 'visible' => 0]);
        $generator->create_module('customcert', ['course' => $hiddencourse->id]);
        $generator->create_module('customcert', ['course' => $othercourse->id]);
        $generator->create_module('customcert', [
            'course' => $course->id,
            'availability' => json_encode([
                'op' => '&',
                'c' => [['type' => 'date', 'd' => '>=', 't' => time() + DAYSECS]],
                'showc' => [false],
            ]),
        ]);

        $this->setUser($student);
        $provider = new \block_my_certificates\local\certificate_data_provider();
        $this->assertSame([(int)$visible->id], array_map('intval', array_column($provider->get_all_certificates(), 'id')));

        // A teacher with access to hidden activities still sees them.
        $this->setAdminUser();
        $this->assertCount(5, $provider->get_all_certificates());
    }

    /**
     * Theme defaults and custom background/text pairs must survive colour-mode changes.
     *
     * @covers \block_my_certificates::get_color_settings
     * @covers \block_my_certificates::build_color_style
     * @covers \block_my_certificates::get_contrasting_text_color
     */
    public function test_colour_styles_preserve_theme_defaults_and_contrast(): void {
        $this->resetAfterTest(true);
        \block_instance('my_certificates');
        $block = new class extends \block_my_certificates {
            /**
             * Get the style for an instance configuration.
             *
             * @param array $config Instance settings.
             * @return string Inline style.
             */
            public function style_for(array $config): string {
                $this->config = (object)$config;
                return $this->build_color_style($this->get_color_settings());
            }
        };

        $this->assertSame('', $block->style_for([]));
        $this->assertSame('', $block->style_for(['allcertsitembg' => '#f8fafc']));
        $monotone = $block->style_for(['allcertsfillmode' => 'monotone']);
        $this->assertStringContainsString('--mc-all-card-gradient-end: var(--mc-all-card-gradient-start)', $monotone);
        $this->assertStringNotContainsString('#ffffff', $monotone);
        // An unused end colour must not freeze the default start colour in monotone mode.
        $this->assertSame($monotone, $block->style_for([
            'allcertsfillmode' => 'monotone', 'allcertsgradientend' => '#ff0000',
        ]));

        $light = $block->style_for([
            'allcertscardbg' => '#fffffe',
            'allcertsitembg' => '#ffffff',
            'allcertsitemhoverbg' => '#ffffff',
        ]);
        $this->assertStringContainsString('--mc-all-card-color: #000000;', $light);
        $this->assertStringContainsString('--mc-all-row-color: #000000;', $light);
        $this->assertStringContainsString('--mc-all-row-course-color: #000000;', $light);
        $this->assertStringContainsString('--mc-all-row-hover-color: #000000;', $light);
        $this->assertStringContainsString('--mc-all-row-hover-course-color: #000000;', $light);
        $this->assertStringContainsString('--mc-all-card-gradient-end: #eef2ff;', $light);

        $dark = $block->style_for([
            'allcertsfillmode' => 'monotone',
            'allcertscardbg' => '#111111',
            'allcertsitembg' => '#111111',
            'allcertsitemhoverbg' => '#111111',
        ]);
        $this->assertStringContainsString('--mc-all-card-gradient-end: #111111;', $dark);
        $this->assertStringContainsString('--mc-all-card-color: #ffffff;', $dark);
        $this->assertStringContainsString('--mc-all-row-color: #ffffff;', $dark);
        $this->assertStringContainsString('--mc-all-row-hover-color: #ffffff;', $dark);
        $this->assertSame('', $block->style_for(['allcertsitembg' => 'invalid']));
    }

    /**
     * Explicit palettes remain independent and default mode ignores saved overrides.
     *
     * @covers \block_my_certificates::build_palette_style
     * @covers \block_my_certificates::get_palette_defaults
     * @covers \block_my_certificates::uses_default_colors
     */
    public function test_independent_light_and_dark_palettes(): void {
        $this->resetAfterTest(true);
        \block_instance('my_certificates');
        $block = new class extends \block_my_certificates {
            /**
             * Get styles for the supplied configuration.
             *
             * @param array $config Instance settings.
             * @return string Inline styles.
             */
            public function style_for(array $config): string {
                $this->config = (object)$config;
                return $this->build_color_style($this->get_color_settings());
            }
        };
        $this->assertTrue(\block_my_certificates::uses_default_colors(null));
        $this->assertTrue(\block_my_certificates::uses_default_colors((object)['cardgradientstart' => '#667eea']));
        $this->assertFalse(\block_my_certificates::uses_default_colors((object)['cardgradientstart' => '#ff0000']));
        $this->assertSame('', $block->style_for([
            'usedefaultcolors' => 1, 'cardgradientstart' => '#ff0000', 'dark_cardgradientstart' => '#00ff00',
        ]));

        $config = [
            'usedefaultcolors' => 0,
            'cardfillmode' => 'monotone',
            'cardgradientstart' => '#ffffff',
            'dark_cardfillmode' => 'monotone',
            'dark_cardgradientstart' => '#111111',
            'allcertsitembg' => '#ffffff',
            'dark_allcertsitembg' => '#111111',
            'dark_allcertsgradientdirection' => '90deg',
        ];
        $style = $block->style_for($config);
        $this->assertStringContainsString('--mc-light-card-gradient-end: #ffffff;', $style);
        $this->assertStringContainsString('--mc-dark-card-gradient-end: #111111;', $style);
        $this->assertStringContainsString('--mc-light-card-color: #000000;', $style);
        $this->assertStringContainsString('--mc-dark-card-color: #ffffff;', $style);
        $this->assertStringContainsString('--mc-light-all-row-color: #000000;', $style);
        $this->assertStringContainsString('--mc-dark-all-row-color: #ffffff;', $style);
        $this->assertStringContainsString('--mc-dark-all-card-gradient-direction: 90deg;', $style);
        $this->assertStringContainsString('--mc-light-all-card-gradient-direction: 135deg;', $style);

        $invalid = $block->style_for([
            'usedefaultcolors' => 0,
            'dark_cardgradientstart' => 'red; display:none',
            'dark_cardgradientdirection' => 'invalid',
        ]);
        $this->assertStringNotContainsString('display:none', $invalid);
        $this->assertStringContainsString('--mc-dark-card-gradient-start: #37306b;', $invalid);
        $this->assertStringContainsString('--mc-dark-card-gradient-direction: 135deg;', $invalid);
    }

    /**
     * The real settings form exposes both palettes and preserves legacy overrides.
     *
     * @covers \block_my_certificates_edit_form
     */
    public function test_colour_form_preserves_legacy_settings(): void {
        global $CFG;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        require_once($CFG->dirroot . '/blocks/edit_form.php');
        $block = \block_instance('my_certificates');
        require_once($CFG->dirroot . '/blocks/my_certificates/edit_form.php');
        // Share the global page with TinyMCE plugins when the editor is rendered.
        $page = $GLOBALS['PAGE'];
        $page->set_context(\context_system::instance());
        $page->set_url('/my/index.php');
        $page->set_pagelayout('mydashboard');
        $block->instance = (object)['id' => null, 'parentcontextid' => \context_system::instance()->id];
        $block->page = $page;
        $block->config = (object)['cardgradientstart' => '#ffffff', 'cardfillmode' => 'monotone'];
        $form = new class (null, ['block' => $block, 'page' => $page]) extends \block_my_certificates_edit_form {
            /**
             * Get a field value after defaults have been prepared.
             *
             * @param string $name Field name.
             * @return mixed Field value.
             */
            public function field_value(string $name) {
                return $this->_form->getElement($name)->getValue();
            }
        };
        $form->set_data((object)['parentcontextid' => \context_system::instance()->id]);
        $this->assertSame(0, (int)$form->field_value('config_usedefaultcolors'));
        $this->assertSame('#ffffff', $form->field_value('config_dark_cardgradientstart'));
        $this->assertSame('#764ba2', $form->field_value('config_dark_cardgradientend'));
        $html = $form->render();
        $this->assertStringContainsString('config_usedefaultcolors', $html);
        $this->assertStringContainsString('config_dark_allcertsitembg', $html);

        $data = ['config_usedefaultcolors' => 0, 'config_dark_allcertsitembg' => 'invalid'];
        $this->assertArrayHasKey('config_dark_allcertsitembg', $form->validation($data, []));
        $data['config_usedefaultcolors'] = 1;
        $this->assertArrayNotHasKey('config_dark_allcertsitembg', $form->validation($data, []));
    }
}
