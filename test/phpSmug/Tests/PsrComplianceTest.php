<?php

namespace phpSmug\Tests;

/**
 * @class
 * Test properties of our codebase rather than the actual code.
 */
class PsrComplianceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function testPsrCompliance()
    {
        // If we can't find the command-line tool, we mark the test as skipped
        // so it shows as a warning to the developer rather than passing silently.
        if (!file_exists('vendor/bin/php-cs-fixer')) {
            $this->markTestSkipped(
                'Needs linter to check PSR compliance'
            );
        }
        
        // Run linter in dry-run mode so it changes nothing.
        exec(
            escapeshellcmd('vendor/bin/php-cs-fixer fix --diff -v --dry-run .'),
            $output,
            $return_var
        );

        // If we've got output, pop its first item ("Fixed all files...")
        // shift off the last two lines, and trim whitespace from the rest.
        if ($output) {
            array_pop($output);
            array_shift($output);
            array_shift($output);
            $output = array_map('trim', $output);
        }

        // Check shell return code: if nonzero, report the output as a failure.
        $this->assertEquals(
            0,
            $return_var,
            "PSR linter reported errors in: \n\t".implode("\n\t", $output)
        );
    }
}
