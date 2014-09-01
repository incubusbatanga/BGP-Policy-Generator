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

include_once $config['includes_dir'].'/git.inc.php';

function vcs_chdir($dir)
{
  if(empty($dir) || !is_dir($dir))
    return;

  $cwd = getcwd();
  chdir($dir);
  return $cwd;
}

function vcs_is_repository($path)
{
  // Change to repo dir
  $cwd = vcs_chdir($path);
  // Check GIT status
  $res = git_status();
  // Change back to prev dir
  vcs_chdir($cwd);
  return $res;
}

function vcs_init_repository($path)
{
  global $config;

  $res = false;

  // If templates dir is not a GIT repository ...
  if(!vcs_is_repository($path)) {
    // ... initialize it as one
    $res = git_init($path);
    if($res)
      // Create symlink for commit hook
      symlink($config['base_dir'].'/exec-on-change.php',
              $config['templates_dir'].'/.git/hooks/post-commit');
  }

  return $res;
}

function vcs_changed_files()
{
  // Get short log output
  $files = git_log('-1 --name-only --oneline');
  if(is_array($files))
    // Skip commit message line
    array_shift($files);
  return $files;
}

function vcs_reset($path=NULL)
{
  return git_reset($path);
}

function vcs_commit($targets, $message)
{
  global $config;

  // Don't wast time
  if(empty($targets) || empty($message))
    return false;

  // Make sure we always iterate over an array
  if(!is_array($targets))
    $targets = array($targets);

  // Change to repo dir
  $cwd = vcs_chdir($config['templates_dir']);

  // Add all given targets to staging area
  foreach($targets as $target) {
    if($target == $config['templates_dir'])
      $target = ".";
    if(!git_add($target)) {
      // If a single addition failed,
      // reset staging area, and abort
      git_reset();
      // Change back to prev dir
      vcs_chdir($cwd);
      return false;
    }
  }

  $author = isset($config['my_name']) ? $config['my_name']:NULL;
  $email = isset($config['my_email']) ? $config['my_email']:NULL;

  // Commit staged changes
  $res = git_commit($message, $author, $email);

  // If commit succeeded ...
  if($res) {
    // 1. Notify configured recipients
    if($config['notify_changes'] && !empty($config['notify_email'])) {
      // Prepare recipients list
      $recipients = is_array($config['notify_email']) ?
                      implode(',', $config['notify_email']):
                      $config['notify_email'];
      // Include the list of changed files ?
      if($config['notify_files']) {
        // Get the lsit of changed files
        $files = vcs_changed_files();
        if(!empty($files))
          // Prepare message
          $message .= "\nFiles that have changed:\n\n".implode("\n", $files)."\n";
      }
      // Include diff in notification message ?
      if($config['notify_detail']) {
        // Get changes since previous commit
        $diff = git_diff('HEAD^1');
        if(!empty($diff))
          // Prepare message
          $message .= "\nChanges that have occured:\n\n".implode("\n", $diff)."\n";
      }
      // Send notification email
      mail($recipients,
           "[BGP Policy Generator] Policy templates updated",
           $message,
           "From: ".$author." <".$email.">");
    }
    // 2. Set the owner of templates dir to the httpd user
    $path = isset($config['templates_dir']) ? $config['templates_dir']:NULL;
    if(isset($path)) {
      $user = isset($config['user']) ? $config['user']:NULL;
      $group = isset($config['group']) ? $config['group']:NULL;
      // Chown templates dir
      if(!empty($user) && posix_getpwnam($user) !== FALSE) {
        exec('chown -R '.$user.' '.$path, $o, $r);
        if($r == 0)
          exec('chmod -R u+rw '.$path);
      }
      // Chgrp template dir
      if(!empty($group) && posix_getgrnam($group) !== FALSE) {
        exec('chgrp -R '.$group.' '.$path, $o, $r);
        if($r == 0)
          exec('chmod -R g+rw '.$path);
      }
    }
  }

  // Change back to prev dir
  vcs_chdir($cwd);

  return $res;
}

function vcs_checkout($timestamp=NULL)
{
  global $config;

  // Change to repo dir
  $cwd = vcs_chdir($config['templates_dir']);

  if(isset($timestamp)) {
    $res = false;
    if(preg_match('/^([<>])?(\d+)$/', $timestamp, $t)) {
      // List previous commits
      if(empty($t[1]) || $t[1] == '<')
        $commits = git_list_before($t[2]);
      elseif($t[1] == '>')
        $commits = git_list_after($t[2]);
      // Checkout first listed commit
      if(!empty($commits))
        $res = git_checkout($commits[0]);
    }
  } else
    $res = git_checkout('master');

  // Change back to prev dir
  vcs_chdir($cwd);

  return $res;
}

?>