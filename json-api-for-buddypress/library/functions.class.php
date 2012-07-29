<?php

class JSON_API_FOR_BUDDYPRESS_FUNCTION extends JSON_API_BuddypressRead_Controller {

        protected static $sVars = array ( );

        /**
         * Load the Parameters defined in parameters.php
         * @param String $sModule the module to load
         * @throws Exception if parameters for module aren't defined
         */
        protected static function initVars ( $sModule ) {
                require_once (JSON_API_FOR_BUDDYPRESS_HOME . '/library/parameters.php');

                if ( !isset ( $aParams [ $sModule ] ) )
                        throw new Exception ( "Parameters for module not defined." );

                foreach ( $aParams [ $sModule ] as $sType => $aParameters ) {
                        foreach ( $aParameters as $sValName => $sVal ) {
                                self::$sVars [ $sValName ] = self::getVar ( $sValName, $sVal, $sType );
                        }
                }
        }

        private static function getVar ( $sValName, $sVal, $sType ) {
                global $json_api;
                $mReturnVal = is_null ( $json_api->query->$sValName ) ? $sVal : $json_api->query->$sValName;
                return self::sanitize ( $mReturnVal, $sType );
        }

        /**
         * Method to sanitize the values given
         * @param mixed $mValue Value to sanitize
         * @param String $sType type of the Value given by parameters array
         * @return mixed sanitized value
         */
        private static function sanitize ( $mValue, $sType ) {
                switch ( $sType ) {
                        case "int":
                                if ( $mValue !== false )
                                        $mValue = (int) $mValue;
                                break;
                        case "boolean":
                                $mValue = (boolean) $mValue;
                        case "string":
                        default:
                                switch ( gettype ( $mValue ) ) {
                                        case 'string':
                                                $mValue = strip_tags ( $mValue );
                                                break;
                                        case 'boolean':
                                        default:
                                                break;
                                }
                                break;
                }
                return $mValue;
        }

        /**
         * Returns a String containing an error message
         * @param String $sModule Modules name
         * @param type $iCode Errorcode
         */
        protected static function error ( $sModule, $iCode ) {
                $oReturn = new stdClass();
                $oReturn->status = "error";
                switch ( $sModule ) {
                        case "activity":
                                $oReturn->msg = __ ( 'No Activities found.' );
                                break;
                        case "profile":
                                switch ( $iCode ) {
                                        case 0:
                                                $oReturn->msg = __ ( 'No Profile found.' );
                                                break;
                                        case 1:
                                                $oReturn->msg = __ ( 'Username not found.' );
                                                break;
                                }
                                break;
                        case "message":
                                $oReturn->msg = __ ( 'No messages found.' );
                                break;
                        case "notification":
                                $oReturn->msg = __ ( 'No notifications found.' );
                                break;
                        default:
                                $oReturn->msg = __ ( 'An undefined error occured.' );
                }
                return $oReturn;
        }

}