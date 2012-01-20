<?php
/**
 * IndexStorage
 *
 * @package   autoload
 * @author    M.Olszewski
 * @since     2010-03-25
 * @copyright Copyright (c) 2010 by M.Olszewski. All rights reserved.
 */


/**
 * Interface for index storage mechanisms responsible for storing and loading index content
 *
 * Although this interface is very simple each implementation must ensure that stored and loaded content is the same.
 *
 * @author  M.Olszewski
 * @package autoload
 */
interface autoload_IndexStorage
{
  /**
   * Stores given index content.
   *
   * @param array $content Array with index content to store.
   */
  public function store(array $content);

  /**
   * Loads index content.
   *
   * @return array Returns array with index content.
   */
  public function load();
}
