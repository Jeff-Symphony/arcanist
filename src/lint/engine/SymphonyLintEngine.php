<?php

/**
 * This lint engine applies the @{class:ArcanistPyLintLinter} to Python 
 * files, and @{class:ArcanistJSHintLinter} to JS files. 
 *
 * @group linter
 */
final class SymphonyLintEngine extends ArcanistLintEngine {

  public function buildLinters() {

    // This is a list of paths to lint. 
    // Either provided them explicitly with `--lintall`,
    // or arc figures them out from a set of changes. 
    $paths = $this->getPaths();

    // Remove any paths that don't exist before we add paths to linters.
    foreach ($paths as $key => $path) {
      if (!$this->pathExists($path)) {
        unset($paths[$key]);
      }
      // exclude certain paths from linter
      if (preg_match('@^externals/@', $path)) {
        unset($paths[$key]);
      }
      if (preg_match('@^lib/@', $path)) {
        unset($paths[$key]);
      }
    }

    $py_paths = preg_grep('/\.py$/', $paths);
    $linters[] = id(new ArcanistPyLintLinter())->setPaths($py_paths);

    $js_paths = preg_grep('/\.js$/', $paths);
    $linters[] = id(new ArcanistJSHintLinter())->setPaths($js_paths);

    return $linters;
  }

}
