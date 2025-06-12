<?php
interface TypeInterface {
  /**
   * Converts the type to the corresponding BSON type
   *
   * @return mixed
   */
  public function toBSONType();
}