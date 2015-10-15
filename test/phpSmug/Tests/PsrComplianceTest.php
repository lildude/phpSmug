<?php

namespace phpSmug\Tests;

/**
 * @class
 * Test properties of our codebase rather than the actual code.
 */
class PsrComplianceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test for all PSR.
     */
    public function testPSR()
    {
        // If we can't find the command-line tool, we mark the test as skipped
        // so it shows as a warning to the developer rather than passing silently.
        if (!file_exists('vendor/bin/php-cs-fixer')) {
            $this->markTestSkipped(
                'Needs linter to check PSR compliance'
            );
        }

        // Let's check all PSR compliance for our code and tests.
        // Add any other pass you want to test to this array.
        foreach (array('lib/', 'test/') as $path) {
            // Run linter in dry-run mode so it changes nothing.
            exec(
                'vendor/bin/php-cs-fixer fix --dry-run '
                        .$_SERVER['PWD']."/$path",
                $output,
                $return_var
            );

            // If we've got output, pop its first item ("Fixed all files...")
            // and trim whitespace from the rest so the below makes sense.
            if ($output) {
                array_pop($output);
                $output = array_map('trim', $output);
            }

            // Check shell return code: if nonzero, report the output as a failure.
            $this->assertEquals(
                0,
                $return_var,
                "PSR linter reported errors in $path/: ".implode('; ', $output)
            );
        }
    }
}
