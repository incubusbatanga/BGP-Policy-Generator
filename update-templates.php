#!/usr/bin/env php
<?php
/*

 Copyright (c) 2014 Marko Dinic <marko@yu.net>. All rights reserved.

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
  echo("Usage: ".basename(realpath(__FILE__))." [--help|-h] [autopolicy_name]\n");
  exit(255);
}

// Skip script name
array_shift($argv);

// Get optional parameters
while(count($argv) > 0) {
  $arg = array_shift($argv);
  if(empty($arg))
    break;
  switch($arg) {
    case '-h':
    case '--help':
      usage();
      break;
    default:
      $args[] = $arg;
      break;
  }
}

// There has to be no more than 1 argument
if(!empty($args) && count($args) > 1)
  usage();

$id = empty($args[0]) ? NULL:$args[0];

// This will hold our own configuration
$config = array('base_dir' => dirname(realpath(__FILE__)));

// Load our own configuration
include $config['base_dir']."/config.php";
// Load common code library
include $config['includes_dir']."/functions.inc.php";

// Update autopolicies
update_templates($id);

?>
