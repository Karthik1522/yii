<?php

abstract class DBDocumentPHP7 extends EMongoEmbeddedDocument {

	public function offsetExists($offset) 
	{
		if( property_exists($this, $offset) ) {
			return true;
		}
	
		/**
		  * fallback for php7. If property does not exist. check if it's a embededDocument's key
		  */
		$embeded_object_keys = array_keys( $this->embeddedDocuments() );
	
		if( in_array($offset, $embeded_object_keys) ) {
			return true;
		}
	
		return false;
	}

}