<?php

class JSON_API_for_BuddyPress_Base {

        protected $sVars = array ( );
        protected $oReturn = "";

        public function __construct () {
                $this->oReturn = new stdClass();
        }

        /**
         * Load the Parameters defined in parameters.php
         * @param String $sModule the module to load
         * @throws Exception if parameters for module aren't defined
         */
        protected function initVars ( $sModule ) {
                require_once (JSON_API_FOR_BUDDYPRESS_HOME . '/library/parameters.php');

                if ( !isset ( $aParams [ $sModule ] ) )
                        throw new Exception ( "Parameters for module not defined." );

                foreach ( $aParams [ $sModule ] as $sType => $aParameters ) {
                        foreach ( $aParameters as $sValName => $sVal ) {
                                $this->sVars [ $sValName ] = $this->getVar ( $sValName, $sVal, $sType );
                        }
                }
        }

        private function getVar ( $sValName, $sVal, $sType ) {
                global $json_api;
                $mReturnVal = is_null ( $json_api->query->$sValName ) ? $sVal : $json_api->query->$sValName;
                return $this->sanitize ( $mReturnVal, $sType );
        }

        /**
         * Method to sanitize the values given
         * @param mixed $mValue Value to sanitize
         * @param String $sType type of the Value given by parameters array
         * @return mixed sanitized value
         */
        private function sanitize ( $mValue, $sType ) {
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
        protected function error ( $sModule, $iCode ) {
                $this->oReturn->status = "error";
                switch ( $sModule ) {
                        case "activity":
                                $this->oReturn->msg = __ ( 'No Activities found.' );
                                break;
                        case "profile":
                                switch ( $iCode ) {
                                        case 0:
                                                $this->oReturn->msg = __ ( 'No Profile found.' );
                                                break;
                                        case 1:
                                                $this->oReturn->msg = __ ( 'Username not found.' );
                                                break;
                                }
                                break;
                        case "message":
                                $this->oReturn->msg = __ ( 'No messages found.' );
                                break;
                        case "notification":
                                $this->oReturn->msg = __ ( 'No notifications found.' );
                                break;
                        default:
                                $this->oReturn->msg = __ ( 'An undefined error occured.' );
                }
                $this->oReturn->msg = isset ( $this->oReturn->msg ) ? $this->oReturn->msg : __ ( 'An undefined error occured.' );
                return $this->oReturn;
        }

}