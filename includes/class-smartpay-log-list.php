<?php
/**
 * Smartpay Plugin Log List
 *
 * @package wc-smartpay
 */

/**
 * SmartPay Log List
 */
class SmartPay_Log_List extends WP_List_Table {
    /*
     * Records per page
     */
    const NUM_PER_PAGE = 20;

    /**
     * Define class dependencies
     *
     * @param array $args constructor arguments.
     */
    public function __construct( $args = array() ) {
        parent::__construct(
            array(
                'singular' => __( 'SmartPay Log', 'wc-smartpay' ),
                'plural'   => __( 'SmartPay Logs', 'wc-smartpay' ),
                'ajax'     => false,
            )
        );
    }

    /**
     * Retrieve list of columns
     *
     * @return array
     */
    public function get_columns() {
        return array(
            'ref_id'  => __( 'Reference ID', 'wc-smartpay' ),
            'type'    => __( 'Type', 'wc-smartpay' ),
            'message' => __( 'Body', 'wc-smartpay' ),
            'date'    => __( 'Date', 'wc-smartpay' ),
        );
    }

    /**
     * Retrieve list of sortable columns
     *
     * @return string[]
     */
    public function get_sortable_columns() {
        return array(
            'date'   => 'date',
            'ref_id' => 'ref_id',
        );
    }

    /**
     * Retrieve search query part
     *
     * @return string
     */
    private function get_search_query() {
        // phpcs:disable
        $search_key = isset( $_REQUEST['s'] ) ? esc_sql( trim( wp_unslash( $_REQUEST['s'] ) ) ) : '';
        // phpcs:enable
        if ( ! empty( $search_key ) ) {
            return sprintf( ' WHERE ref_id LIKE "%s" OR message LIKE "%1$s"', '%' . $search_key . '%' );
        }
        return '';
    }

    /**
     * Retrieve order query part
     *
     * @return string
     */
    private function get_order_query() {
        // phpcs:disable
        $data = $_GET;
        // phpcs:enable
        $orderby = ( isset( $data['orderby'] ) ) ? esc_sql( sanitize_text_field( wp_unslash( $data['orderby'] ) ) ) : 'date';
        $order   = ( isset( $data['order'] ) ) ? esc_sql( sanitize_text_field( wp_unslash( $data['order'] ) ) ) : 'DESC';
        return sprintf( ' ORDER BY %s %s', $orderby, $order );
    }

    /**
     * Retrieve limit query
     *
     * @return string
     */
    private function get_limit_query() {
        $offset = $this->get_pagenum() > 1 ? $this->get_pagenum() * self::NUM_PER_PAGE - self::NUM_PER_PAGE : 0;
        return sprintf(
            ' LIMIT %s OFFSET %s',
            self::NUM_PER_PAGE,
            $offset
        );
    }

    /**
     * Fetching data
     *
     * @return array|object|null
     */
    private function fetch_table_data() {
        global $wpdb;
        $query = sprintf(
            'SELECT ref_id, type, message, `date` FROM %s ',
            $wpdb->prefix . 'smartpay_log'
        );

        $query .= $this->get_search_query();
        $query .= $this->get_order_query();
        $query .= $this->get_limit_query();
        // phpcs:disable
        return $wpdb->get_results( $query, ARRAY_A );
        // phpcs:enable
    }

    /**
     * Retrieve item count
     *
     * @return array|object|null
     */
    private function get_item_count() {
        global $wpdb;

        $query  = sprintf(
            'SELECT count(id) AS cnt from %s',
            $wpdb->prefix . 'smartpay_log'
        );
        $query .= $this->get_search_query();
        $query .= $this->get_order_query();
        // phpcs:disable
        $result = $wpdb->get_row( $query );
        // phpcs:enable
        return $result ? $result->cnt : 0;
    }

    /**
     * Clear log
     *
     * @return void
     */
    private function clear_log() {
        global $wpdb;
        // phpcs:disable
        $wpdb->query( sprintf( 'TRUNCATE %s', $wpdb->prefix . 'smartpay_log' ) );
        // phpcs:enable
    }

    /**
     * Prepare items
     *
     * @return void
     */
    public function prepare_items() {
        // phpcs:disable
        if ( isset( $_POST['clear_log'] ) ) {
            $this->clear_log();
        }
        // phpcs:enable

        $total = $this->get_item_count();
        $this->set_pagination_args(
            array(
                'total_items' => $total,
                'per_page'    => self::NUM_PER_PAGE,
                'total_pages' => ceil( $total / self::NUM_PER_PAGE ),
            )
        );
        $this->items = $this->fetch_table_data();
    }

    /**
     * Display log body
     *
     * @param array $item line itemm object.
     * @return string
     */
    public function column_message( $item ) {
        $item = (object) $item;
        return '<textarea disabled rows="10" cols="50">' . esc_html( $item->message ) . '</textarea>';
    }

    /**
     * Display column info
     *
     * @param array  $item item object.
     * @param string $column_name column name.
     * @return mixed|string|void
     */
    public function column_default( $item, $column_name ) {
        return isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
    }

    /**
     * Display content
     *
     * @return void
     */
    public function display() {
        $this->prepare_items();
        ?>
        <div class="wrap">
            <h2><?php esc_html_e( 'Smartpay Logs', 'wc-smartpay' ); ?></h2>
            <div id="smartpay-wp-list-table">
                <div id="smartpay-log-post-body">
                    <form id="smartpay-log-list-form" method="get">
                        <?php // phpcs:disable ?>
                        <input type="hidden" name="page" value="<?php echo esc_attr( wp_unslash( $_REQUEST['page'] ?? null ) ); ?>"/>
                        <?php // phpcs:enable ?>
                        <?php $this->search_box( __( 'Search', 'wc-smartpay' ), 'smartpay-log-search' ); ?>
                    </form>
                    <form id="smartpay-log-list-form" method="post">
                        <?php submit_button( __( 'Clear Log', 'wc-smartpay' ), 'primary', 'clear_log' ); ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
        parent::display();
    }
}
