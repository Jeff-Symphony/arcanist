<?php

/**
 * Runs the tests through maven. 
 * Allows for the use of specific tests (probably...)
 *
 * @group unitrun
 * @concrete-extensible
 */
class SymphonySurefireTestEngine extends ArcanistBaseUnitTestEngine {

  protected $projectRoot;

  /**
   * This test engine supports running all tests.
   */
  protected function supportsRunAllTests() {
    return true;
  }

  /**
   * Main entry point for the test engine.  
   *
   * @return array   Array of test results.
   */
  public function run() {

    $paths = $this->getPaths();
    
    return $this->runAllTests($paths);
  }


  public function runAllTests($specifc_tests) {
    $results = array();

    //clean test-compile
    $results[] = $this->execMaven('clean test-compile');
    if ($this->resultsContainFailures($results)) {
      return array_mergev($results);
    }

    //process-test-classes (aka schema build)
    $results[] = $this->execMaven('process-test-classes');
    if ($this->resultsContainFailures($results)) {
      return array_mergev($results);
    }

    //test (skip schema build)
    $results[] = $this->runTests($specifc_tests);
    $results[] = $this->parseTestResult('spcore/target/surefire-reports');
    return array_mergev($results);
  }

  /**
   * Run maven goal(s)
   *
   * @param  array   String of the goals to run
   * @return array   Array of results.
   */
  private function execMaven($goal) {
    echo "mvn $goal \n";

    $regenerate_start = microtime(true);
    $regenerate_future = new ExecFuture(
      "mvn %C",
      $goal);
    $regenerate_future->setCWD(Filesystem::resolvePath(
      $this->projectRoot));

    $results = array();
    $result = new ArcanistUnitTestResult();
    $result->setName("(running mvn $goal)");

    try {
      list($stdout, $stderr) = $regenerate_future->resolvex();
      $result->setResult(ArcanistUnitTestResult::RESULT_PASS);
    } catch(CommandException $exc) {
      if ($exc->getError() > 1) {
        throw $exc;
      }
      $result->setResult(ArcanistUnitTestResult::RESULT_FAIL);
      $result->setUserdata($exc->getStdout());
    }

    $result->setDuration(microtime(true) - $regenerate_start);
    $results[] = $result;
    return $results;
  }
  

  /**
   * Run tests through maven. 
   * Optionally, specify which tests to run.
   *
   * @return array   Array of results.
   */
  private function runTests($specifc_tests) {
    $this->execMaven('test -Dskip.schema.build=true');

    return array();
  }


  /**
   * Determine whether or not a current set of results 
   * contains any failures.
   *
   * @param  array   Array of results to check.
   * @return bool    If there are any failures in the results.
   */
  private function resultsContainFailures(array $results) {
    $results = array_mergev($results);
    foreach ($results as $result) {
      if ($result->getResult() != ArcanistUnitTestResult::RESULT_PASS) {
        return true;
      }
    }
    return false;
  }

  /**
   * Parses the test results from xUnit.
   *
   * @param  string  The base path to the xml result files.
   * @return array   Test results.
   */
  private function parseTestResult($pathToResults) {
    
    $path = Filesystem::resolvePath($pathToResults);
    $parser = new ArcanistXUnitTestResultParser();
    $results = array();

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    foreach($files as $filename => $object){
      if (!preg_match('/\.xml$/', $filename)) {
        continue;
      }
      $results[] = $parser->parseTestResults(Filesystem::readFile($filename));
    }

    return array_mergev($results);
  }

}