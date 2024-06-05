<?php

use Robo\Tasks;

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see https://robo.li/
 */
class RoboFile extends Tasks {

  const DRUPAL_ROOT = 'web';

  /**
   * Run all test suites for project at once.
   */
  public function tests():void {
    $this->testsBehat();
  }

  /**
   * Run all behat tests.
   */
  public function testsBehat($profile = 'default'):void {
    $this->taskExec('cd tests/behat && composer install')->run();
    $this->taskBehat('tests/behat/bin/behat')
      ->format('progress') // Can be changed to 'progress'.
      ->noInteraction()
      ->config('tests/behat/behat.yml')
      ->option('tags', '~breaking')
      ->option('tags', '~wip')
      ->option('profile', $profile)
      ->run();
  }

}
