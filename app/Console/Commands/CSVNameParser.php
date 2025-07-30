<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CSVNameParser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:parser {file_path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parses a CSV file containing homeowner data and outputs the parsed results into separate columns for title, first name, initial, and last name.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // ask user if they want to save the output to a file
        $saveToFile = $this->confirm('Do you want to save the output to a file?', false);
        $fileName = $saveToFile ? $this->ask('Enter the output file name (without extension)', 'homeowners_parsed') : null;
        $path = $this->argument('file_path');

        // if file is not csv return error
        if (pathinfo($path, PATHINFO_EXTENSION) !== 'csv') {
            $this->error("File {$path} is not a CSV file.");
            return 1;
        }
        // if file does not exist, return error
        if (!file_exists($path)) {
            $this->error("CSV File not found: {$path}");
            return 1;
        }

        // map the CSV file to an array
        $rows = array_map(function($line) {
            $cols = str_getcsv($line);
            return $cols[0] ?? null;
        }, file($path));

        if (empty($rows)) {
            $this->error("CSV File is empty: {$path}");
            return 1;
        }

        // ignore the first row (header)
        array_shift($rows);

        $titles = [
            "Mr",
            "Mister",
            "Mrs",
            "Ms",
            "Miss",
            "Mx",
            "Dr",
            "Prof",
        ];

        $people = [];

        foreach ($rows as $row) {
            if(empty($row)) {
                continue; // skip empty rows
            }

            $isTwo = preg_match('/(?:\band\b|&)/i', $row);
            if ($isTwo) {
                $parts = preg_split('/\band\b|&/i', $row);
                $person_1= explode(' ', trim($parts[0]));
                $person_2 = explode(' ', trim($parts[1]));
                $onlyTitle = count($person_1) == 1 && in_array($person_1[0], $titles); // only title
                $sharedName = count($person_1) == 1 && count($person_2) == 2 && in_array($person_2[0], $titles); // shared name with title
                $sharedFullName = count($person_1) == 1 && count($person_2) > 2; // shared single full name
                $separateNames = count($person_1) >= 2 && count($person_2) >= 2; // separate names
                $hasInitials_1 = preg_match('/\b[A-Z]\.?\s/', $parts[0], $matches_1); // initials in first part
                $hasInitials_2 = preg_match('/\b[A-Z]\.?\s/', $parts[1], $matches_2); // initials in second part

                if ($onlyTitle && $sharedName && !$sharedFullName) {
                    $people[] = [ // first person with title & last name only
                        'title' => $person_1[0],
                        'first_name' => null,
                        'initial' => null,
                        'last_name' => end($person_2),
                    ];

                    $people[] = [ // second person with title & last name only
                        'title' => $person_2[0],
                        'first_name' => null,
                        'initial' => null,
                        'last_name' => end($person_2)
                    ];
                    
                } else if ($onlyTitle && !$sharedName && $sharedFullName) {
                    $people[] = [ // first person with title & full name
                        'title' => $person_1[0],
                        'first_name' => $person_2[1] ?? null,
                        'initial' => $hasInitials_1 ? $matches_1[0][0] : null, // keep only initial
                        'last_name' => end($person_2),                        
                    ];

                    $people[] = [ // second person with title & last name only
                        'title' => $person_2[0],
                        'first_name' => null,
                        'initial' => null,
                        'last_name' => end($person_2)
                    ];
                } else if ($separateNames) {
                    $people[] = [ // first person with title & full name
                        'title' => $person_1[0],
                        'first_name' => count($person_1) > 2 ? $person_1[1] : null,
                        'initial' => $hasInitials_1 ? $matches_1[0][0] : null,
                        'last_name' => end($person_1)
                    ];

                    $people[] = [ // second person with title & full name
                        'title' => $person_2[0],
                        'first_name' => $hasInitials_2 ? null : $person_2[1],
                        'initial' => $hasInitials_2 ? $matches_2[0][0] : null,
                        'last_name' => end($person_2),
                    ];
                }

            } else {
                $hasInitials = preg_match('/\b[A-Z]\.?\s/', $row, $matches);
                $parts = explode(' ', $row);
                $hasTitle = in_array($parts[0], $titles);

                $people[] = [
                    'title' => $hasTitle ? $parts[0] : null,
                    'first_name' => $hasInitials ? null : $parts[1],
                    'initial' => $hasInitials ? $matches[0][0] : null,
                    'last_name' => end($parts),
                ];
            }

        }

        if ($saveToFile) {
            if (strtolower(substr($fileName, -4)) !== '.csv') {
                $fileName .= '.csv';
            }
            if (file_exists($fileName)) {
                $this->error("File {$fileName} already exists. Please choose a different name.");
                return 1;
            }
            $file = fopen($fileName, 'w');
            fputcsv($file, ['Title', 'First Name', 'Initial', 'Last Name']);
            foreach ($people as $person) {
                fputcsv($file, $person);
            }
            fclose($file);
            $this->info("Output was saved to {$fileName}");
        } else {
            // Output the result
            $this->table(['Title', 'First Name', 'Initial', 'Last Name'], $people);
            $this->info('Output was not saved to a file.');
        }

        $this->info('CSV parsing completed successfully.');
        return 0;
    }
}
