<?php

class SmartPay_Request {
    /**
     * Singleton instance
     *
     * @var SmartPay_Request $instance
     */
    private static $instance;


    /**
     * Cloning is forbidden.
     */
    public function __clone() {
        wc_doing_it_wrong(
            __FUNCTION__,
            __( 'Cloning is forbidden.', 'wc-smartpay' ),
            '2.1'
        );
    }

    /**
     * Wakeup
     */
    public function __wakeup() {
        wc_doing_it_wrong(
            __FUNCTION__,
            __( 'Unserializing instances of this class is forbidden.', 'wc-smartpay' ),
            '2.1'
        );
    }

    /**
     * Mocking class __construct
     */
    private function __construct() {

    }

    /**
     * Singletone instance
     *
     * @return SmartPay_Request
     */
    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Extract params from input array
     *
     * @param array $inputArray
     * @param array $params
     * @return array
     */
    protected function extract( $inputArray, $params ) {
        $output = [];
        foreach ( $params as $param ) {

            if ( !array_key_exists( $param, $inputArray ) ) {
                continue;
            }

            $value = $inputArray[$param];

            if ( is_string( $value ) ) {
                $output[$param] = sanitize_text_field( $value );
            }
        }

        return $output;
    }

    /**
     * Retrieve data from $_REQUEST
     *
     * @param array $params
     * @return array
     */
    public function get_request( $params ) {
        return $this->extract( $_REQUEST, $params );
    }

    /**
     * Retrieve body array
     *
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function get_body( $params ) {
        return $this->extract(
            SmartPay_Serializer::instance()->unserialize( file_get_contents( 'php://input' ) ),
            $params
        );
    }

    /**
     * Retrieve $_POST params
     *
     * @param array $params
     * @return array
     */
    public function get_post( $params ) {
        return $this->extract( $_POST, $params );
    }

    /**
     * Retrieve params from $_GET array
     *
     * @param array $params
     * @return array
     */
    public function get_query( $params ) {
        return $this->extract( $_GET, $params );
    }
}
