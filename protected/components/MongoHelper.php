<?php

class MongoHelper extends EMongoDocument {

	private static $_secondary_read_pref = false;
	private static $_secondary_connection = null;
	public static function model($className = __CLASS__) {
	  return parent::model($className);
	}

	public static function setSecondaryReadPref($secondary = true) {
		self::$_secondary_read_pref = $secondary;
		/* To over ride the existing collection instance */
		self::cleanCache();
		if($secondary) {
			MongoHelper::model()->getMongoDBComponent()
				 				->getDbInstance()
				 				->setReadPreference(Mongo::RP_SECONDARY_PREFERRED);
		}
		else {
			MongoHelper::model()->getMongoDBComponent()
				 				->getDbInstance()
				 				->setReadPreference(Mongo::RP_PRIMARY_PREFERRED);

			self::$_secondary_connection = null;
		}
	}

	public static function readPrefSecondary() {
		return self::$_secondary_read_pref;
	}

	public static function cleanCache()
	{
		self::$_collections = [];
	}
	public function getCollectionName() {
		return 'read_pref_name_not_needed';
	}
    /**
     * Updates readPreference in an existing MongoDB object and sets readPreference to secondary
     *
     * @param MongoDB $db
     *
     * @return mixed
     */
	public static function updateDbReadPreference($db) {
        if (self::$_secondary_connection === null) {
            $read_preference = self::$_secondary_read_pref ? MongoClient::RP_SECONDARY_PREFERRED : MongoClient::RP_PRIMARY_PREFERRED;
            			self::$_secondary_connection = clone $db;
            self::$_secondary_connection->setReadPreference($read_preference);
        }
	    return self::$_secondary_connection;
    }
}