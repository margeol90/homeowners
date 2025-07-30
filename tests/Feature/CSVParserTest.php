<?php

namespace Tests\Feature;

use Tests\TestCase;

class CSVParserTest extends TestCase
{

    /**
     * Test if the command returns an error when a non-existent file is provided.
     * It should return an error message.
     */
    public function testWrongFileProvided()
    {
        $this->artisan('csv:parser does_not_exist.csv')
            ->expectsQuestion('Do you want to save the output to a file?', false)
            ->expectsOutput('CSV File not found: does_not_exist.csv')
            ->assertExitCode(1);
    }

    /**
     * Test if the file provided is not a CSV file.
     * It should return an error message indicating the file is not a CSV.
     */
    public function testFileIsNotCSV()
    {
        $this->artisan('csv:parser not_a_csv.txt')
            ->expectsQuestion('Do you want to save the output to a file?', false)
            ->expectsOutput('File not_a_csv.txt is not a CSV file.')
            ->assertExitCode(1);
    }

    /**
     * Test if the command handles an empty CSV file correctly.
     * It should return an error message indicating the file is empty.
     */
    public function testEmptyCSVFile()
    {
        file_put_contents('empty.csv', '');

        $this->artisan('csv:parser empty.csv')
            ->expectsQuestion('Do you want to save the output to a file?', false)
            ->expectsOutput('CSV File is empty: empty.csv')
            ->assertExitCode(1);

        // clean up
        unlink('empty.csv');
    }

    /**
     * Test if the command processes a valid CSV file correctly.
     * It should parse the file and return the parsed results.
     */
    public function testValidCSVFile()
    {

        $output = $this->artisan('csv:parser examples-284-29-1-.csv')
            ->expectsQuestion('Do you want to save the output to a file?', false)
            ->expectsOutput('Output was not saved to a file.')
            ->expectsOutput('CSV parsing completed successfully.')
            ->assertExitCode(0);
    }

    /**
     * Test if the command saves the output to a file when requested.
     * It should create a new file with the parsed results.
     */
    public function testSaveOutputToFile()
    {
        $newfile = 'homeowners_parsed';
        $this->artisan('csv:parser examples-284-29-1-.csv')
            ->expectsQuestion('Do you want to save the output to a file?', true)
            ->expectsQuestion('Enter the output file name (without extension)', $newfile)
            ->expectsOutput('Output was saved to ' . $newfile . '.csv')
            ->expectsOutput('CSV parsing completed successfully.')
            ->assertExitCode(0);

        // Check if the file was created
        $this->assertFileExists('homeowners_parsed.csv');

        $outputContent = file_get_contents('homeowners_parsed.csv');

        // EOL new line formatting for compatibility
        $expectedContent = 'Title,"First Name",Initial,"Last Name"' . PHP_EOL .
            "Mr,John,,Smith" . PHP_EOL .
            "Mrs,Jane,,Smith" . PHP_EOL .
            "Mister,John,,Doe" . PHP_EOL .
            "Mr,Bob,,Lawblaw" . PHP_EOL .
            "Mr,,,Smith" . PHP_EOL .
            "Mrs,,,Smith" . PHP_EOL .
            "Mr,Craig,,Charles" . PHP_EOL .
            "Mr,,M,Mackie" . PHP_EOL .
            "Mrs,Jane,,McMaster" . PHP_EOL .
            "Mr,Tom,,Staff" . PHP_EOL .
            "Mr,John,,Doe" . PHP_EOL .
            "Dr,,P,Gunn" . PHP_EOL .
            "Dr,Joe,,Bloggs" . PHP_EOL .
            "Mrs,,,Bloggs" . PHP_EOL .
            "Ms,Claire,,Robbo" . PHP_EOL .
            "Prof,Alex,,Brogan" . PHP_EOL .
            "Mrs,Faye,,Hughes-Eastwood" . PHP_EOL .
            "Mr,,F,Fredrickson" . PHP_EOL;
        
        $this->assertEquals($expectedContent, $outputContent);

        // Clean up
        unlink('homeowners_parsed.csv');
    }

    /**
     * Test if the command handles a random file with various name formats.
     * It should parse the file and save the parsed results in a new file.
     */
    public function testRandomFile()
    {
        $this->createCSVFile('random_file.csv', [
            'Mr John Smith',
            'Mrs M. Something',
            '',
            'Mr & Dr John Doe',
            'Miss J. Darnell',
            'Mx Alex Smith',
            '',
            'Mr Robinson and Mrs Y. Smith',
        ]);

        $this->artisan('csv:parser random_file.csv')
            ->expectsQuestion('Do you want to save the output to a file?', true)
            ->expectsQuestion('Enter the output file name (without extension)', 'random_file_results')
            ->assertExitCode(0);

        $this->assertFileExists('random_file_results.csv');
        $outputContent = file_get_contents('random_file_results.csv');

        // EOL new line formatting for compatibility
        $expectedContent = 'Title,"First Name",Initial,"Last Name"' . PHP_EOL .
            "Mr,John,,Smith" . PHP_EOL .
            "Mrs,,M,Something" . PHP_EOL .
            "Mr,John,,Doe" . PHP_EOL .
            "Dr,,,Doe" . PHP_EOL .
            "Miss,,J,Darnell" . PHP_EOL .
            "Mx,Alex,,Smith" . PHP_EOL .
            "Mr,,,Robinson" . PHP_EOL .
            "Mrs,,Y,Smith" . PHP_EOL;
        
        $this->assertEquals($expectedContent, $outputContent);

        // Clean up
        unlink('random_file.csv');
        unlink('random_file_results.csv');
    }

    /**
     * Create a CSV file for testing purposes.
     * @param string $filename
     * @param array $content
     * @return void
     */
    private function createCSVFile($filename, $content)
    {
        $header = ['names'];
        array_unshift($content, implode(',', $header));
        $csvString = implode("\n", $content);
        file_put_contents($filename, $csvString);
    }
}