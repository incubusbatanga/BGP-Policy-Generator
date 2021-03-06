#!/usr/bin/env php
<?php
/*

 Copyright (c) 2017 Marko Dinic. All rights reserved.

 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

function usage()
{
  echo("\nBGP Policy Generator v".RELEASE_VERSION." (".RELEASE_DATE.")\n\n");
  echo("Usage: ".basename(realpath(__FILE__))." [--help|-h] [--all|-a|autopolicy1_name[,autopolicy2_name,...]]\n\n");
  exit(255);
}

// Set memory limit high to avoid
// errors with large data sets
ini_set('memory_limit', '3072M');

// This will hold our own configuration
$base_dir = dirname(realpath(__FILE__));
$config = array(
  'base_dir' => $base_dir,
  'includes_dir' => $base_dir."/includes",
  'templates_dir' => $base_dir."/templates"
);

// Load our own defines
include $config['includes_dir']."/defines.inc.php";
// Load our own configuration
include $config['base_dir']."/config.php";
// Load common code library
include $config['includes_dir']."/functions.inc.php";

// Unless debug has 'php' set ...
if(!(debug_mask() & constant('php')))
  // ... suppress PHP messages
  error_reporting(~E_ALL);

// Set the default time zone
date_default_timezone_set(empty($config['timezone']) ? 'UTC':$config['timezone']);

// Skip script name
array_shift($argv);

// There must be at least 1 argument
if(empty($argv))
  usage();

$all = false;
$id = NULL;

// Get optional parameters
while(count($argv) > 0) {
  $arg = array_shift($argv);
  switch($arg) {
    case '-h':
    case '--help':
      usage();
    case '-a':
    case '--all':
      $all = true;
      break;
    default:
      if($all || isset($id))
        usage();
      $id = $arg;
      break;
  }
}

// Update specified autopolicies
update_templates($all ? NULL:$id);

exit(0);

?>
