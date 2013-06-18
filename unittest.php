<?php

/*

Copyright (c) 2013 by Matt Zabriskie

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

*/

require_once(dirname(__FILE__) . '/src/TestRunner.php');

// Write the help text to the console
function help() {
    echo 'Usage: unittest testpath' . PHP_EOL . PHP_EOL .
            'Options:' . PHP_EOL .
            '   -h, --help         display this help and exit' . PHP_EOL .
            '   -v, --version      output version information and exit' . PHP_EOL;
}

// Write the version to the console
function version() {
    echo '0.0.1-alpha' . PHP_EOL;
}

// Write an error message to the console
function error($msg) {
    echo 'Error: ' . $msg . PHP_EOL;
}

// Get the index of a value from an array
function array_indexof($val, $arr) {
    for ($i=0; $i<sizeof($arr); $i++) {
        if ($arr[$i] === $val) return $i;
    }
    return -1;
}

// Recursively scan a directory looking for test sources
function recursive_scandir($dir) {
    $result = array();
    foreach (scandir($dir) as $name) {
        $path = $dir . '/' . $name;

        // No hidden paths
        if ($name[0] == '.') {
            continue;
        }
        // No non PHP files
        else if (substr($name, strlen($name) - 4) != '.php') {
            // ... unless it is a directory, then recurs
            if (is_dir($path)) {
                $result = array_merge($result, recursive_scandir($path));
            }
            continue;
        }

        // Only accept paths that are within a test directory
        if (strpos($path, '/test/')) {
            $result[] = $path;
        }
    }
    return $result;
}

function main($argv, $argc) {
    $testpath = null; // TODO this should default to the current directory
    $reporter = null; // TODO this should be taken from args

    if ($argc == 1 || in_array('-h', $argv) || in_array('--help', $argv)) {
        help();
        exit;
    }

    if (in_array('-v', $argv) || in_array('--version', $argv)) {
        version();
        exit;
    }

    $testpath = $argv[1];
    if (!is_dir($testpath)) {
        error('"' . $testpath . '" is not a valid directory');
        exit;
    }

    // Scan testpath for test classes and include them
    foreach (recursive_scandir($testpath) as $path) {
        include_once($path);
    }

    // Loop classes and run tests
    $suite = array();
    foreach (get_declared_classes() as $cls) {
        $ref = new ReflectionClass($cls);
        if ($ref->isSubclassOf('TestCase')) {
            $suite[] = $ref->newInstance();
        }
    }

    // Run test suite
    TestRunner::runTestSuite($suite, $reporter);
}

main($argv, $argc);