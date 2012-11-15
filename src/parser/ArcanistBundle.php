  private $author;

  public function setAuthor($author) {
    $this->author = $author;
    return $this;
  }
  public function getAuthor() {
    return $this->author;
  }
      $author        = idx($meta_info, 'author');
      $author        = null;
      'version'      => 4,
      'author'       => $this->getAuthor(),
    $binary_sources = array();
    foreach ($changes as $change) {
      if (!$this->isGitBinaryChange($change)) {
        continue;
      }

      $type = $change->getType();
      if ($type == ArcanistDiffChangeType::TYPE_MOVE_AWAY ||
          $type == ArcanistDiffChangeType::TYPE_COPY_AWAY ||
          $type == ArcanistDiffChangeType::TYPE_MULTICOPY) {
        foreach ($change->getAwayPaths() as $path) {
          $binary_sources[$path] = $change;
        }
      }
    }

      $is_binary = $this->isGitBinaryChange($change);
        $old_binary = idx($binary_sources, $this->getCurrentPath($change));
        $change_body = $this->buildBinaryChange($change, $old_binary);
          $type == ArcanistDiffChangeType::TYPE_COPY_AWAY ||
          $type == ArcanistDiffChangeType::TYPE_CHANGE) {
  private function isGitBinaryChange(ArcanistDiffChange $change) {
    $file_type = $change->getFileType();
    return ($file_type == ArcanistDiffChangeType::FILE_BINARY ||
            $file_type == ArcanistDiffChangeType::FILE_IMAGE);
  }

  private function getBlob($phid, $name = null) {
    $console = PhutilConsole::getConsole();

      if ($name) {
        $console->writeErr("Downloading binary data for '%s'...\n", $name);
      } else {
        $console->writeErr("Downloading binary data...\n");
      }
  private function buildBinaryChange(ArcanistDiffChange $change, $old_binary) {
    // In Git, when we write out a binary file move or copy, we need the
    // original binary for the source and the current binary for the
    // destination.

    if ($old_binary) {
      if ($old_binary->getOriginalFileData() !== null) {
        $old_data = $old_binary->getOriginalFileData();
        $old_phid = null;
      } else {
        $old_data = null;
        $old_phid = $old_binary->getMetadata('old:binary-phid');
      }
    } else {
      $old_data = $change->getOriginalFileData();
      $old_phid = $change->getMetadata('old:binary-phid');
    }

    if ($old_data === null && $old_phid) {
      $name = basename($change->getOldPath());
      $old_data = $this->getBlob($old_phid, $name);
    }

    $old_length = strlen($old_data);
    if ($old_data === null) {
    $new_phid = $change->getMetadata('new:binary-phid');

    $new_data = null;
    if ($change->getCurrentFileData() !== null) {
      $new_data = $change->getCurrentFileData();
    } else if ($new_phid) {
      $name = basename($change->getCurrentPath());
      $new_data = $this->getBlob($new_phid, $name);
    }

    $new_length = strlen($new_data);

    if ($new_data === null) {